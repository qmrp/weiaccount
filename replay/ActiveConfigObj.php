<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/5/27
 * Time: 3:17 PM
 */

namespace qmrp\weiaccount\replay;


use qmrp\weiaccount\Callbackfun;
use qmrp\weiaccount\exception\ResponseException;

class ActiveConfigObj
{
    /**
     * @var int
     */
    public $steps;

    /**
     * @var array
     */
    public $formats = array();

    /**
     * @var array
     */
    public $replays = array();

    /**
     * @var array
     */
    public $finish = ['qmrp\weiaccount\Callbackfun','execute'];

    public $message = "format validate error";

    public function __construct(array $config)
    {
        if(count($config['replays'])!=count($config['formats']))
            throw new ResponseException(104,"ActiveConfigObj replays length not eq formats");
        $this->setFinish($config['finish']);
        $this->setFormats($config['formats']);
        $this->setReplays($config['replays']);
        $this->setSteps(count($config['replays']));
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @param array $replays
     */
    public function setReplays($replays)
    {
        foreach ($replays as &$replay) {
            if(!isset($replay['replay'])||!isset($replay['name'])||!isset($replay['callback']))
                throw new ResponseException(101,'ActiveConfigObj setReplays missing params');
            if(!isset($replay['message']))
                $replay['message'] = $this->message;
            if(!Template::checkTempParams($replay['replay'],$replay['replay']['replayType'])){
                throw new ResponseException(102,'ActiveConfigObj replay Temp params error');
            }
            if(!method_exists($replay['callback'][0],$replay['callback'][1])){
                throw new ResponseException(103,"ActiveConfigObj callback function {$replay['callback'][1]} not exists");
            }
        }
        $this->replays = $replays;
    }

    /**
     * @return array
     */
    public function getReplays()
    {
        return $this->replays;
    }

    /**
     * @return array
     */
    public function getReplay($step)
    {
        return isset($this->replays[$step])?$this->replays[$step]:[];
    }

    /**
     * @param array $formats
     */
    public function setFormats($formats)
    {
        foreach ($formats as $format) {
            if(!isset($format['validate'])||!isset($format['rule']))
                throw new ResponseException(101,'ActiveConfigObj setFormats missing params');
            if($format['validate']=='sys'){
                if(!method_exists(Validate::class,$format['rule'][0]))
                    throw new ResponseException(103,'ActiveConfigObj setFormats sys validate rule function not exists');
            }else{
                if(!method_exists($format['rule'][0],$format['rule'][1])){
                    throw new ResponseException(103,'ActiveConfigObj setFormats custom validate rule function not exists');
                }
            }
        }
        $this->formats = $formats;
    }

    public function getFormat($step)
    {
        return isset($this->formats[$step])?$this->formats[$step]:[];
    }

    /**
     * @return array
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * @param int $steps
     */
    public function setSteps($steps)
    {
        $this->steps = $steps;
    }

    /**
     * @return int
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @param array $finish
     */
    public function setFinish($finish)
    {
        $this->finish = $finish;
    }

    /**
     * @return array
     */
    public function getFinish()
    {
        return $this->finish;
    }

}