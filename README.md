# weiaccount
wechat  official account tool based on yii2 framework

# Install
composer require qmrp/weiaccount

# config
config\web.php
```
'components' => [
        'weixin' =>[
            'class' => 'qmrp\weiaccount\Weixin',
            'appId' => 'wx0147d*******',
            'secret' => '********',
            'token' => 'qmrp',
            'encodingAesKey' => '…………'
        ],
```

# use

```
use qmrp\weiaccount\Weixin;
header('content-type:aplication/xml;charset=utf8');
$replay = ['textReplay'=>[["keyword"=>"你好","replayType"=>"text","content"=>"您好！"],["keyword"=>"我的天","replayType"=>"image","mediaId"=>"0yOLS1Oba0kidD0QR92NZOEkc-2511wAbsEIFHX-6oCLtavb_qUH48rgKKQy098x"]],"eventReplay"=>[["eventType"=>"subscribe","replayType"=>"voice","mediaId"=>"sjJTkQ63M74zCoUJ4-Vg3IsduwG8JF21bzCzYNOVu7wjpaOjEIFtMY9BnqLABNtX","eventKey"=>["eventKey"=>"zhou","replayType"=>"text","content"=>"这里是特殊eventKey值的返回"]]]];
$weixin = \Yii::$app->weixin; \\or $weixin = new Weixin(['appId'=>"","secret"=>"","token"=>"","encodingAesKey"=>""]);
$autoReplay = new AutoReplayConfig($replay);
$xml = file_get_contents("php://input");
$res = $weixin->setRequestMsg($xml)->setAutoReplayConfig($autoReplay)->Response();
echo $res;
```
