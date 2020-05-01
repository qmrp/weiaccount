<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/4/29
 * Time: 7:58 PM
 */

namespace qmrp\weiaccount\library;

use GuzzleHttp\Client;

class AccessToken
{
    public $url = "https://api.weixin.qq.com/cgi-bin/token";

    public $appId = '';

    public $secret = '';

    public $tokenPath = __DIR__.'/../data/';

    public $fileName = "access_token.json";

    public $access_token = "";

    public function __construct($appId,$secret)
    {
        $this->appId = $appId;
        $this->secret = $secret;
    }

    public function getAccessToken()
    {
        if(is_file($this->tokenPath.$this->fileName)){
            $this->access_token = file_get_contents($this->tokenPath.$this->fileName);
            if(""!=$this->access_token){
                $this->access_token = json_decode($this->access_token,true);
                if(!isset($this->access_token['expires_in'])||$this->access_token['expires_in']<time())
                {
                    $this->getAccessTokenByWeixin();
                    return $this->access_token;
                }
                return $this->access_token;
            }else{
                $this->getAccessTokenByWeixin();
                return $this->access_token;
            }
        }else{
            if(!is_dir($this->tokenPath))
                mkdir($this->tokenPath,0777,true);
            $fp = fopen($this->tokenPath.$this->fileName,'w');
            fclose($fp);
            if(!is_file($this->tokenPath.$this->fileName))
                throw new \Exception('存储token的文件不存在');
            $this->getAccessTokenByWeixin();
            return $this->access_token;
        }
    }

    public function getAccessTokenByWeixin()
    {
        $client = new Client(['base_uri'=>"https://api.weixin.qq.com",'timeout'=>2.0]);
        $response = $client->request('GET', $this->url, [
            'query' => ['appId' => $this->appId,'secret'=>$this->secret,'grant_type'=>'client_credential']
        ]);
        $httpdCode = $response->getStatusCode();
        if($httpdCode!=200)
            return false;
        $response = $response->getBody()->getContents();
        $response = json_decode($response,true);
        if(!isset($response['access_token']))
            return false;
        $response['expires_in'] += time();
        $this->access_token = $response;
        file_put_contents($this->tokenPath.$this->fileName,json_encode($response));
        return $response;
    }
}