<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/10 0010
 * Time: 上午 11:05
 */

namespace Managed\Controller;

use Think\Page;
use Think\Controller;

class WalletController extends ManagedBaseController
{

    public function index()
    {
        $small_id = $_SESSION['small_id'];
        if ($_SESSION['small_table']['s_type'] == 1){
            //托管
            $db = M('HostingOrder');
            $join = 'left join fzm_hosting_course a on a.id = o.hc_id';
        }else{
            //教育机构
            $db = M('EducationalOrder');
            $join = 'left join fzm_educational_course a on a.id = o.ec_id';
        }
        if(I("sn")){
            $sn = trim(I("sn"));
            $where['o.sn|a.title'] = array('like',"%$sn%");
            $this->assign('sn',$sn);
        }
        $info = M('small_table')->where(array('id'=>$small_id))->find();
//        $other = M('ActivityPreview')->where(['shop_id'=>$small_id])->sum('money');
//        $info['balance'] += $other;
        $this->assign('info',$info);
        $type = I("type");
        $this->assign('type_red',$type);
        $money=0;
        if($type == 1){
            if (I('start') && I('end')){
                $where['o.sign_time'] = ['between', [I('start'), I('end')]];
            }
            //线下活动
            $where['a.shop_id'] = intval($small_id);
            $where['o.status'] = ['in',[2,3]];
            $count = D('ActivityOrder')
                ->alias('o')
                ->join('left join __ACTIVITY__ a on o.activity_id = a.id')
                ->where($where)
                ->count();
            $page = $this->page($count, 15);
            $list = D('ActivityOrder')
                ->alias('o')
                ->join('left join __ACTIVITY__ a on o.activity_id = a.id')
                ->where($where)
                ->order('o.sign_time desc')
                ->limit($page->firstRow.','.$page->listRows)
                ->field('o.*,a.title,a.people_num,a.now_num')
                ->select();
            $money += D('ActivityOrder')
                ->alias('o')
                ->join('left join __ACTIVITY__ a on o.activity_id = a.id')
                ->where($where)
                ->sum('o.money')??0;
        }elseif($type == 2){
            //教育机构
            if (I('start') && I('end')){
                $where['o.o_time'] = ['between', [I('start'), I('end')]];
            }
            $where['o.st_id'] = $small_id;
            $where['o.status'] = array('in',('2,3,5'));
            $where['o.money'] = array('gt',0);
            $count = $db->alias('o')
                ->join($join)
                ->join('left join fzm_parents p on o.parent_id=p.id')
                ->where($where)
                ->count();
            $page = $this->page($count, 15);
            $list = $db->alias('o')
                ->join($join)
                ->join('left join fzm_parents p on o.parent_id=p.id')
                ->limit($page->firstRow,$page->listRows)
                ->where($where)
                ->order('o_time desc')
                ->select();
            $money += $db->alias('o')
                ->join($join)
                ->join('left join fzm_parents p on o.parent_id=p.id')
                ->where($where)
                ->sum('money')??0;
        }else{
            //线上课堂
            if (I('start') && I('end')){
                $where['o.o_time'] = ['between', [I('start'), I('end')]];
            }
            $where['o.status'] = 2;
            $where['a.tid'] = $small_id;
            $where['a.u_type'] = 2;
            $count = D("OnlineClassOrder")->alias('o')
                ->join("LEFT JOIN fzm_online_class a on a.id = o.oc_id")
                ->where($where)
                ->count();
            $page = $this->page($count, 15);
            $list = D("OnlineClassOrder")->alias('o')
                ->join("LEFT JOIN fzm_online_class a on a.id = o.oc_id")
                ->where($where)
                ->field('o.*,a.title')
                ->limit($page->firstRow,$page->listRows)
                ->order('o.o_time desc')
                ->select();
            $money += D("OnlineClassOrder")->alias('o')
                ->join("LEFT JOIN fzm_online_class a on a.id = o.oc_id")
                ->where($where)
                ->sum('o.money')??0;
        }
        $money ==0?$money=0:$money==$money;
        $this->assign('money',$money);
        $this->assign('list',$list);

        $find = M('Application')->where(array('uid'=>$small_id,'status'=>1,'type'=>2, 'u_type'=>3))->find();
        if($find){
            $type = 1;   //不可以
        }else{
            $type = 2;    //可以
        }
        $this->assign('type', $type);
        $this->assign("page", $page->show('Admin'));

        $this->display();
    }

    /**
     * 提现
     * sunfan
     * 2018.4.10
     */
    public function withdraw()
    {
        $small_id = $_SESSION['small_id'];
        $info = M('small_table')->where(array('id'=>$small_id))->find();
        $this->assign('info',$info);

        $list =  M('Bank')->where(array('st_id'=>$small_id))->select();
        $this->assign('list', $list);

        $this->display();
    }

    //提现申请提交
    public function apply()
    {
        $data = $_POST;
        $id = $_SESSION['small_id'];
        $card_id = $data['card_id'];
        $config = M('Config')->find();
        $find = M('SmallTable')->where(array('id'=>$id))->find();

        if($find['balance'] < $config['quota']){
            $this->error('您的余额不满足最低提现额度');
        }

        if($find['balance'] < $data['money']){
            $this->error('余额不足，不能提现');
        }

        //验证短信
        $data_code['phone'] = $find['s_phone'];
        $data_code['code'] = $data['code'];
        $phone_code = M('PhoneCode')->where($data_code)->find();
        if (!$phone_code || time() > $phone_code['createtime']+60*5){
            $this->error("短信验证码不正确或已超时");
        }
        M('PhoneCode')->where($data_code)->delete();
        $t_type = I("post.t_type");
        if($t_type == 1){
            $card = M('Bank')->where(['st_id'=>$id, 'id'=>$card_id])->find();
        }else{
            $card['name'] = $_POST['card_name'];
            $card['card'] = $_POST['number'];
            $card['phone'] = $data_code['phone'];
        }

        $add = M('Application')->add([
            'uid'=>$id,
            'u_type'=>3,
            'money'=>$data['money'],
            'a_time'=>date('Y-m-d H:i:s'),
            'number'=>$card['card'],
            'status'=>1,
            'type'=>2,
            'card_name'=>$card['name'],
            'address'=>$card['address'],
            'phone'=>$card['phone'],
            'sn'    =>  'W'.sp_get_order_sn(),
            't_type'=>$t_type
        ]);
        //冻结金额
        $other = $find['balance']- $data['money'];
        $freeze = M('SmallTable')->where(array('id'=>$id))->save(array('balance'=>$other,'freeze'=>$data['money']));
        if(!$add){
            $this->error('提交失败');
        }
        $this->success('提交成功', U('Wallet/index',['type'=>1]));
    }
}