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
$replay =
        [
                'msgReplay'=>[
                    ["keyword"=>"你好","replayType"=>"text","content"=>"您好！"],
                    ["keyword"=>'xd',"replayType"=>"callback","function"=>['app\common\common','excute']],
                    ["keyword"=>"我的天","replayType"=>"image","mediaId"=>"M3SVG9PH07TjgXPT3GxK2O7nuVQwDpiZH_2sl9-nfct0R2Te4Uw1EuYSVly-i7Sz"],
                    ["keyword"=>'模板',"replayType"=>"template","tempId"=>"CV1vGj_tseSAAcoXiKMvyML9TnrwLY5LEiUmqvtP3Rk","params"=>['first'=>['value'=>'哈'],'orderID'=>['value'=>'123'],'orderMoneySum'=>['value'=>'12.5'],'backupFieldName'=>['value'=>'可以'],'backupFieldData'=>['value'=>'好'],'remark'=>['value'=>'备注']]],
                ],
                "eventReplay"=>[
                    [
                        "eventType"=>"subscribe",
                        "replayType"=>"image",
                        "mediaId"=>"sjJTkQ63M74zCoUJ4-Vg3IsduwG8JF21bzCzYNOVu7wjpaOjEIFtMY9BnqLABNtX",
                        "eventKey"=>[
                            [
                                "eventKey"=>"qrscene_zhou",
                                "replayType"=>"text",
                                "content"=>"这里是特殊eventKey值的返回"
                            ]
                        ]
                    ],
                    [
                        "eventType"=>"CLICK",
                        "eventKey"=>[
                            [
                                "eventKey"=>"zhou",
                                "replayType"=>"text",
                                "content"=>"这里是特殊eventKey值的返回"
                            ],
                            [
                                "eventKey"=>"V1001_GOOD",
                                "replayType"=>"text",
                                "content"=>"感谢你的点赞"
                            ]
                        ]
                    ]
                ]
        ];


//后面直接用$weixin,不在做实例化;
$weixin = \Yii::$app->weixin; //或者直接实例化传参 $weixin = new Weixin(['appId'=>"","secret"=>"","token"=>"","encodingAesKey"=>""]);
$autoReplay = new AutoReplayConfig($replay);
$xml = file_get_contents("php://input");
$res = $weixin->setRequestMsg($xml)->setAutoReplayConfig($autoReplay)->Response();
echo $res;
```

>被动回复参数说明

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

### 上下文持续对话系统

通过ActiveReplayConfig类设置，收集的信息可以通过继承Callbackfun接口来处理并返回最终数据；

试例：
```
$activeReplay = new ActiveReplayConfig($activeConfig);
$activeConfig = [
                'order' => [
                    'replays' => [
                        [
                            "replay"=>["replayType"=>"text","content"=>"开始下单流程,中途若想退出流程,回复退出即可.\r\n1,请输入派单价格单位为元,此价为总价格"],
                            "name" => "feeGoods",
                            "callback" => ["app\common\common","money"],
                            "message" => "请输入标准价格"
                        ],
                        [
                            "replay"=>["replayType"=>"text","content"=>"2,请上传商品图片，直接发送图片即可"],
                            "name" => "goodsImage",
                            "callback" => ["app\common\common","image"],
                            "message" => "请回复图片"
                        ],
                        [
                            "replay"=>["replayType"=>"text","content"=>"3,请输入商品数量"],
                            "name" => "itemQty",
                            "callback" => ["app\common\common","nums"],
                            "message" => "请回复整数"
                        ]
                    ],
                    'formats' => [
                        [
                            'validate' => 'sys',
                            'rule' => ['float',0,999]
                        ],
                        [
                            'validate' => 'sys',
                            'rule' => ['image']
                        ],
                        [
                            'validate' => 'sys',
                            'rule' => ['int']
                        ]
                    ],
                    'finish' => ["app\common\common","finish"]
                ]
            ];
$xml = file_get_contents("php://input");
$res = $weixin->setRequestMsg($xml)->setActiveReplayConfig($activeReplay)->Response();
exit($res);
```
|参数名|类型|必须|说明|
|---|---|---|---|
|order|string|是|上下文持续对话命名|
|replays|array|是|上下文步骤详情|
|replay|array|是|当前步骤回复内容，格式同被动回复说明|
|name|string|是|当前步骤用户回复内容存储key值|
|callback|array|是|当前步骤用户回复内容处理方法|
|message|string|否|当前步骤用户回复格式检验错误时，返回内容|
|formats|array|是|用户所有步骤回复内容的校验规则|
|validate|string|是|sys表示系统自动的校验方法,self表示自定义校验方法|
|rule|array|是|校验方法sys自带int,float,string,date,image,voice,video,link,phone,email,match,range;自定义检义必须继承接口qmrp\weiaccount\Validate|
|finish|array|是|当前上下文完成片时方法|


### Weixin 方法说明

|方法名|返回|说明|
|---|---|---|
|setRequestMsg|Weixin|将接收到的信息解析|
|setAutoReplayConfig|Weixin|设置自动回复规则|
|setActiveReplayConfig|Weixin|设置上下文对话规则|
|Response|xml|回复信息|
|getRequest|array|获取当前请求信息|
|setActive|bool|进入上下文交互模式|
|getActive|array|获取当前上下文交互信息|
|nextStep|bool|上下文进入下一步|
|quitAcitve|bool|退出上下文交互模式|
|setActiveContent|bool|存储当前交互数据|
|getActiveContent|array|获取当前交互用户返回并处理后的数据|
|getMenu|array|获取微信公众号的菜单配置|
|delMenu|bool|array|删除微信公众号的菜单,开启debug时，未成功返回错误信息|
|createMenu|bool|array|创建微信公众号菜单|
|getTempList|array|返回当前微信公众号内的模版信息列表|
|sendTempMsg|array|发送模版信息|

### QrcodeTool 方法说明

|方法名|返回|说明|
|---|---|---|
|createQr|array|创建带参数二维码|
|getQrcodeByTicket|souce|根据二维码的ticket返回二维码图片资源|

### MediaTool 方法说明

|方法名|返回|说明|
|---|---|---|
|addShortTimeMedia|array|增加临时素材|
|getShortTimeMedia|array|获取临时素材|
