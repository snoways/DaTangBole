<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/26 0026
 * Time: 上午 10:14
 */

namespace Parents\Controller;


use Client\Controller\UserApiBaseController;

class MessageController extends UserApiBaseController
{
    /**
     * 二期新增
     * 消息中心
     * sunfan
     * 2018.4.26
     */


    public function index()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $oauth_user_model=M('Message');
        $lists = $oauth_user_model
            ->where(['user_type'=>['in', [1, 2]], 'type'=>2])
            ->order("msg_time DESC")
            ->field('id as mid, content, msg_time')
            ->page($page)
            ->select();

        foreach ($lists as $k=>$list) {
            if (M('MsgDel')->where(['msg_id'=>$list['mid'], 'uid'=>$id, 'u_type'=>1])->find()){
                unset($lists[$k]);
            }
        }
        $lists = array_values($lists);

        if (!$lists){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $lists);
        }
    }

    public function del()
    {
        $data = $this->data;
        $id = S($data['token']);
        $mid = !empty($data['mid'])?$data['mid']:$this->ApiReturn(-1, '请选择要删除的消息');
        if (!M('MsgDel')->where(['msg_id'=>$mid, 'uid'=>$id, 'u_type'=>1])->find()){
            M('MsgDel')->add([
                'msg_id'=>$mid,
                'uid'=>$id,
                'u_type'=>1
            ]);
        }

        $this->ApiReturn(1, '删除成功');
    }

}