<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/6
 * Time: 15:42
 */
namespace Teacher\Controller;

use Client\Controller\UserApiBaseController;
use JPush\Client;
require('./application/Common/common/JPushSF.php');

class CourseController extends UserApiBaseController
{
    //我的课程
    public function course()
    {
        $data = $this->data;
        $id = S($data['token']);
//        dump($id);
        $page = $data['page']??0;
        $now = M('Order')->where(array('teacher_id'=>$id,'status'=>array('in',('-1,1,2'))))->count();   //进行中个数
        $after = M('Order')->where(array('teacher_id'=>$id,'status'=>5))->count();   //售后个数
        if($data['status'] == ""){
            $where['o.status'] = array('in',('-1,1,2'));
        }else{
            $where['o.status'] = $data['status'];
        }
        $where['o.teacher_id'] = $id;
        $list = M('Order')->alias('o')
            ->field('o.id,o.sn,o.parent_id,o.class_num,o.now_num,o.teacher_id,o.status,o.total_money,o.subject,o.o_price,o.teach_date,o.teach_time,p.child_name,p.p_img,p.child_sex')
            ->join('left join fzm_parents p on o.parent_id=p.id')
            ->limit($page*10,10)
            ->order('o.start_time desc')
            ->where($where)->select();
        if($data['status'] == 5){
            foreach($list as $k=>$v){
                $find = M('Application')->where(array('oid'=>$v['id']))->find();
                $list[$k]['order_status'] = $find['status'];
            }
        }
        /*foreach($list as $k=>$v){
              if($v['status'] == 3){
                  $list[$k]['now_num'] = 0;
              }
        }*/
        $return['list'] = $list;
        $return['now'] = $now;
        $return['after'] = $after;
        $this->ApiReturn(1,'成功',$return);
    }

    //接单
    public function receive()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['id'] ==""){
            $this->ApiReturn(-1, '订单id不存在');
        }
        $save = M('Order')->where(array('id'=>$data['id']))->save(array('status'=>1));
//        $save = M('Order')->where(array('id'=>$data['id']))->save(array('status'=>2));
        if(!$save){
            $this->ApiReturn(-1,'接单失败');
        }
        $order = M('Order')->where(array('id'=>$data['id']))->find();

        //给家长推送
        $push = new \JPushSF(C('JPush.PAPPID'),C('JPush.PAPPSECRET'));
        $receive = array('alias'=>['parent'.$order['parent_id']]);//别名
        $result = $push->push($receive,2, $data['id']);

        //给家长发短信
        $parent_phone = M('Parents')->where(['id'=>$order['parent_id']])->getField('phone');
        send_message($parent_phone,'SMS_126782098');

        $this->ApiReturn(1,'接单成功');
    }

    //课程详情

    public function detail()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['id'] ==""){
            $this->ApiReturn(-1, '订单id不存在');
        }
        $info = M('Order')->alias('o')
            ->field('o.id,o.sn,o.parent_id,o.class_num,o.now_num,o.teacher_id,o.status,o.total_money,o.subject,o.o_price,o.teach_date,o.teach_time,p.child_name,p.phone,o.address,p.p_img,p.child_sex,p.p_xy,o.address_other')
            ->join('left join fzm_parents p on o.parent_id=p.id')
            ->where(array('o.id'=>$data['id']))->find();
        $find = M('OrderComments')->where(array('order_id'=>$data['id'],'num'=>$info['now_num']))->find();
        if($find){
            if($find['lesson_plan'] == "" && $find['comments'] == ""){
                $info['check'] = 3; //家长确认 未写评语
            }else{
                $info['check'] = 1;  //家长确认 写了评语
            }
        }else{
            $info['check'] = 2;   //未确认
        }
        $status = M('Application')->where(array('oid'=>$data['id']))->find();
        if($status){
            $info['order_status'] = $status['status'];
            $info['t_reason'] = $status['t_reason'];
            $info['reason'] = $status['reason'];
        }
        /*if($info['status'] == 3){
            $info['now_num'] = 0;
        }*/
        if(!$info){
            $this->ApiReturn(-1,'获取详情失败');
        }

        $info['accid'] = "p".$info['parent_id'];

        $info['comment'] = M('Assess')->where(['order_id'=>$data['id'], 'type'=>1])->field('content, star, img1, img2, img3, a_time')->find();
        $this->ApiReturn(1,'获取详情成功',$info);
    }

    //获取授课评语
    public function remark()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['id'] ==""){
            $this->ApiReturn(-1, '订单id不存在');
        }
        if($data['num'] ==""){
            $this->ApiReturn(-1, '第几节课num不存在');
        }
        $info = M('OrderComments')->where(array('order_id'=>$data['id'],'num'=>$data['num']))->find();
        if(!$info){
            $this->ApiReturn(0,'没有获取授课评语');
        }
        $this->ApiReturn(1,'获取授课评语成功',$info);
    }

    //授课评语提交
    public function reder()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['id'] ==""){
            $this->ApiReturn(-1, '订单id不存在');
        }
        if($data['num'] ==""){
            $this->ApiReturn(-1, '第几节课num不存在');
        }
        if($data['plan'] ==""){
            $this->ApiReturn(-1, '教案不存在');
        }
        if($data['comment'] ==""){
            $this->ApiReturn(-1, '评语不存在');
        }
        $save = M('OrderComments')->where(array('order_id'=>$data['id'],'num'=>$data['num']))->save(array('lesson_plan'=>$data['plan'],'comments'=>$data['comment'],'status'=>2));
        $find = M('Order')->where(array('id'=>$data['id']))->find();
        if($find['class_num'] == $data['num']){
            M('Order')
                ->where(['id'=>$data['id']])
                ->setInc('status');
            M('Order')
                ->where(['id'=>$data['id']])
                ->save(array('end_time'=>date('Y-m-d H:i:s')));
        }
        if($find['class_num'] >$data['num']){
            M('Order')
                ->where(['id'=>$data['id']])
                ->setInc('now_num');
        }
        $status = M('Order')->where(array('id'=>$data['id']))->find();
//        if($status['status'] == 3){

            $teacher = M('Teacher')->where(array('id'=>$status['teacher_id']))->find();
            $total = $status['money']/$status['class_num'] + $teacher['balance'];
            M('Teacher')->where(array('id'=>$status['teacher_id']))->save(array('balance'=>$total));

            //给老师推送
            $push = new \JPushSF(C('JPush.TAPPID'),C('JPush.TAPPSECRET'));
            $receive = array('alias'=>['teacher'.$id]);//别名
            $result = $push->push($receive,3);

            //给老师发短信
            send_message($teacher['phone'],'SMS_126876846');

//        }
        if(!$save){
            $this->ApiReturn(-1,'提交授课评语失败');
        }
        $this->ApiReturn(1,'提交授课评语成功');
    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////
    /// 新增接口
    /**
     *  我的分享
    */
    public function inviteCode()
    {
        $data = $this->data;
        $id = S($data['token']);
        $info = M('Teacher')->where(['id'=>$id])->find();
        $url = "http://".$_SERVER['HTTP_HOST']."/fzh_teacherweb/twoqi/sign.html";
        $return['image'] = qrcode($url, $id);
        $this->ApiReturn(1, 'success', $return);
    }

}