<?php
/**
 * Created by PhpStorm.
 * User: Sunfan
 * Date: 2017/12/26 0026
 * Time: 上午 10:41
 */

namespace ManagedClient\Controller;



use Client\Controller\UserApiBaseController;

class CouponController extends UserApiBaseController
{

    public function index(){
        $data = $this->data;
        $page = $data['page']??1;
        $id = S($data['token']);
        $where = array();
        $where['m.type'] = 1;
        $where['m.u_id'] = $id;
        $where['m.u_type'] = 2;
        $model = M("coupon");
        $info = $model->alias('m')
            ->join("LEFT JOIN fzm_pay_position pp on pp.id = m.pp_id")
            ->order('create_time desc')->where($where)
            ->page($page)
            ->field('m.id as cid, m.title, money, min_consumption, create_time, start_date, expire_date, c_status,pp.name as pp_name')
            ->select();

        //检查一遍，把过期的优惠券改为失效
        foreach ($info as &$item){
            if ($item['expire_date']<date('Y-m-d')){
                $model->where(['id'=>$item['cid']])->save(['c_status'=>2]);
            }

            $item['can'] = 1;
            //检查优惠券是否有人领过
            if (M('CouponRecords')->where(['coupon_id'=>$item['cid']])->find() && $item['c_status']==1){
                $item['can'] = -1;
            }

        }


        if (!$info){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $info);
        }
    }

    public function getDetail()
    {
        $data = $this->data;
        $id = S($data['token']);
        $cid = $data['cid']??$this->ApiReturn(-1, 'cid不能为空');
        $rs = M('Coupon')->alias('m')
            ->join("LEFT JOIN fzm_pay_position pp on pp.id = m.pp_id")
            ->where(['m.id'=>$cid, 'u_id'=>$id])
            ->field('m.id as cid, m.title, money, min_consumption, create_time, start_date, expire_date, c_status, pp_id, pp.name as pp_name')
            ->find();
        if (!$rs){
            $this->ApiReturn(-1, '优惠券不存在');
        }
        $this->ApiReturn(1, 'success', $rs);
    }

    public function getPayPosition()
    {
        $data = $this->data;
        $id = S($data['token']);
        $small = M('SmallTable')->where(['id'=>$id])->find();
        $type = (int)$small['s_type']+1;
        $terms = M('PayPosition')->where(['type'=>['like', "%".$type."%"]])->field('id as pp_id, name as pp_name')->select();
        $this->ApiReturn(1, 'success', $terms);
    }
    //添加优惠券
    public function add(){
        $data = $this->data;
        $id = S($data['token']);
        $map['pp_id']=$data['pp_id']??$this->ApiReturn(-1, 'pp_id不能为空');
        $map['u_type']=2;
        $map['u_id']=$id;
        $map['title'] = $data['title']??$this->ApiReturn(-1, 'title不能为空');
        $map['money'] = $data['money']??$this->ApiReturn(-1, 'money不能为空');
        $map['min_consumption'] = $data['min_consumption']??$this->ApiReturn(-1, 'min_consumption不能为空');
        $map['create_time'] = date('Y-m-d H:i:s');
        $map['start_date'] = $data['start_date']??$this->ApiReturn(-1, 'start_date不能为空');
        $map['expire_date'] = $data['expire_date']??$this->ApiReturn(-1, 'expire_date不能为空');
        $info = M('coupon')->add($map);
        if($info){
            $this->ApiReturn(1, '添加成功');
        }else{
            $this->ApiReturn(-1, '网络错误！');
        }
    }
    //编辑优惠券
    public function edit(){
        $data = $this->data;
        $id = S($data['token']);
        $cid = $data['cid']??$this->ApiReturn(-1, 'cid不能为空');
        $map['pp_id']=$data['pp_id']??$this->ApiReturn(-1, 'pp_id不能为空');
        $map['u_type']=2;
        $map['u_id']=$id;
        $map['title'] = $data['title']??$this->ApiReturn(-1, 'title不能为空');
        $map['money'] = $data['money']??$this->ApiReturn(-1, 'money不能为空');
        $map['min_consumption'] = $data['min_consumption']??$this->ApiReturn(-1, 'min_consumption不能为空');
        $map['create_time'] = date('Y-m-d H:i:s');
        $map['start_date'] = $data['start_date']??$this->ApiReturn(-1, 'start_date不能为空');
        $map['expire_date'] = $data['expire_date']??$this->ApiReturn(-1, 'expire_date不能为空');
        $info = M('coupon')->where(['id'=>$cid])->save($map);
        if($info){
            $this->ApiReturn(1, '修改成功');
        }else{
            $this->ApiReturn(-1, '网络错误！');
        }
    }
    //禁用优惠券
    public function ban(){
        $data = $this->data;
        $id = S($data['token']);
        $cid = $data['cid']??$this->ApiReturn(-1, 'cid不能为空');
        $status = $data['c_status'];//1正常 2失效
        $info = M('coupon')->where(array('id'=>$cid))->save(array('c_status'=>$status));
        $this->ApiReturn(1, '修改成功');
    }
    //删除优惠券
    public function delete(){
        $data = $this->data;
        $id = S($data['token']);
        $cid = $data['cid']??$this->ApiReturn(-1, 'cid不能为空');

        $info = M('coupon')->where(array('id'=>$cid))->delete();
        if($info){
            M('CouponRecords')->where(array('coupon_id'=>$id))->delete();
            $this->ApiReturn(1, '成功');
        }else{
            $this->ApiReturn(-1, '失败');
        }
    }

}