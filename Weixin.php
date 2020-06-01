<?php
namespace qmrp\weiaccount;

use qmrp\weiaccount\replay\ActiveReplayConfig;
use qmrp\weiaccount\replay\Validate;
use yii\base\Component;
use qmrp\weiaccount\replay\Template;
use qmrp\weiaccount\library\AccessToken;
use qmrp\weiaccount\library\WXBizMsgCrypt;
use qmrp\weiaccount\replay\AutoReplayConfig;
use qmrp\weiaccount\library\AutoReplay;
use GuzzleHttp\Client;
use qmrp\weiaccount\exception\ResponseException;
use yii\redis\Connection;

class Weixin extends Component
{
    public $appId = "";

    public $secret = "";

    public $token = "";

    public $redis;

    public $hashTable = 'qmrp_weixin_temp_list';

    public $prefix = "qmrp";

    /**
     * @var bool 上下文连续对话
     */
    public $context = false;

    /**
     * @var int 上下文有效期
     */
    public $expire = 600;

    /*
     * 微信信息模式
     * 1明文 2混合 3加密
     */
    public $msgMode = 1;

    public $encodingAesKey = "";

    private $accessToken;

    private $replayObj;

    private $access_token = [];

    /*
     * 解析后的请求数据
     */
    private $req;

    private $customId;

    private $ownerId;

    /*
     * 推送事件类型subscribe,unsubscribe,SCAN,LOCATION,CLICK,VIEW
     */
    private $eventType;

    private $eventKey;

    private $siteInfo;

    private $tikcet;

    private $msgId;

    /*
     * 接收普通消息类型text,image,voice,video,shortvideo,location,link,music,news,event
     */
    private $msgType;

    /*
     * 自动回复规则类
     * @var AutoReplayConfig
     */
    private $autoReplayConfig;

    /**
     * 上下文回复规则类
     * @var ActiveReplayConfig
     */
    private $activeReplayConfig;

    private $createTime;

    /*
     * 临时素材id
     */
    private $mediaId;

    /*
     * 视频缩略图素材id
     */
    private $thumbMediaId;

    /*
     * 图片信息，图片链接
     */
    private $picUrl;

    /*
     * 链接信息链接
     */
    private $link;

    private $title;

    private $description;

    /*
     * 语音识别结果
     */
    private $recognition;

    /*
     * 语音格式
     */
    private $format;

    private $content;

    private $template;

    private $httpClient;

    private $url = "https://api.weixin.qq.com/";

    private $urlMap = [
        'getTemp' => 'cgi-bin/template/get_all_private_template',
        'delTemp' => 'cgi-bin/template/del_private_template',
        'sendTemp' => 'cgi-bin/message/template/send',
        'getMenu' => 'cgi-bin/get_current_selfmenu_info',
        'createMenu' => 'cgi-bin/menu/create',
        'delMenu' => 'cgi-bin/menu/delete',
        'userSummary' => 'datacube/getusersummary',
        'userCumulate' => 'datacube/getusercumulate'
    ];

    private $errors = [];

    /**
     * init
     */
    public function init()
    {
        if (is_string($this->redis)) {
            $this->redis = Yii::$app->get($this->redis);
        } elseif (is_array($this->redis)) {
            if (!isset($this->redis['class'])) {
                $this->redis['class'] = Connection::className();
            }
            $this->redis = \Yii::createObject($this->redis);
        }

        if(null === $this->accessToken){
            $this->accessToken = new AccessToken($this->appId,$this->secret);
        }

        $this->access_token = $this->accessToken->getAccessToken();
        $this->template = new Template($this->token,$this->encodingAesKey,$this->appId,$this->msgMode);
        $this->httpClient = new Client(['base_uri'=>$this->url,'timeout'=>2.0]);
        if($this->content) {
            if (!$this->redis instanceof yii\redis\Connection)
                throw new ResponseException(101, '使用持续交互模式必须设置redis');
        }

        parent::init();
    }

    /**
     * 获取accessToken
     * @return array
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * 设置自动回复规则类
     * @param AutoReplayConfig $replay
     * @return $this
     */
    public function setAutoReplayConfig(AutoReplayConfig $replay)
    {
        $this->autoReplayConfig = $replay;
        return $this;
    }

