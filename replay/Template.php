<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/4/30
 * Time: 4:04 PM
 */

namespace qmrp\weiaccount\replay;


    /**
     * 信息模版
     */
class Template
{
    public $jurl=false;
    public $token,$app_id,$encoding_aes_key,$msgMode,$resultStr;
    public $toUserName;
    public $fromUserName;
    public $time;
    public function __construct($token,$encoding_aes_key,$app_id,$msgMode){
        $this->token = $token;
        $this->app_id = $app_id;
        $this->encoding_aes_key = $encoding_aes_key;
        $this->msgMode = $msgMode;
        $this->time = time();
    }

    /*
     * to为收到信息的FromUserName,from为ToUserName
     */
    public function setClient($to,$from)
    {
        $this->toUserName = $to;
        $this->fromUserName = $from;
    }

    //文本回复
    public function  text($params){
        $tpl = "<xml>
                        <ToUserName><![CDATA[{$this->toUserName}]]></ToUserName>
                        <FromUserName><![CDATA[{$this->fromUserName}]]></FromUserName>
                        <CreateTime>{$this->time}</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                    </xml>";
        $this->resultStr = sprintf($tpl, $params['content']);
        return $this->encryptMsg();
    }

    //单图文回复
    public function news($params){
        $tpl = "<xml>
                        <ToUserName><![CDATA[{$this->toUserName}]]></ToUserName>
                        <FromUserName><![CDATA[{$this->fromUserName}]]></FromUserName>
                        <CreateTime>{$this->time}</CreateTime>
                        <MsgType><![CDATA[news]]></MsgType>
                        <ArticleCount>1</ArticleCount>
                        <Articles>
                                <item>
                                <Title><![CDATA[%s]]></Title>
                                <Description><![CDATA[%s]]></Description>
                                <PicUrl><![CDATA[%s]]></PicUrl>
                                <Url><![CDATA[%s]]></Url>
                                </item>
                        </Articles>
                        </xml> ";
        $this->resultStr = sprintf($tpl , $params['title'], $params['description'], $params['picUrl'], $params['url']);
        return $this->encryptMsg();
    }

    public function image($params)
    {
        $tpl = "<xml>
                        <ToUserName><![CDATA[{$this->toUserName}]]></ToUserName>
                        <FromUserName><![CDATA[{$this->fromUserName}]]></FromUserName>
                        <CreateTime>{$this->time}</CreateTime>
                        <MsgType><![CDATA[image]]></MsgType>
                        <Image>
                            <MediaId><![CDATA[%s]]></MediaId>
                        </Image>
                       </xml>";
        $this->resultStr = sprintf($tpl,$params['mediaId']);
        return $this->encryptMsg();
    }

    public function video($params)
    {
        $tpl = "<xml>
                      <ToUserName><![CDATA[{$this->toUserName}]]></ToUserName>
                      <FromUserName><![CDATA[{$this->fromUserName}]]></FromUserName>
                      <CreateTime>{$this->time}</CreateTime>
                      <MsgType><![CDATA[video]]></MsgType>
                      <Video>
                        <MediaId><![CDATA[%s]]></MediaId>
                        <Title><![CDATA[%s]]></Title>
                        <Description><![CDATA[%s]]></Description>
                      </Video>
                    </xml>";
        $this->resultStr = sprintf($tpl,$params['mediaId'],$params['title'],$params['description']);
        return $this->encryptMsg();
    }

    /**
     *语音消息
     */
    public function voice($params)
    {
        $tpl = "<xml>
                      <ToUserName><![CDATA[{$this->toUserName}]]></ToUserName>
                      <FromUserName><![CDATA[{$this->fromUserName}]]></FromUserName>
                      <CreateTime>{$this->time}</CreateTime>
                      <MsgType><![CDATA[voice]]></MsgType>
                      <Voice>
                        <MediaId><![CDATA[%s]]></MediaId>
                      </Voice>
                    </xml>";
        $this->resultStr = sprintf($tpl,$params['mediaId']);
        return $this->encryptMsg();
    }

    public function music($params)
    {
        $tpl = "<xml>
                      <ToUserName><![CDATA[{$this->toUserName}]]></ToUserName>
                      <FromUserName><![CDATA[{$this->fromUserName}]]></FromUserName>
                      <CreateTime>{$this->time}</CreateTime>
                      <MsgType><![CDATA[music]]></MsgType>
                      <Music>
                        <Title><![CDATA[%s]]></Title>
                        <Description><![CDATA[%s]]></Description>
                        <MusicUrl><![CDATA[%s]]></MusicUrl>
                        <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                        <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
                      </Music>
                    </xml>";
        $this->resultStr = sprintf($tpl,$params['title'],$params['description'],$params['musicUrl'],$params['hqMusicUrl'],$params['thumbMediaId']);
        return $this->encryptMsg();
    }

    /**
     *转给客服
     */
    public function transfer()
    {
        $tpl = "<xml> 
                  <ToUserName><![CDATA[{$this->toUserName}]]></ToUserName>
                  <FromUserName><![CDATA[{$this->fromUserName}]]></FromUserName>  
                  <CreateTime>{$this->time}</CreateTime>  
                  <MsgType><![CDATA[transfer_customer_service]]></MsgType> 
                </xml>";
        $this->resultStr = $tpl;
        return $this->encryptMsg();
    }

    /**
     *统一下单Xml模版
     */
    public function prepayXml($data)
    {
        $payTpl = "<xml>
                       <appid>%s</appid>
                       <body>%s</body>
                       <mch_id>%s</mch_id>
                       <nonce_str>%s</nonce_str>
                       <notify_url>%s</notify_url>
                       <openid>%s</openid>
                       <out_trade_no>%s</out_trade_no>
                       <spbill_create_ip>%s</spbill_create_ip>
                       <total_fee>%s</total_fee>
                       <trade_type>%s</trade_type>
                       <sign>%s</sign>
                    </xml>";
        return sprintf($payTpl,$data['appid'],$data['body'],$data['mch_id'],$data['nonce_str'],$data['notify_url'],$data['openid'],$data['out_trade_no'],$data['spbill_create_ip'],$data['total_fee'],$data['trade_type'],$data['sign']);
    }

    /**
     *回调返回
     */
    public function notify($data)
    {
        $notifyTpl = "<xml>
                        <return_code><![CDATA[%s]]></return_code>
                        <return_msg><![CDATA[%s]]></return_msg>
                      </xml>";
        return sprintf($notifyTpl,$data['return_code'],$data['return_msg']);
    }

    public function encryptMsg()
    {
        if($this->msgMode==3){
            $pc = new WXBizMsgCrypt($this->token, $this->encoding_aes_key, $this->app_id);
            $nonce = $this->getRandomStr();
            $timeStamp = time();
            $encryptMsg = '';
            $errCode = $pc->encryptMsg($this->resultStr, $timeStamp, $nonce, $encryptMsg);
            $this->resultStr = $encryptMsg;
        }
        return $this->resultStr;
    }

    function getRandomStr()
    {

        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }

}