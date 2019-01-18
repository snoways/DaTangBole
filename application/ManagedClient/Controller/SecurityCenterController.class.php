<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/17 0017
 * Time: 上午 10:58
 */

namespace ManagedClient\Controller;


use Client\Controller\UserApiBaseController;

class SecurityCenterController extends UserApiBaseController
{

    //修改密码
    public function changePwd()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['password'] ==""){
            $this->ApiReturn(-1, '原密码不存在');
        }
        if($data['newpassword'] ==""){
            $this->ApiReturn(-1, '新密码不存在');
        }
        if($data['newpassword'] == $data['password']){
            $this->ApiReturn(-1, '新密码不能与原密码相同');
        }
        $user = M("SmallTable");
        $find = $user->where(array('id'=>$id))->find();
        if($find['s_password'] != $data['password']){
            $this->ApiReturn(-1, '原密码不正确');
        }
        $save = $user->where(array('id'=>$id))->save(array('s_password'=>$data['newpassword']));
        if(!$save){
            $this->ApiReturn(-1,'修改失败');
        }
        $this->ApiReturn(1,'修改成功');
    }

    //修改手机号
    public function editphone()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['phone'] ==""){
            $this->ApiReturn(-1, '电话不存在');
        }
        if($data['password'] ==""){
            $this->ApiReturn(-1, '密码不存在');
        }
        if($data['newphone'] ==""){
            $this->ApiReturn(-1, '新电话不存在');
        }
        if($data['code'] ==""){
            $this->ApiReturn(-1, 'code不存在');
        }
        $user = M("SmallTable");
        checkPhoneCode($data['newphone'], $data['code']);
        $find = $user->where(array('s_phone'=>$data['phone']))->find();
        if(!$find){
            $this->ApiReturn(-1, '用户不存在');
        }

        if ($user->where(array('s_phone'=>$data['newphone'], 'id'=>['neq', $find['id']]))->find()){
            $this->ApiReturn(-1, '用户已存在');
        }
        if($find['s_password'] != $data['password']){
            $this->ApiReturn(-1, '用户密码不正确');
        }
        $save = $user->where(array('id'=>$id))->save(array('s_phone'=>$data['newphone']));
        if(!$save){
            $this->ApiReturn(-1, '修改失败');
        }
        $this->ApiReturn(1, '修改成功');
    }

    //银行卡列表
    public function getBankList()
    {
        $data = $this->data;
        $id = S($data['token']);
        $list =  M('Bank')->where(array('st_id'=>$id))->field('id as bid, address, name, card, phone')->select();
        if(!$list){
            $this->ApiReturn(0,'没有信息');
        }
        $this->ApiReturn(1,'成功',$list);
    }

    //银行卡删除
    public function delBank()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['bid'] ==""){
            $this->ApiReturn(-1, 'bid不存在');
        }
        $del = M('Bank')->where(array('st_id'=>$id,'id'=>array('in',$data['bid'])))->delete();
        $this->ApiReturn(1,'成功');
    }

    //添加银行卡
    public function addBank()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['card'] ==""){
            $this->ApiReturn(-1, '卡号不存在');
        }
        if($data['name'] ==""){
            $this->ApiReturn(-1, '姓名不存在');
        }
        if($data['address'] ==""){
            $this->ApiReturn(-1, '开户行不存在');
        }
        if($data['phone'] ==""){
            $this->ApiReturn(-1, '手机号不存在');
        }
        $add = M('Bank')->add(array('st_id'=>$id,'card'=>$data['card'],'name'=>$data['name'],'address'=>$data['address'],'phone'=>$data['phone']));
        if(!$add){
            $this->ApiReturn(-1,'失败');
        }
        $this->ApiReturn(1,'成功');
    }

    /**
     * 我的营业额
     */
    public function wallet()
    {
        $data = $this->data;
        $id = S($data['token']);

        //1线下活动 2教育机构/托管订单  3线上课堂
        if(!in_array($data['type'],[1,2,3]))$this->ApiReturn(-1,'参数错误');
        //时间搜索
        if($data['start_time'] && $data['end_time']){
            if($data['type'] == 1){
                $where['o.sign_time'] = ['between', [$data['start_time'], $data['end_time']]];
            }else{
                $where['o.o_time'] = ['between',[$data['start_time'],$data['end_time']]];
            }
        }
        //页码
        $page = $data['page']??1;
        $info = M('SmallTable')->where(array('id'=>$id))->find();
        $other = M('ActivityPreview')->where(['shop_id'=>$id])->sum('money');
        $return['balance'] = $info['balance']+$other;

        $rs=[];
        $money = 0;
        $count = 0;
        if($data['type'] == 2) {
            if ($info['s_type'] == 1){
                //托管
                $db = M('HostingOrder');
                $field="hc_title";
            }else{
                //教育机构
                $db = M('EducationalOrder');
                $field="subject";
            }

            //关键字搜索
            if($data['keyword']){
                $keyword = trim($data['keyword']);
                $where['o.sn|'.$field] = ['like',"%$keyword%"];
            }

            $where['o.st_id'] = $id;
            $where['o.status'] = array('in',('2,3,5'));
            $where['o.money'] = array('gt',0);
            $count = $db->alias('o')
                ->join('left join fzm_parents p on o.parent_id=p.id')
                ->where($where)
                ->count();
            $list = $db->alias('o')
                ->join('left join fzm_parents p on o.parent_id=p.id')
                ->where($where)
                ->order('o.o_time desc')
                ->limit(($page-1)*10,10)
                ->select();

            if (!empty($list))foreach ($list as $k=>$item){
                $rs[$k]['sn'] = $item['sn'];
                $rs[$k]['o_time'] = $item['o_time'];
                $rs[$k]['title'] = $item[$field];
                $rs[$k]['money'] = $item['money'];

                $money = $db->alias('o')
                    ->join('left join fzm_parents p on o.parent_id=p.id')
                    ->where($where)
                    ->sum('o.money');
            }
        }elseif($data['type'] == 1){
            //关键字搜索
            if($data['keyword']){
                $keyword = trim($data['keyword']);
                $where['o.sn|a.title'] = ['like',"%$keyword%"];
            }

            //线下活动
            $where['a.shop_id'] = intval($id);
            $where['o.status'] = ['in',[2,3]];
            $count = D('ActivityOrder')
                ->alias('o')
                ->join('left join __ACTIVITY__ a on o.activity_id = a.id')
                ->where($where)
                ->count();
            $list = D('ActivityOrder')
                ->alias('o')
                ->join('left join __ACTIVITY__ a on o.activity_id = a.id')
                ->where($where)
                ->order('o.sign_time desc')
                ->page($page)
                ->field('o.*,a.title')
                ->select();
            if (!empty($list))foreach ($list as $k=>$item){
                $rs[$k]['sn'] = $item['sn'];
                $rs[$k]['o_time'] = $item['sign_time'];
                $rs[$k]['title'] = $item['title'];
                $rs[$k]['money'] = $item['money'];

                $money = D('ActivityOrder')
                    ->alias('o')
                    ->join('left join __ACTIVITY__ a on o.activity_id = a.id')
                    ->where($where)
                    ->sum('o.money');
            }
        }else{
            //关键字搜索
            if($data['keyword']){
                $keyword = trim($data['keyword']);
                $where['o.sn|title|t_sub1|t_sub2'] = ['like',"%$keyword%"];
            }
            //线上课堂
            $where['o.status'] = 2;
            $where['oc.tid'] = $id;
            $where['oc.u_type'] = 2;
            $count = D("OnlineClassOrder")->alias('o')
                ->join("LEFT JOIN fzm_online_class oc on oc.id = o.oc_id")
                ->where($where)
                ->count();
            $list = D("OnlineClassOrder")->alias('o')
                ->join("LEFT JOIN fzm_online_class oc on oc.id = o.oc_id")
                ->where($where)
                ->field('o.*,oc.title,oc.t_sub1,oc.t_sub2')
                ->limit(($page-1)*10,10)
                ->order('o.o_time desc')
                ->select();
            if (!empty($list))foreach ($list as $k=>$item){
                $rs[$k]['sn'] = $item['sn'];
                $rs[$k]['o_time'] = $item['o_time'];
                $rs[$k]['title'] = $item['title'].''.$item['t_sub1'].''.$item['t_sub2'];
                $rs[$k]['money'] = $item['money'];

                $money = D("OnlineClassOrder")->alias('o')
                    ->join("LEFT JOIN fzm_online_class oc on oc.id = o.oc_id")
                    ->where($where)
                    ->sum('o.money');
            }
        }
        $return['count'] = $count;
        $return['total_money'] = $money;
        $return['list'] = $rs;
        $this->ApiReturn(1, 'success', $return);
    }

    //提现申请提交
    public function apply()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['money'] ==""){
            $this->ApiReturn(-1, '请输入提现金额');
        }
        if($data['money'] <= 0){
            $this->ApiReturn(-1, '金额输入有误,请重新输入！');
        }
        $card_id = $data['card_id']??$this->ApiReturn(-1, '请选择银行卡');
        if($data['phone'] ==""){
            $this->ApiReturn(-1, '手机号不存在');
        }
        if($data['code'] ==""){
            $this->ApiReturn(-1, '验证码不存在');
        }
        $config = M('Config')->find();
        $find = M('SmallTable')->where(array('id'=>$id))->find();

        if($find['balance'] < $config['quota']){
            $this->ApiReturn(-1, '您的余额不满足最低提现额度');
        }

        if($find['balance'] < $data['money']){
            $this->ApiReturn(-1, '余额不足，不能提现');
        }
        checkPhoneCode($data['phone'], $data['code']);

        $card = M('Bank')->where(['st_id'=>$id, 'id'=>$card_id])->find();
        if (!$card){
            $this->ApiReturn(-1, '银行卡不存在');
        }

        $add = M('Application')->add([
            'uid'=>$id,
            'u_type'=>3,        //1.家长 2.老师 3.托管/教育机构
            'money'=>$data['money'],
            'a_time'=>date('Y-m-d H:i:s'),
            'number'=>$card['card'],
            'status'=>1,        //1申请中   2已退款或提现成功  3已驳回
            'type'=>2,          //1退款申请   2提现申请
            'card_name'=>$card['name'],
            'address'=>$card['address'],
            'phone'=>$card['phone'],
            'sn'    =>  'W'.sp_get_order_sn()
        ]);
        //冻结金额
        $find = M('SmallTable')->where(array('id'=>$id))->find();
        $other = $find['balance']- $data['money'];
        $freeze = M('SmallTable')->where(array('id'=>$id))->save(array('balance'=>$other,'freeze'=>$data['money']));
        if(!$add){
            $this->ApiReturn(-1,'提交失败');
        }
        $this->ApiReturn(1,'提交成功');
    }
    //支付宝提现
    public function ali_apply(){
        $data = $this->data;
        $id = S($data['token']);
        if($data['number'] ==""){
            $this->ApiReturn(-1, '请输入支付宝账号');
        }
        if($data['card_name'] ==""){
            $this->ApiReturn(-1, '请输入用户名');
        }
        if($data['money'] ==""){
            $this->ApiReturn(-1, '请输入提现金额');
        }
        if($data['money'] <= 0){
            $this->ApiReturn(-1, '金额输入有误,请重新输入！');
        }
        if($data['phone'] ==""){
            $this->ApiReturn(-1, '手机号不存在');
        }
        if($data['code'] ==""){
            $this->ApiReturn(-1, '验证码不存在');
        }
        $config = M('Config')->find();
        $find = M('SmallTable')->where(array('id'=>$id))->find();

        if($find['balance'] < $config['quota']){
            $this->ApiReturn(-1, '您的余额不满足最低提现额度');
        }

        if($find['balance'] < $data['money']){
            $this->ApiReturn(-1, '余额不足，不能提现');
        }
        checkPhoneCode($data['phone'], $data['code']);

        $add = M('Application')->add([
            'uid'=>$id,
            'u_type'=>3,        //1.家长 2.老师 3.托管/教育机构
            'money'=>$data['money'],
            'a_time'=>date('Y-m-d H:i:s'),
            'number'=>$data['number'],
            'card_name'=>$data['card_name'],
            'status'=>1,        //1申请中   2已退款或提现成功  3已驳回
            'type'=>2,          //1退款申请   2提现申请
            'phone'=>$data['phone'],
            't_type'=>2,
            'sn'    =>  'W'.sp_get_order_sn()
        ]);
        //冻结金额
        $find = M('SmallTable')->where(array('id'=>$id))->find();
        $other = $find['balance']- $data['money'];
        $freeze = M('SmallTable')->where(array('id'=>$id))->save(array('balance'=>$other,'freeze'=>$data['money']));
        if(!$add){
            $this->ApiReturn(-1,'提交失败');
        }
        $this->ApiReturn(1,'提交成功');

    }

    //提现申请
    public function application()
    {
        $data = $this->data;
        if(!in_array($data['type'],[1,2]))$this->ApiReturn(-1,'参数错误');
        $id = S($data['token']);
        $page = $data['page']??1;
        //提现到银行卡
        if($data['type'] == 1){
            $where['t_type'] = 1;
        }else{
            //支付宝
            $where['t_type'] = 2;
        }
        $where['uid'] = $id;
        $where['u_type'] = 3;
        $where['type'] = 2;

        $info = M('SmallTable')->where(array('id'=>$id))->find();
        $other = M('ActivityPreview')->where(['shop_id'=>$id])->sum('money');
        $return['balance'] = $info['balance']+$other;

        $List = M('Application')
            ->limit(($page-1)*10,10)
            ->order('a_time desc')
            ->field('uid,money,a_time,number,status,reason')
            ->where($where)->select();
        if(!$List){
            $return['info'] = [];
            $this->ApiReturn(0,'没有记录',$return);
        }
        $return['info'] = $List;
        $this->ApiReturn(1,'成功',$return);
    }

    //是否可以提交申请
    public function checkApply()
    {
        $data = $this->data;
        $id = S($data['token']);
        $find = M('Application')->where(array('uid'=>$id,'status'=>1,'type'=>2, 'u_type'=>3))->find();
        if($find){
            $arr['type'] = 1;   //不可以
        }else{
            $arr['type'] = 2;    //可以
        }
        $this->ApiReturn(1,'成功',$arr);
    }
}