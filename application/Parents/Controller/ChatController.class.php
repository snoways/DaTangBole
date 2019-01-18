<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/12/5
 * Time: 10:16
 */

namespace Parents\Controller;


use Client\Controller\UserApiBaseController;
require('./application/Common/common/ServerAPI.php');

class ChatController extends UserApiBaseController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->AppKey = C('NetEaseConf.APPID');
        $this->AppSecret = C('NetEaseConf.APPSECRET');
    }
    /**
     * 获取云信token
     * sunfan
     * 2017.12.5
     */
    public function getNetEaseToken()
    {
        $data = $this->data;
        $id = S($data['token']);
        $type = $data['type'];  //1.家长 2.老师 3.托管、教育机构

        $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库

        if ($type==1)
        {
            //家长
            $userinfo = M('Parents')->where(array('id'=>$id))->find();
            $accid = "p".$id;
            $name = $userinfo['parent_name'];
            $img = C('WEBHOST').$userinfo['p_img'];
        }elseif ($type==2){
            //老师
            $userinfo = M('Teacher')->where(array('id'=>$id))->find();
            $accid = "t".$id;
            $name = $userinfo['name'];
            $img = C('WEBHOST').$userinfo['t_img'];
        }elseif ($type==3){
            //托管、教育机构
            $userinfo = M('SmallTable')->where(array('id'=>$id))->find();
            $accid = "s".$id;
            $name = $userinfo['s_name'];
            $img = C('WEBHOST').$userinfo['s_img'];
        }

        //更新用户名片
        $p->updateUinfo($accid, $name , $img);

        $result = $p->updateUserToken($accid);
        if ($result['code']==200)
        {
            $this->ApiReturn(1, '成功', $result['info']);
        }
        elseif($result['desc']==$accid." not register"){
            //注册网易云账号
            $result = $p->createUserId($accid, $name);
            $this->ApiReturn(1, '成功', $result['info']);
        }else{
            $this->ApiReturn(-1, '失败', $result['desc']);
        }
    }

    //更新用户名片
    public function updateUser()
    {
        $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
        $result = $p->updateUinfo("t34", "赵老师" , "http://39.104.28.127/data/upload/stamp/531512637833.png");
    }

    //获取用户名片
    public function getUinfos()
    {
        $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
        $result = $p->getUinfos(["p2"]);
        dump($result);
    }

    //聊天记录
    public function chatMsg()
    {
        $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
        $result = $p->querySessionMsg("t34", "p24", "1512639110000", "1513727024000", 100, 2);
        dump($result);
    }

}