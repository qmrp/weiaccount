<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/5/25
 * Time: 2:27 PM
 */

namespace qmrp\weiaccount\media;

use qmrp\weiaccount\library\BaseTool;
use qmrp\weiaccount\exception\ResponseException;

class MediaTool extends BaseTool
{
    public $actMap = [
        'st_up' => '/cgi-bin/media/upload',
        'st_get' => '/cgi-bin/media/get',
        'fe_up' => '/cgi-bin/material/add_material',
        'fe_news' => '/cgi-bin/material/add_news',
        'news_img' => '/cgi-bin/media/uploadimg',
        'fe_get' => '/cgi-bin/material/get_material',
        'fe_del' => '/cgi-bin/material/del_material',
        'fe_ud' => '/cgi-bin/material/update_news',
        'fe_count' => '/cgi-bin/material/get_materialcount',
        'fe_list' => '/cgi-bin/material/batchget_material'
    ];

    public $typeList = ['image','voice','video','thumb'];

    /**
     * 新增临时素材
     * 图片（image）: 2M，支持PNG\JPEG\JPG\GIF格式
     * 语音（voice）：2M，播放长度不超过60s，支持AMR\MP3格式
     * 视频（video）：10MB，支持MP4格式
     * 缩略图（thumb）：64KB，支持JPG格式
     * @param $type 素材类型image/voice/video/thumb
     * @param $path 上传素材的文件路径
     */
    public function addShortTimeMedia($type,$path)
    {
        if(is_file($path)){
            $filesize = filesize($path);
            $pathinfo = pathinfo($path);
            $ext = strtolower($pathinfo['extension']);
            switch ($type){
                case 'image':
                    if($filesize>1024*1024*2){
                        throw new ResponseException(201,'file size too big');
                    }
                    if(!in_array($ext,['png','jpg','jpeg','gif'])){
                        throw new ResponseException(202,'file extension failed validate');
                    }
                    break;
                case 'voice':
                    if($filesize>1024*1024*2){
                        throw new ResponseException(201,'file size too big');
                    }
                    if(!in_array($ext,['amr','mp3'])){
                        throw new ResponseException(202,'file extension failed validate');
                    }
                    break;
                case 'video':
                    if($filesize>1024*1024*10){
                        throw new ResponseException(201,'file size too big');
                    }
                    if($ext!='mp4'){
                        throw new ResponseException(202,'file extension failed validate');
                    }
                    break;
                case 'thumb':
                    if($filesize>64*1024){
                        throw new ResponseException(201,'file size too big');
                    }
                    if($ext!='jpg'){
                        throw new ResponseException(202,'file extension failed validate');
                    }
                    break;
                default:
                    throw new ResponseException(203,'type not in media type');
                    break;
            }
            $data = [
                [
                    'name' => 'type',
                    'contents' => $type
                ],
                [
                    'name' => 'media',
                    'contents' => fopen($path,'r'),
                    'filename' => $pathinfo['basename']
                ]
            ];
            $res = $this->postRequest('st_up',$data,true);
            return $res;
        }else{
            return ['errcode'=>204,'msg' => 'path not file'];
        }
    }

    /**
     * 获取临时素材
     * @param string $mediaId
     * @return array|resource
     */
    public function getShortTimeMedia(string $mediaId)
    {
        return $this->getResources('st_get',['media_id'=>$mediaId]);
    }

    /**
     * 增加其它类型永久素材
     * @param $type
     * @param $path
     * @param $description 当type=video时必填
     * @return array
     * @throws ResponseException
     */
    public function addForeverMedia($type,$path,$description="")
    {
        if(is_file($path)){
            $filesize = filesize($path);
            $pathinfo = pathinfo($path);
            $ext = strtolower($pathinfo['extension']);
            switch ($type){
                case 'image':
                    if($filesize>1024*1024*2){
                        throw new ResponseException(201,'file size too big');
                    }
                    if(!in_array($ext,['png','jpg','jpeg','gif'])){
                        throw new ResponseException(202,'file format failed validate');
                    }
                    break;
                case 'voice':
                    if($filesize>1024*1024*2){
                        throw new ResponseException(201,'file size too big');
                    }
                    if(!in_array($ext,['amr','mp3'])){
                        throw new ResponseException(202,'file format failed validate');
                    }
                    break;
                case 'video':
                    if($filesize>1024*1024*10){
                        throw new ResponseException(201,'file size too big');
                    }
                    if($ext!='mp4'){
                        throw new ResponseException(202,'file format failed validate');
                    }
                    break;
                case 'thumb':
                    if($filesize>64*1024){
                        throw new ResponseException(201,'file size too big');
                    }
                    if($ext!='jpg'){
                        throw new ResponseException(202,'file format failed validate');
                    }
                    break;
                default:
                    throw new ResponseException(203,'type not in media type');
                    break;
            }
            $data = [
                [
                    'name' => 'type',
                    'contents' => $type
                ],
                [
                    'name' => 'media',
                    'contents' => fopen($path,'r'),
                    'filename' => $pathinfo['basename']
                ]
            ];
            if($type=='video'){
                if($description=="")
                    throw new ResponseException(205,'upload video must have field for description');
                $data[] = ['name'=>'description','contents'=>$description];
            }
            $res = $this->postRequest('fe_up',$data,true);
            return $res;
        }else{
            return ['errcode'=>204,'msg' => 'path not file'];
        }
    }

