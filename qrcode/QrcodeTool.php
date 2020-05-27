<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/5/9
 * Time: 7:18 PM
 */

namespace qmrp\weiaccount\qrcode;

use qmrp\weiaccount\library\BaseTool;

class QrcodeTool extends BaseTool
{
    public $actMap = [
        'createQr' => '/cgi-bin/qrcode/create'
    ];
    /*
     * @params $scene string 二维码参数整数型1--100000，字符型1到64
     * @params $expire string 有效时长，为0时为永久,最大值为2592000
     * @params $type string 二维码参数类型整数型或字符窜型,default 整数型
     */
    public function createQr($scene,$expire,$type='int')
    {
        if($type == 'int') {
            if($scene<1 || $scene>100000)
                return ['errcode' => 1,'errmsg'=>'整数型参数有效值范围为1-100000'];
            $data = [
                'action_info' => ['scene' => ['scene_id' => $scene]],
                'action_name' => 'QR_SCENE'
            ];
        }else{
            $len = strlen($scene);
            if($len<1 || $len>64)
                return ['errcode' => 1,'errmsg'=>'字符型参数有效长度范围为1-64'];
            $data = [
                'action_info' => ['scene' => ['scene_str' => $scene]],
                'action_name' => 'QR_STR_SCENE'
            ];
        }
        if($expire==0){
            if($type == 'int')
                $data['action_name'] = 'QR_LIMIT_SCENE';
            else
                $data['action_name'] = 'QR_LIMIT_STR_SCENE';
        }else{
            if($expire>2592000)
                $expire = 2592000;
            $data['expire_seconds'] = $expire;
        }
        $res = $this->postRequest('createQr',$data);
        return $res;
    }

    /*
     * @param $ticket 生成二维码返回的票据
     * @return jpe 返回图片资源
     */
    public function getQrcodeByTicket($ticket)
    {
        try {
            $res = $this->client->request('GET', "https://mp.weixin.qq.com/cgi-bin/showqrcode", ['query' => ['ticket' => urlencode($ticket)]]);
            $httpCode = $res->getStatusCode();
            if (200 != $httpCode)
                return ['errcode' => 1, 'errmsg' => 'http request error httpd_code:' . $httpCode];
            $res = $res->getBody()->getContents();
            if ($res) {
                header('Content-Type:image/jpg');
                exit($res);
            }
        }catch (\GuzzleHttp\Exception\ClientException $e){
            throw new \Exception('二维码不存在',404);
        }
    }

}