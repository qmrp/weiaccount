<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/4/30
 * Time: 4:04 PM
 */

namespace qmrp\weiaccount\replay;


use qmrp\weiaccount\exception\ResponseException;

class AutoReplayConfig
{
    private $msgReplay = array();

    private $eventReplay = array();

    private $replayMsgTypeList = ['text','image','voice','video','music','news'];

    private $eventType = ['subscribe','unsubscribe','SCAN','LOCATION','CLICK,VIEW'];

    /*
     * @params $options ['textReplay'=>[["keyword"=>"你好","replayType"=>"text","content"=>"您好！"],["keyword"=>"我的天","replayType"=>"image","mediaId"=>"0yOLS1Oba0kidD0QR92NZOEkc-2511wAbsEIFHX-6oCLtavb_qUH48rgKKQy098x"]],"eventReplay"=>[["eventType"=>"subscribe","replayType"=>"voice","mediaId"=>"sjJTkQ63M74zCoUJ4-Vg3IsduwG8JF21bzCzYNOVu7wjpaOjEIFtMY9BnqLABNtX","eventKey"=>["eventKey"=>"zhou","replayType"=>"text","content"=>"这里是特殊eventKey值的返回"]]]]
     */
    public function __construct(array $options)
    {
        if(isset($options['msgReplay']))
            $this->setMsgReplay($options['msgReplay']);

        if(isset($options['eventReplay']))
            $this->setEventReplay($options['eventReplay']);

    }

    /**
     * @return mixed
     */
    public function getMsgReplay()
    {
        return $this->msgReplay;
    }

    /**
     * @return mixed
     */
    public function getEventReplay()
    {
        return $this->eventReplay;
    }

    public function setMsgReplay(array $replays)
    {
        if(empty($replays))
            return false;
        $config = [];
        foreach ($replays as $replay){
            if(!isset($replay['keyword'])||!isset($replay['replayType']))
                throw new ResponseException(101,"AutoReplayConfig missing params");

            if(in_array($replay['replayType'],$this->replayMsgTypeList)&&!Template::checkTempParams($replay,$replay['replayType']))
                throw new ResponseException(102,"AutoReplayConfig replay {$replay['keyword']} temp prams missing");
            elseif($replay['replayType']=='callback'){
                if(!isset($replay['function'])) {
                    throw new ResponseException(103, 'AutoReplayConfig set callback missing param function');
                }elseif (!isset($replay['function'][0])||!isset($replay['function'][1])){
                    throw new ResponseException(104,'AutoReplayConfig callback function missing params');
                }
                elseif(!method_exists($replay['function'][0],$replay['function'][1])){
                    throw new ResponseException(103,"AutoReplayConfig callback function {$replay['function'][1]} not exists");
                }
            }
            $config[$replay['keyword']] = $replay;
        }
        $this->msgReplay = $config;
    }

    public function setEventReplay(array $replays)
    {
        if(empty($replays))
            return false;
        $config = [];
        foreach ($replays as $replay) {
            if(!isset($replay['eventType']))
                throw new ResponseException(101,"AutoReplayConfig missing eventType");

            if(isset($replay['eventKey'])&&is_array($replay['eventKey'])&&!empty($replay['eventKey'])){
                $temp = [];
                foreach ($replay['eventKey'] as $event){
                    if(!isset($event['eventKey'])||!isset($event['replayType']))
                        throw new ResponseException(101,"AutoReplayConfig missing eventKey params");
                    if(!Template::checkTempParams($event,$event['replayType']))
                        throw new ResponseException(102,"AutoReplayConfig eventKey replay temp prams missing");
                    $temp[$event['eventKey']] = $event;
                }
                $replay['eventKey'] = $temp;
            }elseif(isset($replay['replayType'])) {

                if (!Template::checkTempParams($replay, $replay['replayType']))
                    throw new ResponseException(102, "AutoReplayConfig replay temp prams missing");
            }else{
                throw new ResponseException(101,"AutoReplayConfig missing replay config");
            }
            $config[$replay['eventType']] = $replay;
        }
        $this->eventReplay = $config;
    }

    public function hitContentReplay($keyword)
    {
        return isset($this->msgReplay[$keyword])?$this->msgReplay[$keyword]:false;
    }

    public function hitEventReplay($event,$eventKey)
    {
        if(!empty($event)&&!empty($eventKey)){
            return isset($this->eventReplay[$event]['eventKey'][$eventKey])?$this->eventReplay[$event]['eventKey'][$eventKey]:false;
        }elseif(!empty($event)){
            return isset($this->eventReplay[$event])?$this->eventReplay[$event]:false;
        }
    }
}