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

#### 自动回复

```
use qmrp\weiaccount\Weixin;
header('content-type:aplication/xml;charset=utf8');
//自动回复参数格式
$replay = [
    'textReplay'=>[
        ["keyword"=>"你好","replayType"=>"text","content"=>"您好！"],
        ["keyword"=>"我的天","replayType"=>"image","mediaId"=>"0yOLS1Oba0kidD0QR92NZOEkc-2511wAbsEIFHX-6oCLtavb_qUH48rgKKQy098x"]
    ],
    "eventReplay"=>[
        ["eventType"=>"subscribe","replayType"=>"voice","mediaId"=>"sjJTkQ63M74zCoUJ4-Vg3IsduwG8JF21bzCzYNOVu7wjpaOjEIFtMY9BnqLABNtX","eventKey"=>["eventKey"=>"zhou","replayType"=>"text","content"=>"这里是特殊eventKey值的返回"]]
    ]
];



$weixin = \Yii::$app->weixin; //或者直接实例化传参 $weixin = new Weixin(['appId'=>"","secret"=>"","token"=>"","encodingAesKey"=>""]);
$autoReplay = new AutoReplayConfig($replay);
$xml = file_get_contents("php://input");
$res = $weixin->setRequestMsg($xml)->setAutoReplayConfig($autoReplay)->Response();
echo $res;
```

>自动回复参数说明

|参数名|类型|说明|
|----|----|----|
|textReplay,eventReplay|string|文字信息回复,事件回复,目前支持文字、图片、事件、音频、视频信息的自动回复|
|keyword|string|文字回复的关键词|
|replayType|enum|回复的类型,text,voice,image,video,根据回复类型的不同，后面的参数不同|
|content|string|text回复类型的参数，回复内容|
|mediaId|string|voice,image,video等素材回复的素材id|
|title|string|video,news,music等的标题|
|url|string|图文信息的链接|
|picUrl|string|图文信息的图片链接|
|description|string|描述内容|
|musicUrl|string|music回复的音乐链接|
|hqMusicUrl|string|music回复的高清音乐链接|
|thumbMediaId|string|music回复的缩略图|

-----

#### 自定义公众号菜单

```
use qmrp\weiaccount\Weixin;

$weixin = new Weixin($config);

$menuConfig = $weixin->get

```