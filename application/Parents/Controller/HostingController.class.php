<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/3 0003
 * Time: 下午 01:32
 */

namespace Parents\Controller;


use Client\Controller\MapiBaseController;
use Client\Controller\UserApiBaseController;

class HostingController extends MapiBaseController
{

    /**
     * 二期新增
     * 托管课程
     * sunfan
     * 2018.5.3
     */

    public function index()
    {
        $data = $this->data;
        $st_id = $data['st_id'];
        $page = $data['page']??1;
        $where['status'] = ['neq', 3];
        $where['st_id'] = $st_id;

        $oauth_user_model=M('HostingCourse');
        $lists = $oauth_user_model
            ->where($where)
            ->order("add_time DESC")
            ->field('id as hc_id, title, img, month_price, day_price, star, buy_num')
            ->page($page)
            ->select();

        if (!$lists){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $lists);
        }
    }

    //详情
    public function detail()
    {
        $data = $this->data;
        $hc_id = !empty($data['hc_id'])?$data['hc_id']:$this->ApiReturn(-1,'id不能为空');
        $oauth_user_model=M('HostingCourse');
        $lists = $oauth_user_model
            ->where(['id'=>$hc_id])
            ->field('id as hc_id, title, img, month_price, day_price, star, content, st_id, buy_num, tui_price, no_days')
            ->find();

        //评价
        $assess = M('Assess')
            ->join('LEFT JOIN fzm_hosting_order ON fzm_hosting_order.id=fzm_assess.order_id')
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_assess.u_id')
            ->where(['fzm_hosting_order.st_id'=>$lists['st_id'], 'fzm_assess.type'=>3])
            ->field('child_name, child_sex, p_img, content, a_time, fzm_assess.status, img1, img2, img3, star')
            ->order('fzm_assess.a_time DESC')
            ->select();
        foreach($assess as $k=>$v){
            $assess[$k]['a_time'] = substr($v['a_time'],5,11);
            if ($v['status'] == 2){
                $assess[$k]['child_name'] = "匿名用户";
            }
            if (empty($v['img1'])){
                unset($assess[$k]['img1']);
            }
            if (empty($v['img2'])){
                unset($assess[$k]['img2']);
            }
            if (empty($v['img3'])){
                unset($assess[$k]['img3']);
            }
        }

        //机构信息
        $educational = M('SmallTableSort')
            ->where(['sid'=>$lists['st_id']])
            ->field('sid as st_id, s_name, s_img, address, c_name, s_content, s_phone, distance, star, view_num, level_id')
            ->find();
        //会员等级图标
        $educational['icon'] = M('Level')->where(['id'=>$educational['level_id']])->getField('icon');

        $lists['educational'] = $educational;
        $lists['assess'] = $assess;
        $this->ApiReturn(1, 'success', $lists);
    }


    //下单
    public function addOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $hc_id = $data['hc_id']??$this->ApiReturn(-1, '课程id不能为空');
        $type = $data['type']??1;   //1.按天 2.按月
        $date_period = $data['date_period']??$this->ApiReturn(-1, '时间段不能为空');
        $coupon_id = $data['cr_id'];//优惠券id

        $course = M('HostingCourse')->where(['id'=>$hc_id])->find();
        if (!$course)$this->ApiReturn(-1, '课程异常，请选择其他课程');

        if ($type==1){
            //1.按天

            //时间段
            $date_p = explode(',', $date_period);
            $time_length = diffBetweenTwoDays($date_p[0], $date_p[1]);

            //算钱
            $total = $course['day_price']*$time_length;
        }else{
            //2.按月

            //时间段
            $date_p = explode(';', $date_period);
            $time_length=0;
            foreach ($date_p as $item){
                $date_pa = explode(',', $item);
                $time_length += diffBetweenTwoDays($date_pa[0], $date_pa[1]);
            }
            $time_length = ceil($time_length/31);
            //算钱
            $total = $course['month_price']*$time_length;
        }
        $config = M('Config')->find();

        $platform_money = $total*$config['hosting_radio'];
        $money = $total*(1-$config['hosting_radio']);
        $order_sn = "H";
        $order_sn .= sp_get_order_sn();

        //使用优惠券
        $coupon_money=0;
        if (!empty($coupon_id)){
            $coupon_money = $this->useCoupon($id, 2, $total, $coupon_id);
        }
        $total -= $coupon_money;

        $o_time = date('Y-m-d H:i:s');
        $oid = M('HostingOrder')->add([
            'sn'            =>  $order_sn,
            'hc_id'         =>  $hc_id,
            'parent_id'     =>  $id,
            'class_num'     =>  $course['class'],
            'st_id'         =>  $course['st_id'],
            'total_money'   =>  $total,
            'platform_money'=>  $platform_money,
            'money'         =>  $money,
            'coupon_money'  =>  $coupon_money??0.00,
            'hc_title'      =>  $course['title'],
            'o_time'        =>  $o_time,
            'time_length'   =>  $time_length,
            'date_period'    =>  $date_period,
            'type'    =>  $type,
        ]);
        if ($oid){
            $rs['sn'] = $order_sn;
            $rs['title'] = $course['title'];
            $rs['o_time'] = $o_time;
            $rs['date_period'] = $date_period;
            $rs['time_length'] = $time_length;
            $rs['money'] = $total;
            $rs['type'] = $type;
            $this->ApiReturn(1, 'success', $rs);
        }else{
            $this->ApiReturn(-1, '失败', ['kefu_tel'=>$config['kefu_tel']]);
        }
    }


    /**
     * 二期增加
     * 托管订单
     * sunfan
     * 2018.5.7
     */
    public function getOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $where = ['parent_id'=>$id];
        if (!empty($data['status']))
        {
            $where['ho.status'] = $data['status'];
        }
        $orders = M('HostingOrder')
            ->alias('ho')
            ->join('LEFT JOIN fzm_small_table st ON st.id = ho.st_id')
            ->join('LEFT JOIN fzm_hosting_course hc ON hc.id = ho.hc_id')
            ->where($where)
            ->field('ho.id as oid, sn as order_sn, ho.status, total_money, hc.img, hc_title, time_length, tui_price, no_days, type, o_time, hc_id')
            ->order('o_time DESC')
            ->page($page)
            ->select();
        foreach ($orders as $k=>$order)
        {
            $orders[$k]['tui_status'] = -1;
            $orders[$k]['assess'] = -1;
            if ($order['status']==5)
            {
                $orders[$k]['tui_status'] = M('Application')->where(['oid'=>$order['oid'], 'o_type'=>3])->getField('status');
            }
            if ($order['status']==3)
            {
                if (M('Assess')->where(['order_id'=>$order['oid'],'type'=>3])->find())
                {
                    $orders[$k]['assess'] = 1;
                }
            }
        }
        if (!$orders){$this->ApiReturn(0, '没有数据');}

        $this->ApiReturn(1, 'success', $orders);
    }

    /**
     * 二期增加
     * 托管订单--详情页
     * sunfan
     * 2018.5.7
     */
    public function getOrderDetail()
    {
        $data = $this->data;
        $id = S($data['token']);
        $where = ['parent_id'=>$id];
        !empty($data['oid'])?$where['ho.id'] = $data['oid']:$this->ApiReturn(-1, '请选择要查看的订单');

        $orders = M('HostingOrder')
            ->alias('ho')
            ->join('LEFT JOIN fzm_hosting_course hc ON hc.id = ho.hc_id')
            ->where($where)
            ->field('ho.id as oid, sn as order_sn, ho.status, total_money, hc.img, hc_title, time_length, tui_price, no_days, type, date_period, o_time, hc_id ,hc.refunds coupon_refund')
            ->find();

        $orders['tui_status'] = -1;
        if ($orders['status']==5)
        {
            $tui = M('Application')->where(['oid'=>$data['oid'], 'o_type'=>3])->find();
            $orders['tui_status'] = $tui['status'];
            $orders['t_reason'] = $tui['t_reason'];  //申请退款原因
            $orders['b_reason'] = $tui['reason'];  //退款驳回原因
        }

        $orders['assess'] = -1;  //未评价
        if ($orders['status']==3)
        {
            if (M('Assess')->where(['order_id'=>$orders['oid'],'type'=>3])->find())
            {
                $orders['assess'] = 1;
            }
        }

        //会员身份
        $level_id = M('Parents')->where(['id'=>$id])->getField('level_id');
        $orders['usertype_name'] = M('Level')->where(['id'=>$level_id])->getField('name');

        $this->ApiReturn(1, 'success', $orders);
    }

    /**
     * 二期增加
     * 托管订单--申请售后
     * sunfan
     * 2018.5.7
     */
    public function refundOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $where = ['parent_id'=>$id];
        !empty($data['oid'])?$where['id'] = $data['oid']:$this->ApiReturn(-1, '请选择要退款的订单');
        $order = M('HostingOrder')->where($where)->find();
        $day_num = $data['day_num']??$this->ApiReturn(-1, '退款天数不能为空');
        $reason = $data['reason']??$this->ApiReturn(-1, '退款原因不能为空');
        if (!$order){$this->ApiReturn(-1, '订单异常');}
        if ($order['status']!=2){$this->ApiReturn(-1, '订单状态不允许退款');}

        $course = M('HostingCourse')->where(['id'=>$order['hc_id']])->find();
        $num = $order['time_length']-$day_num;//上了xx节课
        if ($num>$course['no_days']){
            $this->ApiReturn(-1, '超过可退款天数');
        }

        $money = $day_num*$course['tui_price'];
        M('HostingOrder')->where($where)->save(['status'=>5, 'refunds_money'=>$money]);

        M('Application')->add([
            'uid'   =>  $id,
            'u_type'=>  1,
            'money' =>  $money,
            'a_time'=>  date('Y-m-d H:i:s'),
            'type'  =>  1,  //1退款申请   2提现申请
            't_reason'=>$reason,
            'oid'   =>$order['id'],
            'o_type'=>3,    //1.老师课程订单 2.教育机构订单 3.托管订单
        ]);
        $this->ApiReturn(1, '申请成功，请等待工作人员处理');
    }

    /**
     * 二期增加
     * 托管订单--取消订单
     * sunfan
     * 2018.5.7
     */
    public function cancelOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $oid = $data['oid']??$this->ApiReturn(-1, '订单id不能为空');
        $order = M('HostingOrder')->where(['id'=>$oid, 'parent_id'=>$id])->find();
        if (!$order){
            $this->ApiReturn(-1, '订单异常');
        }
        if ($order['status']>1){
            $this->ApiReturn(-1, '订单状态不允许取消');
        }
        M('HostingOrder')->where(['id'=>$oid])->save(['status'=>4]);
        $this->ApiReturn(1, '取消成功');
    }

    /**
     * 二期增加
     * 托管订单--完成
     * sunfan
     * 2018.5.7
     */
    public function finishOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $oid = $data['oid']??$this->ApiReturn(-1, '订单id不能为空');
        $order = M('HostingOrder')
            ->where(['id'=>$oid, 'parent_id'=>$id])
            ->find();
        if (!$order){
            $this->ApiReturn(-1, '订单异常');
        }
        if ($order['status']!=2){
            $this->ApiReturn(-1, '订单状态异常');
        }
        M('HostingOrder')->where(['id'=>$oid])->save(['status'=>3]);
        $this->ApiReturn(1, '操作成功');
    }

    /**
     * 评价订单
     * sunfan
     * 2018.6.22
     */
    public function assess()
    {
        $data = $this->data;
        $id = S($data['token']);
        $order_sn = $data['order_sn']??$this->ApiReturn(-1, '订单编号不能为空');
        $order = M('HostingOrder')->where(['sn'=>$order_sn, 'parent_id'=>$id])->find();
        $content = $data['content']??$this->ApiReturn(-1, '评论内容不能为空');
        $star = $data['star']??$this->ApiReturn(-1, '评论星级不能为空');
        $status = $data['status']??1;   //2.匿名
        $img1 = $data['img1'];
        $img2 = $data['img2'];
        $img3 = $data['img3'];
        if (!$order){
            $this->ApiReturn(-1, '订单异常');
        }
        if ($order['status']!=3){
            $this->ApiReturn(-1, '订单暂不能评价');
        }
        if (M('Assess')->where(['order_id'=>$order['id'], 'type'=>3])->find()){
            $this->ApiReturn(-1, '订单已评价');
        }
        M('Assess')->add([
            'order_id'   =>  $order['id'],
            'content'=>  $content,
            'u_id' =>  $id,
            't_id' =>  $order['st_id'],
            'a_time'=>  date('Y-m-d H:i:s'),
            'star'  =>  $star,
            'status'=>  $status,
            'img1'=>  $img1,
            'img2'=>  $img2,
            'img3'=>  $img3,
            'type'=>3,       //1家长（给老师的评价）  2.家长给机构课程的评价 3.家长给托管课程的评价
            'st_id'=>$order['st_id']
        ]);

        //修改课程星级
        $course = M('HostingCourse')->where(['id'=>$order['hc_id']])->find();
        $star = ($course['star']+$star)/2;
        M('HostingCourse')->where(['id'=>$order['hc_id']])->save(['star'=>$star]);

        $this->ApiReturn(1, '评价成功');
    }

}