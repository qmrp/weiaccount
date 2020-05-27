<?php
/**
 * Created by PhpStorm.
 * User: zhou
 * Date: 2020/5/27
 * Time: 11:14 AM
 */

namespace qmrp\weiaccount\member;

use qmrp\weiaccount\library\BaseTool;

class MemberTool extends BaseTool
{
    public $actMap = [
        'add_tag' => '/cgi-bin/tags/create',
        'get_tags' => '/cgi-bin/tags/get',
        'ud_tag' => '/cgi-bin/tags/update',
        'del_tag' => '/cgi-bin/tags/delete',
        'get_users_tag' => '/cgi-bin/user/tag/get',
        'set_users_tag' => '/cgi-bin/tags/members/batchtagging',
        'cancel_users_tag' => '/cgi-bin/tags/members/batchuntagging',
        'get_user_tagid' => '/cgi-bin/tags/getidlist',
        'set_user_alias' => '/cgi-bin/user/info/updateremark',
        'get_user_info' => '/cgi-bin/user/info',
        'get_users_info' => '/cgi-bin/user/info/batchget',
        'get_users' => '/cgi-bin/user/get',
        'get_black_usrs' => '/cgi-bin/tags/members/getblacklist'
    ];

    /**
     * 创建标签
     * @param $tagName
     * @return array
     */
    public function addTag($tagName)
    {
        return $this->postRequest('add_tag',['tag'=>['name'=>$tagName]]);
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->getRequest('get_tags');
    }

    /**
     * @param $tagId
     * @param $tagName
     * @return array
     */
    public function updateTag($tagId,$tagName)
    {
        return $this->postRequest('ud_tag',['tag'=>['id'=>$tagId,'name'=>$tagName]]);
    }

    /**
     * @param $tagId
     * @return array
     */
    public function delTag($tagId)
    {
        return $this->postRequest('ud_tag',['tag'=>['id'=>$tagId]]);
    }

    /**
     * @param $tagId
     * @param string $nextOpenId
     * @return array
     */
    public function getUsersByTags(int $tagId,string $nextOpenId="")
    {
        $data = ['tagid'=>$tagId];
        if($nextOpenId!=""){
            $data['next_openid'] = $nextOpenId;
        }
        return $this->postRequest('get_users_tag',$data);
    }

    /**
     * @param int $tagId
     * @param array $openIds
     * @return array
     */
    public function setUserTagInBatch(int $tagId,array $openIds)
    {
        return $this->postRequest('set_users_tag',['tagid'=>$tagId,'openid_list'=>$openIds]);
    }

    /**
     * @param int $tagId
     * @param array $openIds
     * @return array
     */
    public function cancelUserTagInBatch(int $tagId,array $openIds)
    {
        return $this->postRequest('cancel_users_tag',['tagid'=>$tagId,'openid_list'=>$openIds]);
    }

    /**
     * @param string $openId
     * @return array
     */
    public function getUserTagIds(string $openId)
    {
        return $this->postRequest('get_user_tagid',['openid'=>$openId]);
    }

    /**
     * @param string $openId
     * @param string $aliasName
     * @return array
     */
    public function setUserAlias(string $openId,string $aliasName)
    {
        return $this->postRequest('set_user_alias',['openid'=>$openId,'remark'=>$aliasName]);
    }

    /**
     * @param string $openId
     * @param string $lang
     * @return array
     */
    public function getUserInfo(string $openId,string $lang="zh_CN")
    {
        return $this->getRequest('get_user_info',['openid'=>$openId,'lang'=>$lang]);
    }

    /**
     * @param array $openIds
     * @param string $lang
     * @return array
     */
    public function getUserInfoInBatch(array $openIds,string $lang="zh_CN")
    {
        $data = ["user_list"=>[]];
        foreach ($openIds as $openId){
            $data["user_list"][] = ['openid'=>$openId,'lang'=>$lang];
        }
        return $this->postRequest('get_users_info',$data);
    }

    /**
     * @param string $nextOpenId
     * @return array
     */
    public function getAccountUsers($nextOpenId="")
    {
        if($nextOpenId!="")
            return $this->getRequest('get_users',['next_openid'=>$nextOpenId]);
        return $this->getRequest('get_users');
    }

    /**
     * @param string $nextOpenId
     * @return array
     */
    public function getAccountBlackUsers($nextOpenId="")
    {
        if($nextOpenId!="")
            return $this->getRequest('get_black_usrs',['next_openid'=>$nextOpenId]);
        return $this->getRequest('get_black_usrs');
    }

}