    public function setActiveReplayConfig(ActiveReplayConfig $replay)
    {
        $this->activeReplayConfig = $replay;
        return $this;
    }

    /**
     * 设置接收信息
     * @param $xml
     * @return $this
     * @throws \Exception
     */
    public function setRequestMsg($xml)
    {
        if($this->msgMode==3){
            $crypt = new WXBizMsgCrypt($this->token,$this->encodingAesKey,$this->appId);
            $encryptMsg = "";
            $nonce = $this->getRandomStr();
            $timeStamp = time();
            $crypt->encryptMsg($xml,$timeStamp, $nonce, $encryptMsg);
            $this->req = $this->xmlToArray($encryptMsg);
        }else{
            $this->req = $this->xmlToArray($xml);
        }

        $this->createTime = $this->req['CreateTime'];

        $this->msgType = $this->req['MsgType'];

        if(isset($this->req['Event']))
            $this->eventType = $this->req['Event'];

        if(isset($this->req['EventKey']))
            $this->eventKey = $this->req['EventKey'];

        if(isset($this->req['Ticket']))
            $this->tikcet = $this->req['Ticket'];

        if(isset($this->req['MsgId']))
            $this->msgId = $this->req['MsgId'];

        if(isset($this->req['MediaId']))
            $this->mediaId = $this->req['MediaId'];

        if(isset($this->req['PicUrl']))
            $this->picUrl = $this->req['PicUrl'];

        if(isset($this->req['Title']))
            $this->title = $this->req['Title'];

        if (isset($this->req['Url']))
            $this->link = $this->req['Url'];

        if (isset($this->req['Description']))
            $this->description = $this->req['Description'];

        if (isset($this->req['ThumbMediaId']))
            $this->thumbMediaId = $this->req['ThumbMediaId'];

        if(isset($this->req['Content']))
            $this->content = $this->req['Content'];

        if(isset($this->req['Recognition']))
            $this->recognition = $this->req['Recognition'];

        if('location'==$this->msgType){
            $this->siteInfo['lat'] = $this->req['Location_X'];
            $this->siteInfo['lng'] = $this->req['Location_Y'];
        }
        if("LOCATION"==$this->eventType){
            $this->siteInfo['lat'] = $this->req['Latitude'];
            $this->siteInfo['lng'] = $this->req['Longitude'];
        }
        $this->customId = $this->req['FromUserName'];
        $this->ownerId = $this->req['ToUserName'];
        $this->template->setClient($this->customId, $this->ownerId);
        return $this;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * 获取接收信息数据
     * @return array $req
     */
    public function getRequest()
    {
        return $this->req;
    }

    /**
     * 进入持续交互模式
     * @param array $callback ['app\common\common','excute']
     * @param string $activeName
     * @return mixed
     * @throws ResponseException
     */
    public function setActive(string $activeName,int $step = 0)
    {
        if($this->context&&$this->activeReplayConfig){
            if($this->activeReplayConfig->isSetReplay($activeName)) {
                $key = $this->prefix . ":" . $this->customId;
                $this->redis->set($key, $activeName . "|" . $step);
                return $this->redis->expire($key, $this->expire);
            }else{
                throw new ResponseException(102,"context {$activeName} not defined");
            }
        }
        throw new ResponseException(101,'context not turned on');
    }

    /**
     * 获取当前用户交互信息
     * @return mixed
     * @throws ResponseException
     */
    public function getActive()
    {
        if($this->context){
            $key = $this->prefix.":".$this->customId;
            $res =  $this->redis->get($key);
            $rm = [];
            if($res!=""){
                $res = explode("|",$res);
                $rm['activeName'] = $res[0];
                $rm['step'] = $res[1];
            }
            return $rm;
        }
        throw new ResponseException(101,'context not turned on');
    }

    /**
     * @return mixed
     * @throws ResponseException
     */
    public function nextStep()
    {
        $res = $this->getActive();
        $res['step'] += 1;
        return $this->setActive($res['activeName'],$res['step']);
    }

    /**
     * 退出持续交互
     * @return mixed
     * @throws ResponseException
     */
    public function quitAcitve()
    {
        if($this->context){
            $key = $this->prefix.":".$this->customId;
            return $this->redis->del($key);
        }
        throw new ResponseException(101,'context not turned on');
    }

    /**
     * @param $activeName
     * @param $name
     * @param $val
     * @param int $expire
     * @return mixed
     * @throws ResponseException
     */
    public function setActiveContent($activeName,$name,$val)
    {
        if($this->context){
            $key = $this->prefix.":".$this->customId."-".$activeName;
            $content = $this->redis->get($key);
            if($content!=""){
                $content = @json_decode($content,true);
                if(is_array($content)){
                    $content[$name] = $val;
                }else{
                    $content = [$name=>$val];
                }
            }else{
                $content = [$name=>$val];
            }
            $this->redis->set($key,json_encode($content));
            return $this->redis->expire($key,$this->expire);
        }
        throw new ResponseException(101,'context not turned on');
    }

    public function getActiveContent($activeName)
    {
        if($this->context){
            $key = $this->prefix.":".$this->customId."-".$activeName;
            $content = $this->redis->get($key);
            if($content!=""){
                $content = @json_decode($content,true);
                if(is_array($content)){
                    return $content;
                }
            }
            return [];
        }
        throw new ResponseException(101,'context not turned on');
    }

    /**
     * 自动回复返回信息
     * @return string
     */
    public function Response()
    {
        if(null==$this->autoReplayConfig){
            $this->template->transfer();
        }
        /*
         * 开启上下文进入
         */
        if($this->context&&$this->activeReplayConfig){
            $active = $this->getActive();
            if(!empty($active)){
                $activeName = $active['activeName'];
                $step = $active['step'];
                $steps = $this->activeReplayConfig->getSteps($activeName);

                $activeReplay = $this->activeReplayConfig->getReplay($activeName,$step);
                $format = $this->activeReplayConfig->getFormat($activeName,$step);
                if($format['validate']=='sys'){     //系统自带校验方法
                    $vFun = $format['rule'][0];
                    $format['rule'][0] = $this;
                    if(!call_user_func_array([Validate::class,$vFun],$format['rule'])){
                        return $this->template->text(['content'=>$activeReplay['message']]);
                    }
                }else{
                    if(!call_user_func_array($format['rule'],[$this])){
                        return $this->template->text(['content'=>$activeReplay['message']]);
                    }
                }
                $val = call_user_func_array($activeReplay['callback'],[$this]);
                $this->setActiveContent($activeName,$activeReplay['name'],$val);

                if(($step+1)==$steps){
                    $finish = $this->activeReplayConfig->getFinish($activeName);
                }
                if(isset($finish)){
                    $res = call_user_func_array($finish,[$this]);
                    if(isset($res['replayType'])) {
                        $funName = $res['replayType'];
                        return $this->template->$funName($res);
                    }
                    return '';
                }
                $this->nextStep();
                $activeReplay = $this->activeReplayConfig->getReplay($activeName,$step+1);
                $funName = $activeReplay['replay']['replayType'];
                $response = $this->template->$funName($activeReplay['replay']);
                return $response;
            }
        }
        $res = false;
        switch ($this->msgType){
            case 'text':
                $res = $this->autoReplayConfig->hitContentReplay($this->content);
                break;
            case 'image':
                break;
            case 'voice':
                break;
            case 'video':
                break;
            case 'shortvideo':
                break;
            case 'location':
                break;
            case 'music':
                break;
            case 'link':
                break;
            case 'event':
                $res = $this->autoReplayConfig->hitEventReplay($this->eventType,$this->eventKey);
                break;
        }

        try {

            if ($res && isset($res['replayType'])) {
                $funName = strval($res['replayType']);
                if ($funName == 'callback' && isset($res['function'])) {
                    $funName = $res['function'];
                    $res = call_user_func_array([$funName[0], $funName[1]], [$this]);
                    $funName = strval($res['replayType']);
                }
                if ($funName == 'template') {
                    $this->sendTempMsg($res['tempId'], $this->customId, $res['params']);
                    $response = '';
                } else {
                    $response = $this->template->$funName($res);
                }

            } else {
                $response = $this->template->transfer();
            }
        }catch (ResponseException $e){
            $response = $this->template->text(['content'=>$e->getMessage().$e->getFile().$e->getLine()]);
        }
        return $response;
    }

    /*
     * 获取公众号菜单
     */
    public function getMenu()
    {
        $res = $this->httpClient->request('GET',$this->url.$this->urlMap['getMenu'],['query'=>['access_token'=>$this->access_token['access_token']]]);
        $httpdCode = $res->getStatusCode();
        if($httpdCode!=200)
            return false;
        $res = $res->getBody()->getContents();
        $res = @json_decode($res,true);
        if(is_array($res))
            return $res;
        return false;
    }
    /*
     * 删除菜单
     * {"errcode":0,"errmsg":"ok"}
     */
    public function delMenu()
    {
        $res = $this->httpClient->request('GET',$this->url.$this->urlMap['delMenu'],['query'=>['access_token'=>$this->access_token['access_token']]]);
        $httpdCode = $res->getStatusCode();
        if($httpdCode!=200)
            return false;
        $res = $res->getBody()->getContents();
        $res = @json_decode($res,true);
        if(is_array($res)&&$res['errcode']==0)
            return true;
        return false;
    }

    /**
     * 创建菜单
     */
    public function createMenu($menu)
    {
        if(is_array($menu)) {
            $menu = json_encode($menu);
        }

        $res = $this->httpClient->request('POST',$this->url.$this->urlMap['createMenu']."?access_token=".$this->access_token['access_token'],['body'=>$menu]);
        $httpdCode = $res->getStatusCode();
        if($httpdCode!=200)
            return false;
        $res = $res->getBody()->getContents();
        $res = @json_decode($res,true);
        if(is_array($res)&&$res['errcode']==0)
            return true;
        return $res;

    }

    /**
     * 获取模版信息列表
     * @return array
     */
    public function getTempList()
    {
        $temp = new TempMsg($this->access_token['access_token']);
        $rm = $temp->getTempList();
        if($this->redis instanceof yii\redis\Connection) {
            if ($rm['errcode'] == 0) {
                $list = $rm['template_list'];
                foreach ($list as $item) {
                    $this->redis->hset($this->hashTable,$item['template_id'],json_encode($item));
                }
            }
        }
        return $rm;
    }

    /**
     * 发送模版信息
     * @param string $tempId 模版信息Id
     * @param string $openId 接收人
     * @param string $url 模版信息跳转链接优先级低于小程序
     * @param array $miniparam 模彼信息跳转小程序
     * @param array $params 模版信息参数必须与模版对的参数对应
     * @return array
     */
    public function sendTempMsg(string $tempId,string $openId,array $params=[],string $url="",array $miniparam=[])
    {
        if(!$this->checkTempParam($tempId,$params)){
            $temp = new TempMsg($this->access_token['access_token']);
            $rm = $temp->sendMsg($tempId,$openId,$url,$miniparam,$params);
            return $rm;
        }
        return ['errcode'=>1,'msg'=>'参数错误','data'=>$this->errors];
    }

    private function checkTempParam($tempId,$params){
        if($this->redis instanceof yii\redis\Connection) {
            $tempInfo = $this->redis->hget($this->hashTable,$tempId);
            if($tempId!=""){
                $tempInfo = json_decode($tempInfo,true);
            }else{
                $tempList = $this->getTempList();
                if($tempList['errcode'])
                    return false;
                foreach ($tempList['template_list'] as $item){
                    if($item['template_id']==$tempId) {
                        $tempInfo = $item;
                        break;
                    }
                }
            }
            if(empty($tempInfo))
                return false;
            $content = $tempInfo['content'];
            $arr = explode("{{", $content);
            unset($arr[0]);
            $keys = [];
            foreach ($arr as $value) {
                $keys[] = explode(".DATA}}", $value)[0];
            }
            $pkeys = array_keys($params);
            $difArr = array_diff($keys,$pkeys);
            if(!empty($difArr)) {
                $this->errors = $difArr;
                return false;
            }
            return true;
        }
    }

    private function getRandomStr()
    {

        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }

    private function xmlToArray($xml)
    {
        if(!$xml){
            throw new ResponseException(102,"xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
}

