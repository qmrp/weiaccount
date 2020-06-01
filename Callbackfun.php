<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/5/22
 * Time: 4:44 PM
 */

namespace qmrp\weiaccount;

use qmrp\weiaccount\exception\ResponseException;
use qmrp\weiaccount\Weixin;

interface Callbackfun
{
    const RES_TEXT = 'text';
    const RES_IMG = 'image';
    const RES_VOICE = 'voice';
    const RES_VIDEO = 'video';
    const RES_MUSIC = 'music';
    const RES_NEWS = 'news';

    /**
     * 自动回复，回调函数，在设置自复中如设置replayType的值为callback时，将调自动调设置的function
     * @param $openId
     * @throws ResponseException
     * @return array 函数的返回格式必须按指定格式返回，不同的replayType有不同格式["replayType"=>self::RES_TEXT,"content">"返回"]
     */
    public static function excute(Weixin $weixin):array ;

    /**
     * 上下文完成调用函数
     * @return array 函数的返回格式必须按指定格式返回，不同的replayType有不同格式["replayType"=>self::RES_TEXT,"content">"返回"]
     */
    public static function finish(Weixin $weixin);

}