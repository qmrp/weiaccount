<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/4/30
 * Time: 4:04 PM
 */

namespace qmrp\weiaccount\replay;


class AutoReplayConfig
{
    private $textReplay;

    private $voiceReplay;

    private $videoReplay;

    private $musicReplay;

    private $eventReplay;

    private $replayMsgTypeList = ['text','image','voice','video','music','news'];

    private $eventType = ['subscribe','unsubscribe','SCAN','LOCATION','CLICK,VIEW'];

    /*
     * @params $options ['textReplay'=>[["keyword"=>"你好","replayType"=>"text","content"=>"您好！"],["keyword"=>"我的天","replayType"=>"image","mediaId"=>"0yOLS1Oba0kidD0QR92NZOEkc-2511wAbsEIFHX-6oCLtavb_qUH48rgKKQy098x"]],"eventReplay"=>[["eventType"=>"subscribe","replayType"=>"voice","mediaId"=>"sjJTkQ63M74zCoUJ4-Vg3IsduwG8JF21bzCzYNOVu7wjpaOjEIFtMY9BnqLABNtX","eventKey"=>["eventKey"=>"zhou","replayType"=>"text","content"=>"这里是特殊eventKey值的返回"]]]]
     */
    public function __construct(array $options)
    {
        foreach ($options as $key => $item) {
            $this->$key = $item;
        }

    }

    public function hitContentReplay($keyword)
    {
        if(is_null($this->textReplay))
            return false;
        $textReplay = array_column($this->textReplay,null,'keyword');
        if(isset($textReplay[$keyword]))
            return $textReplay[$keyword];
        return false;
    }

    public function hitEventReplay($event,$eventKey)
    {
        if(is_null($this->eventReplay))
            return false;
        $eventReplay = array_column($this->eventReplay,null,'eventType');
        if(isset($eventReplay[$event])){
            $eventReplay = $eventReplay[$event];
            if(empty($eventKey)) {
                return $eventReplay;
            }else{
                $eventKeyReplay = $eventReplay['eventKey'];
                $eventKeyReplay = array_column($eventKeyReplay,null,'eventKey');
                if(isset($eventKeyReplay[$eventKey]))
                    return $eventKeyReplay[$eventKey];
            }
        }
        return false;
    }
}