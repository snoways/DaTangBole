<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/4 0004
 * Time: 上午 10:21
 */

namespace Parents\Controller;

use Client\Controller\UserApiBaseController;

class ActivityController extends UserApiBaseController
{


    /**
     * 二期新增
     * 线下活动
     * sunfan
     * 2018.5.3
     */


    public function index()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $type = $data['type']??1; //1.进行中的 2.已结束的

        $now = date('Y-m-d H:i:s');
        if ($type==2){
            $where['start'] = ['lt', $now];
        }else{
            $where['end'] = ['egt', $now];
        }
        $where['a.status'] = ['in', [1,4]];

        //判断活动列表是否已经结束

        //分类筛选
        if (!empty($data['cate_id'])){
            $where['cate_id'] = $data['cate_id'];
        }

        //关键字搜索
        if (!empty($data['keyword'])){
            $where['title'] = ['like', "%".$data['keyword']."%"];
        }

        //机构筛选
        if (!empty($data['st_id'])){
            $where['shop_id'] = $data['st_id'];
        }

        $oauth_user_model=M('Activity');
        $lists = $oauth_user_model
            ->alias('a')
            ->join('LEFT JOIN fzm_activity_cate ac ON ac.id=a.cate_id')
            ->join('LEFT JOIN fzm_small_table st ON st.id=a.shop_id')
            ->where($where)
            ->order("add_time DESC")
            ->page($page)
            ->field('a.id as aid, a.title, a.img, a.people_num, a.now_num, a.money, a.content, a.shop_id, a.start, a.end, a.state, ac.name as cate_name, st.s_name, st.id as st_id, st.s_type, st.s_img, a.view_num')
            ->select();
        foreach($lists as $k=>$v){
            if($now > $v['end']){
                $save = $oauth_user_model->where(['id'=>$v['aid']])->save(['state'=>2]);
            }
        }
        if (!$lists){
            $this->ApiReturn(0, 'success');
        }else{

            foreach ($lists as &$item) {
                //收藏
                $item['collection'] = -1;
                if ($item['shop_id']!=-1){
                    if (M('Collection')->where(['parent_id'=>$id, 'item_id'=>$item['st_id'], 'type'=>2])->find())
                    {
                        $item['collection'] = 1;
                    }
                }else{
                    $item['s_name'] = "大唐伯乐平台";
                    $item['s_img'] = "/public/images/touxiang.jpg";
                    $item['st_id'] = -1;
                }
            }

            //调取活动结束后返送给商家余额
//            activity_managed();
            $this->ApiReturn(1, 'success', $lists);
        }
    }

    public function detail()
    {
        $data = $this->data;
        $st_id = $data['st_id'];
        $where['a.status'] = ['neq', 3];
        !empty($data['aid'])?$where['a.id']=$data['aid']:$this->ApiReturn(-1, '活动id不能为空');

        //浏览量加1
        M('Activity')->where(['id'=>$data['aid']])->setInc('view_num');

        $now = date('Y-m-d H:i:s');

        $oauth_user_model=M('Activity');
        $lists = $oauth_user_model
            ->alias('a')
            ->join('LEFT JOIN fzm_activity_cate ac ON ac.id=a.cate_id')
            ->join('LEFT JOIN fzm_small_table st ON st.id=a.shop_id')
            ->where($where)
            ->field('a.id as aid, a.title, a.img, a.people_num, a.now_num, a.end_time, a.money, a.content, a.address, a.a_xy, a.start, a.end, ac.name as cate_name, st.s_name, st.id as st_id, a.view_num, a.video_cover, a.video_url, state, shop_id, a.status')
            ->find();
        if (!$lists){
            $this->ApiReturn(-1, '活动不存在');
        }

        if ($lists['status']==2){
            $this->ApiReturn(-1, '活动已下架');
        }

        $lists['can'] = 1;//可以报名
        if ($lists['end_time']<$now){
            //过期
            $lists['can'] = 2;//不能报名
        }

        if ($lists['shop_id']!=-1){

        }
        //机构信息
        $educational = M('SmallTableSort')
            ->where(['sid'=>$lists['st_id']])
            ->field('sid as st_id, s_name, s_img, address, c_name, s_content, s_phone, distance, star, view_num, level_id, s_type')
            ->find();
        //会员等级图标
        $educational['icon'] = M('Level')->where(['id'=>$educational['level_id']])->getField('icon');

        $lists['educational'] = $educational;
        //云信id
        $lists['accid'] = "s".$lists['st_id'];

        $this->ApiReturn(1, 'success', $lists);
    }

    //下单
    public function addOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $aid = $data['aid']??$this->ApiReturn(-1, '课程id不能为空');
        $coupon_id = $data['cr_id'];//优惠券id
        if (empty($data['list'])){
            $this->ApiReturn(-1, '报名人不能为空');
        }
        $list = json_decode($data['list']);

        $activity = M('Activity')->where(['id'=>$aid])->find();
        if (!$activity)$this->ApiReturn(-1, '活动异常，请选择其他活动');
        if ($activity['now_num']>=$activity['people_num']){
            $this->ApiReturn(-1, '该活动人数已满，不能报名');
        }
        $count = count($list);
        if (($activity['now_num']+$count)>$activity['people_num']){
            $this->ApiReturn(-1, '报名人数超出活动人数，请重新提交');
        }
        $config = M('Config')->find();
        $order_sn = "A";
        $order_sn .= sp_get_order_sn();

        $total = $activity['money']*$count;

        //使用优惠券
        $coupon_money=0;
        if (!empty($coupon_id)){
            $coupon_money = $this->useCoupon($id, 4, $total, $coupon_id);
        }
        $total -= $coupon_money;
        $platform_money = $total*$config['activity_radio'];
        $money = $total*(1-$config['activity_radio']);

        $o_time = date('Y-m-d H:i:s');

        $oid = M('ActivityOrder')->add([
            'sn'            =>  $order_sn,
            'activity_id'   =>  $aid,
            'uid'           =>  $id,
            'pay_money'     =>  $total,
            'num'           =>  $count,
            'sign_time'     =>  $o_time,
            'platform_money'=>  $platform_money,
            'money'         =>  $money,
            'coupon_money'  =>  $coupon_money??0.00,
        ]);

        //添加人
        $price = sprintf("%.2f",$total/count($list));
        foreach ($list as $item){
            M('ActOrderDetail')->add([
                'order_id'      =>  $oid,
                'member_name'   =>  $item->name,
                'member_phone'  =>  $item->phone,
                'price'         =>  $price,
            ]);
        }

        if ($oid){
            $rs['sn'] = $order_sn;
            $this->ApiReturn(1, 'success', $rs);
        }else{
            $this->ApiReturn(-1, '失败', ['kefu_tel'=>$config['kefu_tel']]);
        }
    }
    
    //分类
    public function categoryList()
    {
        $data = $this->data;
        $id = S($data['token']);
        $db = M('ActivityCate');
        $list = $db->field('id as cate_id, name')->select();
        $this->ApiReturn(1, 'success', $list);
    }

    /**
     * 二期增加
     * 活动订单
     * sunfan
     * 2018.5.7
     */
    public function getOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $where = ['uid'=>$id];
        if (!empty($data['status']))
        {
            $where['ao.status'] = $data['status'];
        }
        $orders = M('ActivityOrder')
            ->alias('ao')
            ->join('LEFT JOIN fzm_activity ac ON ac.id = ao.activity_id')
            ->where($where)
            ->field('ao.id as oid, sn as order_sn, ao.status, pay_money, refund_money , ac.img, ac.title, refund_status, sign_time, end_time, activity_id, ac.start, ac.end')
            ->order('sign_time DESC')
            ->page($page)
            ->select();

        foreach ($orders as $k=>$item){
            $orders[$k]['tui'] = 1;
            if (date('Y-m-d H:i:s')>$item['end_time']){
                $orders[$k]['tui'] = -1;
            }
            $orders[$k]['pay_money'] = sprintf("%.2f",$item['pay_money'] - $item['refund_money']);
            $orders[$k]['num'] = M('ActOrderDetail')->where(['order_id'=>$item['oid'], 'status'=>1])->count();
        }

        if (!$orders){$this->ApiReturn(0, '没有数据');}

        $this->ApiReturn(1, 'success', $orders);
    }

    /**
     * 二期增加
     * 活动订单--详情页
     * sunfan
     * 2018.5.7
     */
    public function getOrderDetail()
    {
        $data = $this->data;
        $id = S($data['token']);
        $where = ['uid'=>$id];
        !empty($data['oid'])?$where['ao.id'] = $data['oid']:$this->ApiReturn(-1, '请选择要查看的订单');

        $orders = M('ActivityOrder')
            ->alias('ao')
            ->join('LEFT JOIN fzm_parents p ON p.id = ao.uid')
            ->join('LEFT JOIN fzm_activity ac ON ac.id = ao.activity_id')
            ->where($where)
            ->field('ao.id as oid, sn as order_sn, ao.status, pay_money,refund_money, ac.img, ac.title, refund_status, sign_time, p.phone, p.parent_name, end_time, p.p_img, sign_time, activity_id, ac.start, ac.end')
            ->find();

        //会员身份
        $level_id = M('Parents')->where(['id'=>$id])->getField('level_id');
        $orders['usertype_name'] = M('Level')->where(['id'=>$level_id])->getField('name');

        $orders['tui'] = 1;
        if (date('Y-m-d H:i:s')>$orders['end_time']){
            $orders['tui'] = -1;
        }
        $orders['pay_money'] = sprintf("%.2f",$orders['pay_money'] - $orders['refund_money']);
        $orders['num'] = M('ActOrderDetail')->where(['order_id'=>$orders['oid'], 'status'=>1])->count();
        $orders['list'] = M('ActOrderDetail')->where(['order_id'=>$orders['oid'], 'status'=>1])->field('id as aod_id, member_name, member_phone, price')->select();

        $this->ApiReturn(1, 'success', $orders);
    }

    /**
     * 二期增加
     * 活动订单--取消订单
     * sunfan
     * 2018.5.7
     */
    public function cancelOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $oid = $data['oid']?$data['oid']:$this->ApiReturn(-1, '订单id不能为空');
        $order = M('ActivityOrder')->where(['id'=>$oid, 'uid'=>$id])->find();
        if (!$order){
            $this->ApiReturn(-1, '订单异常');
        }
        if ($order['status']>1){
            $this->ApiReturn(-1, '订单状态不允许取消');
        }
        M('ActivityOrder')->where(['id'=>$oid])->save(['status'=>5]);
        $this->ApiReturn(1, '取消成功');
    }

    /**
     * 二期增加
     * 活动订单--申请售后
     * sunfan
     * 2018.5.7
     */
    public function refundOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $where = ['uid'=>$id];
        !empty($data['oid'])?$where['id'] = $data['oid']:$this->ApiReturn(-1, '请选择要退款的订单');
        $order = M('ActivityOrder')->where($where)->find();
        $memberids_arr = !empty($data['aod_ids'])?explode(',', $data['aod_ids']):$this->ApiReturn(-1, '请选择要退款的会员');
        $reason = $data['reason']??$this->ApiReturn(-1, '退款原因不能为空');
        if (!$order){$this->ApiReturn(-1, '订单异常');}
        if ($order['status']!=2){$this->ApiReturn(-1, '订单状态不允许退款');}

        $activity = M('Activity')->where(['id'=>$order['activity_id']])->find();
        if (date('Y-m-d H:i:s')>$activity['end_time']){
            $this->ApiReturn(-1, '活动已结束，不允许退款');
        }

        $money=0;
        foreach ($memberids_arr as $item){
            //修改订单详情 该用户状态为2.申请退款了
            M('ActOrderDetail')->where(['id'=>$item])->setInc('status');
            $money += M('ActOrderDetail')->where(['id'=>$item])->getField('price');
        }

        //查找该活动所有报名人，如果全部退款了，判断一下这次退款金额
        $a=0;//全退款
        $actOrderDetail = M('ActOrderDetail')->where(['order_id'=>$order['id']])->select();
        foreach ($actOrderDetail as &$v){
            if ($v['status']==1){
                $a+=1;//部分退款
            }
        }
        if ($a==0){
            $all_refund_money = M('ActOrderDetail')->where(['order_id'=>$order['id']])->sum('price');
            if ($order['pay_money']>$all_refund_money){
                $money += $order['pay_money']-$all_refund_money;
            }
        }

        M('ActivityOrder')->where($where)->save([
            'status'=>3,
            'refund_status'=>2,
            'reason'=>$reason,
            'now_refund_money'=>$money,
            'now_refund_num'=>count($memberids_arr)
        ]);
        M("ActivityOrder")->where($where)->setInc('refund_money',$money);
        M("Activity")->where(['id'=>$order['activity_id']])->setDec('now_num',count($memberids_arr));
        $this->ApiReturn(1, '申请成功，请等待工作人员处理');
    }
}