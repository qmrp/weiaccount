<?php
namespace qmrp\weiaccount;

use yii\base\Component;
use qmrp\weiaccount\replay\Template;
use qmrp\weiaccount\library\AccessToken;
use qmrp\weiaccount\library\WXBizMsgCrypt;
use qmrp\weiaccount\replay\AutoReplayConfig;
use qmrp\weiaccount\library\AutoReplay;
use GuzzleHttp\Client;

class Weixin extends Component
{
    public $appId = "";

    public $secret = "";

    public $token = "";

    public $redis;

    public $hashTable = 'qmrp_weixin_temp_list';

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
     */
    private $autoReplayConfig;

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

    public function init()
    {
        if(null === $this->accessToken){
            $this->accessToken = new AccessToken($this->appId,$this->secret);
        }

        $this->access_token = $this->accessToken->getAccessToken();
        $this->template = new Template($this->token,$this->encodingAesKey,$this->appId,$this->msgMode);
        $this->httpClient = new Client(['base_uri'=>$this->url,'timeout'=>2.0]);
    }

    public function getAccessToken()
    {
        return $this->access_token;
    }

    public function setAutoReplayConfig(AutoReplayConfig $replay)
    {
        $this->autoReplayConfig = $replay;
        return $this;
    }

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
        return $this;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function getRequest()
    {
        return $this->req;
    }

    public function Response()
    {
        if(null==$this->autoReplayConfig){
            $this->template->transfer();
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

        if($res&&isset($res['replayType'])){
            $funName = strval($res['replayType']);
            $this->template->setClient($this->customId,$this->ownerId);
            $response = $this->template->$funName($res);
        }else{
            $this->template->setClient($this->customId,$this->ownerId);
            $response = $this->template->transfer();
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

    public function setIn()
    {
        if($rm['errcode']==0){
            $list = $rm['template_list'];
            $redis
            foreach ($list as $item) {

            }
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
            throw new \Exception("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
}

