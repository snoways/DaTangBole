<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/18 0018
 * Time: 下午 04:39
 */

namespace Parents\Controller;


use Client\Controller\UserApiBaseController;

class CouponController extends UserApiBaseController
{

    /**
     * 优惠券
     * 二期
     * 20180518
     */
    public function index()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = !empty($data['page'])?$data['page']:1;
        $db = M('CouponRecords');
        $where['uid'] = $id;
        $where['cr_status'] = !empty($data['status'])?$data['status']:1;    //1未使用2已使用3已过期

        //检查一遍，把过期的优惠券改为过期
        $list = $db->where($where)->select();
        foreach ($list as $k=>$item){
            if ($item['expire_date']<date('Y-m-d')){
                $db->where(['id'=>$item['id']])->save(['cr_status'=>3]);
            }
        }

        $list = $db
            ->alias('cr')
            ->join('LEFT JOIN fzm_coupon c ON c.id=cr.coupon_id')
            ->join('LEFT JOIN fzm_pay_position pp ON pp.id=c.pp_id')
            ->where($where)
            ->page($page)
            ->order('cr.create_time DESC')
            ->field('cr.id as crid, c.title, cr.money, cr.min_consumption, cr.expire_date, pp_id, pp.name, cr_status as status')
            ->select();

        if (!$list){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $list);
        }
    }
    /**
     * 下单前--查找所有可用优惠券
     * sunfan
     * 2018.5.21
     */
    public function findCoupon()
    {
        $data = $this->data;
        $uid = S($data['token']);
        $type = !empty($data['type'])?$data['type']:$this->ApiReturn(-1, '请选择订单类型');// 1.老师下单 2.托管班下单 3.教育机构下单 4.线下活动订单 5.线上课堂订单
        $money = !empty($data['money'])?$data['money']:$this->ApiReturn(-1, '订单金额不能为空');
        //是否有优惠券
        //是否过期
        $coupons = M('CouponRecords')
            ->alias('cr')
            ->join('LEFT JOIN fzm_coupon c ON c.id=cr.coupon_id')
            ->join('LEFT JOIN fzm_pay_position pp ON pp.id=c.pp_id')
            ->where([
                'uid'=>$uid,
                'cr_status'=>1,
                'cr.expire_date'=>['gt', date('Y-m-d')],
            ])
            ->field('cr.id as crid, c.title, cr.money, cr.min_consumption, cr.expire_date, pp.name, c.pp_id')
            ->order('cr.create_time DESC')
            ->select();
        if (!$coupons){
            $this->ApiReturn(0, '没有可使用的优惠券');
        }

        //找一下 该类目或者通用类目可使用的优惠券
        //判断满足满减金额
        foreach ($coupons as $k=>$coupon)
        {
            $coupons[$k]['can'] = 1;
            if ($coupon['pp_id']!=1 && $coupon['pp_id']!=($type+1)){
                $coupons[$k]['can'] = -1;
            }
            if ($coupon['min_consumption']>$money){
                $coupons[$k]['can'] = -1;
            }
        }
        if (!$coupons){
            $this->ApiReturn(0, '没有可使用的优惠券');
        }
        $coupons = array_values($coupons);
        $this->ApiReturn(1, 'success', $coupons);
    }

    //获取商户端优惠券列表
    public function getShopCoupon()
    {
        $data = $this->data;
        $page = $data['page']??1;
        $st_id = $data['st_id']??$this->ApiReturn(-1, 'st_id不能为空');
        $where = array();
        $where['m.type'] = 1;
        $where['m.u_id'] = $st_id;
        $where['m.u_type'] = 2;
        $model = M("coupon");
        $info = $model->alias('m')
            ->join("LEFT JOIN fzm_pay_position pp on pp.id = m.pp_id")
            ->order('create_time desc')->where($where)
            ->page($page)
            ->field('m.id as cid, m.title, money, min_consumption, create_time, start_date, expire_date, c_status,pp.name as pp_name')
            ->select();

        //检查一遍，把过期的优惠券改为失效
        foreach ($info as $item){
            if ($item['expire_date']<date('Y-m-d H:i:s')){
                $model->where(['id'=>$item['id']])->save(['c_status'=>2]);
            }
        }

        if (!$info){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $info);
        }
    }

    //领取商户优惠券
    public function receiveCoupon()
    {
        $data = $this->data;
        $id = S($data['token']);
        $st_id = $data['st_id']??$this->ApiReturn(-1, 'st_id不能为空');
        $cid = $data['cid']??$this->ApiReturn(-1, 'cid不能为空');
        $coupon = M('Coupon')->where(['id'=>$cid])->find();
        if (!$coupon || $coupon['c_status']==2){
            $this->ApiReturn(-1, '优惠券已经失效了');
        }
        if (M('CouponRecords')->where(['coupon_id'=>$cid, 'uid'=>$id])->find()){
            $this->ApiReturn(-1, '每个账号只能领一张~');
        }
        M('CouponRecords')->add([
            'coupon_id'     =>  $cid,
            'uid'           =>  $id,
            'create_time'   =>  date('Y-m-d H:i:s'),
            'start_date'    =>  $coupon['start_date'],
            'expire_date'   =>  $coupon['expire_date'],
            'money'         =>  $coupon['money'],
            'min_consumption'=> $coupon['min_consumption'],
        ]);
        $this->ApiReturn(1, 'success');
    }
}