<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/5/9
 * Time: 5:13 PM
 */

namespace qmrp\weiaccount\kf;
use GuzzleHttp\Client;

class Account
{
    const URL = 'https://api.weixin.qq.com';

    public $client;

    public $actionMap = [
        'create' => '/customservice/kfaccount/add',
        'update' => '/customservice/kfaccount/update',
        'del' => '/customservice/kfaccount/del',
        'setHead' => '/customservice/kfaccount/uploadheadimg',
        'query' => '/cgi-bin/customservice/getkflist',
        'send' => '/cgi-bin/message/custom/send',
        'typing' => '/cgi-bin/message/custom/typing'
    ];

    public function __construct($access_token)
    {
        $this->access_token = $access_token;
        $this->client = new Client(['base_uri'=>self::URL,'timeout'=>2.0]);
    }

    public function getKfList()
    {
        $res = $this->client->request('GET',self::URL.$this->actionMap['query'],['query'=>['access_token'=>$this->access_token]]);
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
     * {
     *    "kf_account" : "test1@test",
     *    "nickname" : "客服1",
     *    "password" : "pswmd5"
     * }
     */
    public function createKf($account,$username,$password,$isTest = false)
    {
        $data = ['kf_account'=>$account,'nickname'=>$username,'password'=>$password];
        $res = $this->client->request('POST',self::URL.$this->actionMap['create']."?access_token=".$this->access_token,['body'=>json_encode($data)]);
        $httpdCode = $res->getStatusCode();
        if($httpdCode!=200)
            return false;
        $res = $res->getBody()->getContents();
        $res = @json_decode($res,true);
        if(is_array($res)&&$res['errcode']==0)
            return true;
        if($isTest)
            return $res;
        return false;
    }
}