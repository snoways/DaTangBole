<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/28 0028
 * Time: 下午 02:21
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;
require('./application/Common/common/JPushSF.php');

class MessageController extends AdminbaseController
{
    public function index()
    {
        $where=array();
        if (I('keyword')){
            $where['content'] = ['like', "%".I('keyword')."%"];
        }
        if (I('user_type')){
            $where['user_type'] = I('user_type');
        }
        if (I('type')){
            $where['type'] = I('type');
        }
        $oauth_user_model=M('Message');
        $count=$oauth_user_model
            ->where($where)
            ->count();
        $page = $this->page($count, 15);
        $lists = $oauth_user_model
            ->where($where)
            ->order("msg_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $this->assign("page", $page->show('Admin'));
        $this->assign('lists', $lists);
        $this->display();
    }

    /**
     * 添加消息
     */
    public function add()
    {
        if(IS_POST)
        {
            if ($_POST['type']==1){
                //推送
                if (I('post.user_type')==1){
                    $receive = 'all';//全部
                    $push = new \JPushSF(C('JPush.PAPPID'),C('JPush.PAPPSECRET'));
                    $push->push($receive, '', 0, $_POST['content']);
                    $push = new \JPushSF(C('JPush.TAPPID'),C('JPush.TAPPSECRET'));
                    $push->push($receive, '', 0, $_POST['content']);
                    $push = new \JPushSF(C('JPush.SAPPID'),C('JPush.SAPPSECRET'));
                    $push->push($receive, '', 0, $_POST['content']);
                }elseif (I('post.user_type')==2){
                    $receive = 'all';//家长
                    $push = new \JPushSF(C('JPush.PAPPID'),C('JPush.PAPPSECRET'));
                    $push->push($receive, '', 0, $_POST['content']);
                }elseif (I('post.user_type')==3){
                    $receive = 'all';//老师
                    $push = new \JPushSF(C('JPush.TAPPID'),C('JPush.TAPPSECRET'));
                    $push->push($receive, '', 0, $_POST['content']);
                }else{
                    //教育机构
                    $receive = 'all';
                    $push = new \JPushSF(C('JPush.SAPPID'),C('JPush.SAPPSECRET'));
                    $push->push($receive, '', 0, $_POST['content']);
                }
            }
            $map['user_type'] = $_POST['user_type'];
            $map['type'] = $_POST['type'];
            $map['content'] = $_POST['content'];
            $map['msg_time'] = date('Y-m-d H:i:s');
            M('Message')->add($map);
            $this->success('消息发送成功！');
        }
        $this->display();
    }

    /**
     * 删除
     * sunfan
     * 2018.10.17
     */
    public function del()
    {
        $id = I('get.id');
        M('Message')->where(['id'=>$id])->delete();
        $this->success("删除成功！", U("Message/index"));
    }

}