<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/6/1
 * Time: 4:30 PM
 */

namespace qmrp\weiaccount;


interface Validate
{
    public static function execute(Weixin $weixin,...$arg):bool;
}