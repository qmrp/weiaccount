<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/5/22
 * Time: 5:37 PM
 */

namespace qmrp\weiaccount\exception;


use Throwable;

class ResponseException extends \Exception
{
    public function __construct($code = 0,$message = "", Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}