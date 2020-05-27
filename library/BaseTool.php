<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/5/25
 * Time: 2:45 PM
 */

namespace qmrp\weiaccount\library;

use GuzzleHttp\Client;


class BaseTool
{
    const URL = 'https://api.weixin.qq.com';

    public $client;

    public $access_token;

    public $actMap = [];

    public function __construct($access_token)
    {
        $this->access_token = $access_token;
        $this->client = new Client(['base_uri'=>self::URL,'timeout'=>2.0]);
    }

    public function getRequest($act,$params=[]):array
    {
        $url = self::URL.$this->actMap[$act];
        $params['access_token'] = $this->access_token;
        $res = $this->client->request('GET',$url,['query'=>$params]);
        $httpCode = $res->getStatusCode();
        if(200!=$httpCode)
            return ['errcode'=>1,'errmsg'=>'http request error httpd_code:'.$httpCode];
        $body = $res->getBody()->getContents();
        $res = @json_decode($body,true);
        if(is_array($res))
            return $res;
        return ['errcode'=>1,'errmsg'=>'http request error'];
    }

    public function postRequest($act,$params=[],$form=false):array
    {
        $url = self::URL.$this->actMap[$act]."?access_token=".$this->access_token;
        if($form){
            $res = $this->client->request('POST',$url,['multipart'=>$params,]);
        }else {
            $res = $this->client->request('POST', $url, ['body' => json_encode($params)]);
        }
        $httpCode = $res->getStatusCode();
        if(200!=$httpCode)
            return ['errcode'=>1,'errmsg'=>'http request error httpd_code:'.$httpCode];
        $body = $res->getBody()->getContents();
        $res = @json_decode($body,true);
        if(is_array($res))
            return $res;
        return ['errcode'=>1,'errmsg'=>'http request error'];
    }

    public function getResources($act,$params=[])
    {
        $url = self::URL.$this->actMap[$act];
        $params['access_token'] = $this->access_token;
        $res = $this->client->request('GET',$url,['query'=>$params]);
        $httpCode = $res->getStatusCode();
        if(200!=$httpCode)
            return ['errcode'=>1,'errmsg'=>'http request error httpd_code:'.$httpCode];
        $body = $res->getBody()->getContents();
        $res = @json_decode($body,true);
        if(is_array($res))
            return $res;
        $header = $res->getHeader('Content-Type');
        header('Content-Type:'.$header[0]);
        exit($body);
    }
}