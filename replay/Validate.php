<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/5/27
 * Time: 3:55 PM
 */

namespace qmrp\weiaccount\replay;


use qmrp\weiaccount\Weixin;

class Validate
{
    public static function int(Weixin $weixin,...$arg)
    {
        if($weixin->msgType!='text')
            return false;
        if(!is_int((int)$weixin->content))
            return false;
        if(isset($arg[0])){
            if($weixin->content<$arg[0])
                return false;
        }
        if(isset($arg[1])){
            if($weixin->content>$arg[1])
                return false;
        }
        return true;
    }

    public static function string(Weixin $weixin,...$arg)
    {
        if($weixin->msgType!='text')
            return false;
        if(!is_string($weixin->content))
            return false;
        $len = strlen($weixin->content);
        if(isset($arg[0])){
            if($len<$arg[0])
                return false;
        }
        if(isset($arg[1])){
            if($len>$arg[1])
                return false;
        }
        return true;
    }

    public static function float(Weixin $weixin,...$arg)
    {
        if($weixin->msgType!='text')
            return false;
        if(!is_float((float)$weixin->content))
            return false;
        if(isset($arg[0])){
            if($weixin->content<$arg[0])
                return false;
        }
        if(isset($arg[1])){
            if($weixin->content>$arg[1])
                return false;
        }
        return true;
    }

    public static function date(Weixin $weixin,...$arg){
        if($weixin->msgType!='text')
            return false;
        $time = strtotime($weixin->content);
        if(!$time)
            return false;
        if(isset($arg[0])){
            $start = strtotime($arg[0]);
            if($time<$start)
                return false;
        }

        if(isset($arg[1])){
            $end = strtotime($arg[1]);
            if($time>$end)
                return false;
        }
        return true;
    }

    public static function image(Weixin $weixin)
    {
        if($weixin->msgType!='image')
            return false;
        return true;
    }

    public static function voice(Weixin $weixin)
    {
        if($weixin->msgType!='voice')
            return false;
        return true;
    }

    public static function video(Weixin $weixin)
    {
        if($weixin->msgType=='video'||$weixin->msgType=='shortvideo')
            return true;
        return false;
    }

    public static function link(Weixin $weixin)
    {
        if($weixin->msgType!='link')
            return false;
        return true;
    }

    public static function match(Weixin $weixin,...$arg)
    {
        if($weixin->msgType!='text'&&!isset($arg[0]))
            return false;
        if(!preg_match($arg[0],$weixin)){
            return false;
        }
        return true;
    }

    public static function phone(Weixin $weixin)
    {
        if($weixin->msgType=='text'){
            if(!preg_match("^(13[0-9]|14[5|7]|15[0|1|2|3|4|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9])\d{8}$",$weixin->content)){
                return false;
            }
            return true;
        }
        return false;
    }

    public static function email(Weixin $weixin)
    {
        if($weixin->msgType=='text'){
            if(!preg_match("^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$",$weixin->content)){
                return false;
            }
            return true;
        }
        return false;
    }

    public function range(Weixin $weixin,...$arg)
    {
        if($weixin->msgType!='text'&&!isset($arg[0]))
            return false;
        if(in_array($weixin->content,$arg))
            return true;
        return false;
    }
}