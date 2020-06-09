<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/5/27
 * Time: 2:52 PM
 */

namespace qmrp\weiaccount\replay;


use qmrp\weiaccount\exception\ResponseException;

class ActiveReplayConfig
{
    /**
     * @var ActiveConfigObj
     */
    public $replayConfig;

    public function __construct($replayConfig)
    {
        $this->setReplayConfig($replayConfig);
    }

    /**
     * @param mixed $replayConfig
     */
    public function setReplayConfig($replayConfig)
    {
        foreach ($replayConfig as $activeName => $replay){
            if(!isset($replay['formats']))
                throw new ResponseException(201,"ActiveReplayConfig {$activeName} formats missing");
            if(!isset($replay['replays']))
                throw new ResponseException(202,"ActiveReplayConfig {$activeName} replays missing");
            if(!isset($replay['finish']))
                throw new ResponseException(203,"ActiveReplayConfig {$activeName} finish missing");
            if(!isset($replay['quit']))
                throw new ResponseException(203,"ActiveReplayConfig {$activeName} quit missing");
            $this->replayConfig[$activeName] = new ActiveConfigObj($replay);
        }
    }

    public function isSetReplay($activeName)
    {
        return isset($this->replayConfig[$activeName]);
    }

    /**
     * @param $activeName
     * @param $step
     * @return bool|array
     */
    public function getReplay($activeName,$step)
    {
        return isset($this->replayConfig[$activeName])?$this->replayConfig[$activeName]->getReplay($step):false;
    }

    /**
     * @param $activeName
     * @return bool|array
     */
    public function getSteps($activeName)
    {
        return isset($this->replayConfig[$activeName])?$this->replayConfig[$activeName]->getSteps():false;
    }

    /**
     * @param $activeName
     * @param $step
     * @return bool|array
     */
    public function getFormat($activeName,$step)
    {
        return isset($this->replayConfig[$activeName])?$this->replayConfig[$activeName]->getFormat($step):false;
    }

    /**
     * @param $activeName
     * @return bool|array
     */
    public function getFinish($activeName)
    {
        return isset($this->replayConfig[$activeName])?$this->replayConfig[$activeName]->getFinish():false;
    }

    public function isQuit($activeName,$keyword)
    {
        return isset($this->replayConfig[$activeName])?$this->replayConfig[$activeName]->isQuit($keyword):false;
    }

    public function getQuit($activeName)
    {
        return isset($this->replayConfig[$activeName])?$this->replayConfig[$activeName]->getQuit():false;
    }
}