<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/5/22
 * Time: 11:22 AM
 */

namespace qmrp\weiaccount;

use GuzzleHttp\Client;

/**
 * Class TempMsg 模版信息
 *
 * @package qmrp\weiaccount
 */
class TempMsg
{
    const URL = 'https://api.weixin.qq.com';

    public $client;

    public $access_token;

    public $actMap = [
        'set_industry'  => '/cgi-bin/template/api_set_industry',
        'get_industry'  => '/cgi-bin/template/get_industry',
        'get_temp_id'   => '/cgi-bin/template/api_add_template',
        'get_temp_list' => '/cgi-bin/template/get_all_private_template',
        'del_template'  => '/cgi-bin/template/del_private_template',
        'send_msg'      => '/cgi-bin/message/template/send'
    ];

    public function __construct($access_token)
    {
        $this->access_token = $access_token;
        $this->hashTable = $hashTable;
        $this->client = new Client(['base_uri'=>self::URL,'timeout'=>2.0]);
    }

    private function getRequest($act,$params=[]):array
    {
        $url = self::URL.$this->actMap[$act];
        $res = $this->client->request('GET',$url,array_merge(['access_token'=>$this->access_token],$params));
        $httpCode = $res->getStatusCode();
        if(200!=$httpCode)
            return ['errcode'=>1,'errmsg'=>'http request error httpd_code:'.$httpCode];
        $body = $res->getBody()->getContents();
        $res = @json_decode($res,true);
        if(is_array($res))
            return $res;
        return ['errcode'=>1,'errmsg'=>'http request error'];
    }

    private function postRequest($act,$params=[]):array
    {
        $url = self::URL.$this->actMap[$act]."?access_token=".$this->access_token;
        $res = $this->client->request('POST',$url,['body'=>json_encode($params)]);
        $httpCode = $res->getStatusCode();
        if(200!=$httpCode)
            return ['errcode'=>1,'errmsg'=>'http request error httpd_code:'.$httpCode];
        $body = $res->getBody()->getContents();
        $res = @json_decode($res,true);
        if(is_array($res))
            return $res;
        return ['errcode'=>1,'errmsg'=>'http request error'];
    }

    /**
     * 设置公众号所属行业
     * @param int $id1 公众号模板消息所属行业编号
     * @param int $id2 公众号模板消息所属行业编号
     * @return array
     */
    public function setIndustry(int $id1,int $id2):array
    {
        $rm = $this->postRequest('set_industry',['industry_id1'=>$id1,'industry_id2'=>$id2]);
        return $rm;
    }

    /**
     * 获取设置的行业信息
     * @return array
     */
    public function getIndeustry():array
    {
        return $this->getRequest('get_industry');
    }

    /**
     * 获取模版ID
     * @param string $shortId
     * @return array
     */
    public function getTempId($shortId):array
    {
        return $this->postRequest('get_temp_id',['template_id_short'=>$shortId]);
    }

    /**
     * 获取模板列表
     * @return array
     */
    public function getTempList()
    {
        $rm = $this->getRequest('get_temp_list');
        return $rm;
    }

    /**
     * 删除模板
     * @param string $tempId 模版ID
     * @return array
     */
    public function delTemp(string $tempId)
    {
        return $this->postRequest('del_template',['template_id'=>$tempId]);
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
    public function sendMsg(string $tempId,string $openId,string $url="",array $miniparam=[],array $params=[])
    {
        $data = [
            'touser' => $openId,
            'template_id' => $tempId,
            'data' => $params
        ];
        if($url!="")
            $data['url'] = $url;
        if(!empty($miniparam))
            $data['miniprogram'] = $miniparam;
        return $this->postRequest('send_msg',$data);
    }

}