    /**
     * 新增永久图文素材
     * @param $articles array 是  图文素材
     * @param $title string	是	标题
     * @param $thumb_media_id string 是	图文消息的封面图片素材id（必须是永久mediaID）
     * @param $author string 否	作者
     * @param $digest string 否	图文消息的摘要，仅有单图文消息才有摘要，多图文此处为空。如果本字段为没有填写，则默认抓取正文前64个字。
     * @param $show_cover_pic int	是	是否显示封面，0为false，即不显示，1为true，即显示
     * @param $content string 是 图文消息的具体内容，支持HTML标签，必须少于2万字符，小于1M，且此处会去除JS,涉及图片url必须来源 "上传图文消息内的图片获取URL"接口获取。外部图片url将被过滤。
     * @param $content_source_url string 是	图文消息的原文地址，即点击“阅读原文”后的URL
     * @param $need_open_comment int 否	Uint32 是否打开评论，0不打开，1打开
     * @param $only_fans_can_comment int 否	Uint32 是否粉丝才可评论，0所有人可评论，1粉丝才可评论
     */
    public function addForeverNews($articles)
    {
        foreach ($articles as $item){
            if(!isset($item['title'])||!isset($item['thumb_media_id'])||!isset($item['show_cover_pic'])||!isset($item['content'])||
            !isset($item['content_source_url']))
                throw new ResponseException(301,'Missing required parameter');
        }
        $res = $this->postRequest('fe_news',$articles);
        return $res;
    }

    /**
     * 上传图文信息中的图片
     * @param $path
     * @return array
     * @throws ResponseException
     */
    public function upForeverImg($path)
    {
        if(is_file($path)){
            $filesize = filesize($path);
            if($filesize>1024*1024)
                throw new ResponseException(201,'file size too big');
            $pathinfo = pathinfo($filesize);
            if(!in_array($pathinfo['extension'],['jpg','png']))
                throw new ResponseException(202,'file format failed validate');
            $data = [
                [
                    'name' => 'media',
                    'contents' => fopen($path,'r'),
                    'filename' => $pathinfo['basename']
                ]
            ];

            $res = $this->postRequest('news_img',$data,true);
            return $res;
        }
        return ['errcode'=>204,'msg' => 'path not file'];
    }

    /**
     * 获取永久素材，图文及视频素材返回array,其余的类型返回素材资源
     * @param $mediaId
     * @return array|resource
     */
    public function getForeverMedia($mediaId)
    {
        return $this->getResources('fe_get',['media_id'=>$mediaId]);
    }

    /**
     * 删除永久素材
     * @param $mediaId string 是 素材ID
     * @return array
     */
    public function detForeverMedia($mediaId)
    {
        return $this->postRequest('fe_del',['media_id'=>$mediaId]);
    }

    /**
     * 修改图文信息
     * @param $mediaId string 是 素材ID
     * @param $index 多图文有意义，0为第一篇
     * @param $articles 图文内容，包含字段同新增图文信息
     */
    public function updateNews($mediaId,$index,$articles)
    {
        return $this->postRequest('fe_ud',['media_id'=>$mediaId,'index'=>$index,'articles'=>$articles]);
    }

    /**
     * 获取永久素材数量
     * @return array
     */
    public function getForeverCount()
    {
        return $this->getRequest('fe_count');
    }

    /**
     * 获取永久素材列表
     * @param $type
     * @param int $page
     * @param int $limit
     * @return array
     * @throws ResponseException
     */
    public function getForeverList($type,$page=1,$limit=10)
    {
        if(!in_array($type,['image','voice','video','news']))
            throw new ResponseException(203,'type not in media type');
        if($limit<=0)
            throw new ResponseException(302,'limit must between 1 and 20');
        $page = $page?(int)$page:1;
        $offset = ($page-1)*$limit;
        return $this->postRequest('fe_list',['type'=>$type,'offset'=>$offset,'count'=>$limit]);
    }
}