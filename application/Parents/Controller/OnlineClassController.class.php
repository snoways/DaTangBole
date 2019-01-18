<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/25 0025
 * Time: 上午 10:43
 */

namespace Parents\Controller;


use Client\Controller\UserApiBaseController;
use Think\Exception;

class OnlineClassController extends UserApiBaseController
{
    /**
     * 线上课堂视频
     * jizhongbao
     */

    public function index()
    {
        $data = $this->data;
        $info = [];
        try {
            $where['m.status'] = 1;
            $model = M("OnlineClass");
            $page = $data['page']??1;
            //热门
            if ($data['type'] == 1) $where['is_popular'] = 1;
            //付费
            if ($data['type'] == 2) $where['price_type'] = 1;
            //免费
            if ($data['type'] == 3) $where['price_type'] = 0;
            //代理商id
            if (!empty($data['tid'])) $where['tid'] = $data['tid'];

            //精品课堂 1精品 2普通
            if (!empty($data['quality'])) $where['hot'] = $data['quality'];

            //科目筛选
            if (!empty($data['subject']) &&
                $data['subject'] != "全部") $where['m.t_sub1|m.t_sub2|m.t_sub3'] = ['like', "%".$data['subject']."%"];
            //关键字筛选
            if (!empty($data['keyword']))  $where['m.title'] = ['like', "%".$data['keyword']."%"];
            if (!empty($data['age_id']))  $where['m.age_id'] = $data['age_id'];
            //排序相关
            $order = [];
            $order['m.sort'] = 'ASC';
            //销量排序
            if ($data['order']==1) $order['sale_num'] = 'DESC';
            //价格从高到低
            if ($data['order']==2) $order['price'] = 'DESC';
            //价格从低到高
            if ($data['order']==3) $order['price'] = 'ASC';
            //人气从高到低
            if ($data['order']==4) $order['view'] = 'DESC';
            $order['m.oc_time'] = 'desc';
            $field='m.id as olid, m.title, m.img_url, m.view, u_type, m.price, m.sale_num as sale, tid';
            $info = $model->alias('m')
                ->order($order)
                ->where($where)
                ->page($page)
                ->field($field)
                ->select();
            foreach ($info as &$item){
                if ($item['u_type']==2) {    //用户类型 1.老师 2.商户 3.后台
                    //商户
                    $shopInfo = M('SmallTable')->where(['id'=>$item['tid']])->field('s_name,s_img')->find();
                    $item['t_name'] = $shopInfo['s_name'];
                    $item['t_img'] = $shopInfo['s_img'];
                    unset($shopInfo);
                } else {
                    //平台
                    $item['t_name'] = M('Config')->getField('class_name');
                    $item['t_img'] = "/public/images/img_new.png";
                }
            }
            $code = 0;
            $msg  = '';
            if (!empty($info)) {
                $code = 1;
            }
        } catch (Exception $e) {
            $code = -1;
            $msg  = $e->getMessage();
        }
        $this->ApiReturn($code, $msg, $info);
    }

    //获取年龄筛选
    public function get_age()
    {
        $code = 0;
        $msg = 'success';
        $info = M("ages")->field('id,min,max')->order('id')->select();
        if ($info) {
            $code = 1;
            foreach ($info as &$item) {
                if ($item['max'] == 0) {
                    $item['title'] = $item['min'].'岁以上';
                } else {
                    $item['title'] = $item['min'].'-'.$item['max'].'岁';
                }
                unset($item['min']);
                unset($item['max']);
            }
            array_unshift($info,['id'=>0,'title'=>'全部']);
        }
        $this->ApiReturn($code, $msg, $info);
    }
    /**
     * 轮播图
     */
    public function Carousel()
    {
        $where['position'] = 2;
        $list = M('Banner')->where($where)->field('url, type, item_id')->select();
        if (!empty($list)){
            $this->ApiReturn(1, 'success', $list);
        }else{
            $this->ApiReturn(0, 'success');
        }
    }

    public function detail()
    {
        $data = $this->data;
        $id = S($data['token']);
//        $where['status'] = 1;
        $where['id'] = $olid = $data['olid']??$this->ApiReturn(-1, 'id不能为空');
        $model = M("OnlineClass");
        $info = $model
            ->order('oc_time desc')
            ->where($where)
            ->field('id as olid, title,status, img_url, video_url, view, price, tid, oc_time, content, sale_num as sale, u_type, access_password,price_type')
            ->find();
        if ($info['status'] != 1){
            if ($info['price_type'] != 1){
                $this->ApiReturn(-1, '视频不存在或已下架');

            } else {
                if (!M('OnlineClassOrder')->where(['oc_id'=>$olid, 'parent_id'=>$id, 'status'=>2])->find()) {
                    $this->ApiReturn(-1, '视频不存在或已下架');
                }
            }
        }
        $info['accid'] = "s".$info['tid'];


        if ($info['u_type'] == 2){    //用户类型 2.商户 3.后台
            //商户
            $info['name'] = M('SmallTable')->where(['id'=>$info['tid']])->getField('s_name');
            $info['t_img'] = M('SmallTable')->where(['id'=>$info['tid']])->getField('s_img');
            $info['s_type'] = M('SmallTable')->where(['id'=>$info['tid']])->getField('s_type');
        } else {
            //平台
            $info['name'] = M('Config')->getField('class_name');
            $info['t_img'] = "/public/images/img_new.png";
        }
        $info['collection'] = 0;
        $online_class_collection = M('online_class_collection');
        $where = ['oc_id'=>$olid,'p_id'=>$id];
        if ($online_class_collection->where($where)->getField('id'))  $info['collection'] = 1;
        $field = 'id as olid, title, img_url, view, price, u_type, sale_num as sale, tid';
        $info['list'] = $model
            ->where(['tid'=>$info['tid'], 'u_type'=>$info['u_type'], 'id'=>['neq', $olid], 'status'=>1])
            ->field($field)
            ->limit(10)
            ->order('oc_time desc')
            ->select();

        foreach ($info['list'] as &$item){
            if ($item['u_type']==2){    //用户类型  2.商户 3.后台
                //商户
                $item['name'] = M('SmallTable')->where(['id'=>$item['tid']])->getField('s_name');
                $item['t_img'] = M('SmallTable')->where(['id'=>$item['tid']])->getField('s_img');
            }else{
                //平台
                $item['name'] = M('Config')->getField('class_name');
                $item['t_img'] = "/public/images/img_new.png";
            }
        }

        //检查一下视频是否是付费视频
        $info['buy'] = 1;//买过了
        if ($info['price_type'] == 1) {
            //检查一下是否购买过该视频
            $old_order = M('OnlineClassOrder')->where(['oc_id'=>$olid, 'parent_id'=>$id, 'status'=>2])->find();
            if (!$old_order) {

                $access_password1 = S('OnlineClass_'.$olid.'_'.$id);
                if (!$access_password1 || $access_password1 != $info['access_password']) {
                    $info['buy'] = -1;//没买过也没有用密码访问过
                    $info['video_url'] = '';
                }
            }
            //判断是否设置了密码
            if ($info['access_password']) {
                $info['access_password'] = '1'; //设置了密码
            } else {
                $info['access_password'] = '0';
            }
        }
        $evaluate = D("OnlineEvaluate");
        $info['evaluate_count'] = $evaluate->where(['c_id'=>$olid])->count();

        //增加浏览量
        M('OnlineClass')->where(['id'=>$olid])->setInc('view');

        $this->ApiReturn(1, 'success', $info);
    }

    //校验密码
    public function check_access_password()
    {

        $data = $this->data;
        $olid = $data['olid']??$data['olid']??$this->ApiReturn(-1,'缺少参数');;
        $id = S($data['token']);
        $access_password1 = $data['access_password']??$this->ApiReturn(-1,'缺少参数');
        $access_password2 = M("OnlineClass")->where(['id'=>$olid])->getField('access_password');
        if ($access_password1 == $access_password2) {
            S('OnlineClass_'.$olid.'_'.$id,$access_password1);
            //增加观看记录
            if (!M('ClassHistory')->where([
                'pid'   =>  $id,
                'oc_id' =>  $olid
            ])->find()){
                M('ClassHistory')->add([
                    'pid'   =>  $id,
                    'oc_id' =>  $olid,
                    'cw_time'=> date('Y-m-d H:i:s')
                ]);
            }
            $this->ApiReturn(1,'success');
        } else {
            $this->ApiReturn(-1,'error');
        }
    }

    //评论列表
    public function evaluate_list()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $evaluate = D("OnlineEvaluate");
        $where['o.c_id'] = $olid = $data['olid']??$this->ApiReturn(-1, 'id不能为空');
        $list = $evaluate->alias('o')
            ->join("LEFT JOIN fzm_parents p on o.uid = p.id")
            ->limit(($page-1)*10,10)
            ->order("o.create_time desc")
            ->where($where)
            ->field('o.create_time,o.content,p.parent_name,p.p_img')
            ->select();
        if(empty($list)){
            $this->ApiReturn(0,'无数据');
        }else{
            $this->ApiReturn(1,'成功',$list);
        }

    }
    //添加线上课堂评价
    public function online_evaluate()
    {
        $data = $this->data;
        $id = S($data['token']);
        $olid = $data['olid']??$this->ApiReturn(-1, 'id不能为空');
        $content = $data['content']??$this->ApiReturn(-1,'评论内容不能为空！');
        $info = D("OnlineEvaluate")->add([
            'uid'       =>      $id,
            'c_id'      =>      $olid,
            'content'   =>      $content,
            'create_time'=>     date("Y-m-d H:i:s")
        ]);
        if($info){
            $this->ApiReturn(1,'评论成功');
        }else{
            $this->ApiReturn(-1,'评论失败');
        }


    }

    //举报视频
    public function reportClass()
    {
        $data = $this->data;
        $id = S($data['token']);
        $where['id'] = $data['olid']??$this->ApiReturn(-1, 'id不能为空');
        $model = M("OnlineClass");
        $info = $model->where($where)->setInc('report_num');
        $this->ApiReturn(1, '举报成功');
    }

    //购买视频
    public function addOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $olid = $data['olid']??$this->ApiReturn(-1, 'id不能为空');
        $coupon_id = $data['cr_id'];//优惠券id

        $class = M('OnlineClass')->where(['id'=>$olid])->find();
        if (!$class)$this->ApiReturn(-1, '视频课程异常，请选择其他课程');

        //如果有相同的会员等级，未支付的订单，用那个，否则生成一个新的
        $old_order = M('OnlineClassOrder')->where(['oc_id'=>$olid, 'parent_id'=>$id])->find();
        if ($old_order){
            if ($old_order['status']==1){
//                $oid = $old_order['id'];
                M('OnlineClassOrder')->where(['id'=>$old_order['id']])->delete();
            }else{
                $this->ApiReturn(-1, '您已购买过此课程');
            }
        }
        $total = $class['price'];
        $coupon_money = 0.00;

        //使用优惠券
        if (!empty($coupon_id)){
            $coupon_money = $this->useCoupon($id, 5, $total, $coupon_id);
            $total -= $coupon_money;
        }
        $money = $total;
        $platform_money = 0.00;
        //如果是平台订单
        if ($class['u_type'] == 3) {
            $st_id = M('parents')->where(['id'=>$id])->getField('st_id');
            //有直属代理商上级
            if ($st_id > 0) {
                $kecheng_ratio = M('direct_agent_ratio')->where(['agent_id'=>$st_id])->getField('kecheng_ratio');
                if ($kecheng_ratio > 0) {
                    $platform_money = sprintf('%.2f',$total * $kecheng_ratio / 100); //平台应得的钱
                    if ($platform_money < $money && $platform_money > 0) {
                        $money = $total - $platform_money; //商户实际应得的钱
                    }
                }
            }
        } else {
            $kecheng_ratio = M('smalltable_ratio')->where(['agent_id'=>$class['tid']])->getField('kecheng_ratio');
            if ($kecheng_ratio > 0) {
                $platform_money = sprintf('%.2f',$total * $kecheng_ratio / 100); //平台应得的钱
                if ($platform_money < $money && $platform_money > 0) {
                    $money = $total - $platform_money; //商户实际应得的钱
                }
            }

        }

        $order_sn = "O";
        $order_sn .= sp_get_order_sn();

        $o_time = date('Y-m-d H:i:s');
        $oid = M('OnlineClassOrder')->add([
            'sn'            =>  $order_sn,
            'tid'           =>  $class['tid'],
            'u_type'        =>  $class['u_type'],       //用户类型2.商户 3.后台
            'oc_id'         =>  $olid,
            'parent_id'     =>  $id,
            'total_money'   =>  $total,
            'platform_money'=>  $platform_money,
            'money'         =>  $money,
            'coupon_money'  =>  $coupon_money??0.00,
            'cr_id'         =>  $coupon_id,
            'o_time'        =>  $o_time,
        ]);

        //增加观看记录
        if (!M('ClassHistory')->where([
            'pid'   =>  $id,
            'oc_id' =>  $olid
        ])->find()){
            M('ClassHistory')->add([
                'pid'   =>  $id,
                'oc_id' =>  $olid,
                'cw_time'=> date('Y-m-d H:i:s')
            ]);
        }

        if ($oid){
            $order = M('OnlineClassOrder')->where(['id'=>$oid])->find();
            $rs['sn'] = $order['sn'];
            $rs['title'] = $class['title'];
            $rs['o_time'] = $order['o_time'];
            $rs['total_money'] = $order['total_money'];
            $this->ApiReturn(1, 'success', $rs);
        }else{
            $this->ApiReturn(-1, '失败', ['kefu_tel'=>M('config')->getField('kefu_tel')]);
        }
    }

    //添加观看记录
    public function addHistory()
    {
        $data = $this->data;
        $id = S($data['token']);
        $olid = $data['olid']??$this->ApiReturn(-1, 'id不能为空');
        if (!M('ClassHistory')->where([
            'pid'   =>  $id,
            'oc_id' =>  $olid
        ])->find()){
            M('ClassHistory')->add([
                'pid'   =>  $id,
                'oc_id' =>  $olid,
                'cw_time'=> date('Y-m-d H:i:s')
            ]);
        }
        $this->ApiReturn(1, 'success');
    }

    /**
     * 观看记录
     * sunfan
     */
    public function watchHistory()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = !empty($data['page'])?$data['page']:1;
        $where['pid'] = $id;
        $type = !empty($data['type'])?$data['type']:1;

        $field = 'ch.id as ch_id, ch.cw_time, oc.id as oc_id, oc.img_url, oc.title, oc.view, oc.price, oc.status, tid, u_type';
        if ($type==2){
            //付费
            $where['oc.price_type'] = 1;
        }else{
            //免费
            $where['oc.price'] = 0;
        }

        $list = M('ClassHistory')
            ->alias('ch')
            ->join('LEFT JOIN fzm_online_class oc ON oc.id=ch.oc_id')
//            ->join('LEFT JOIN fzm_teacher t ON oc.tid=t.id')
            ->where($where)
            ->order('cw_time desc')
            ->page($page)
            ->field($field)
            ->select();

        if (!$list){
            $this->ApiReturn(0, 'success');
        }else{

            foreach ($list as $key=>&$item){
                if ($item['u_type']==2){    //用户类型 2.商户 3.后台
                    //商户
                    $item['t_name'] = M('SmallTable')->where(['id'=>$item['tid']])->getField('s_name');
                    $item['t_img'] = M('SmallTable')->where(['id'=>$item['tid']])->getField('s_img');
                }else{
                    //平台
                    $item['t_name'] = M('Config')->getField('class_name');
                    $item['t_img'] = "/public/images/img_new.png";
                }
                if ($type == 1 && $item['status'] != 1) {
                    unset($list[$key]);
                }
                if ($type == 2 && !M('OnlineClassOrder')->where(['oc_id'=>$item['oc_id'], 'parent_id'=>$id, 'status'=>2])->find()) {
                    unset($list[$key]);
                }
            }
            $this->ApiReturn(1, 'success', $list);
        }
    }

    /**
     * 删除观看记录
     * sunfan
     */
    public function delHistory()
    {
        $data = $this->data;
        $id = S($data['token']);
        $ch_ids = !empty($data['ch_ids'])?$data['ch_ids']:$this->ApiReturn(-1, '请选择要删除的记录');
        $pro = M('ClassHistory');
        $del = $pro->where(['id'=>['in', $ch_ids], 'pid'=>$id])->delete();
        $this->ApiReturn(1, '删除成功');
    }

//    /**
//     * 二次确认课堂价格、及会员级别
//     */
//    public function checkPrice()
//    {
//        $data = $this->data;
//        $id = S($data['token']);
//        $model = M("OnlineClass");
//        $where['m.status'] = ['neq', 4];
//        $where['m.id'] = $olid = $data['olid']??$this->ApiReturn(-1, 'id不能为空');
//        $field='m.id as olid, m.title, m.img_url, m.view, m.price, t.name, t_img';
//        $info = $model->alias('m')
//            ->join("LEFT JOIN fzm_teacher t on t.id = m.tid")
//            ->order('oc_time desc')
//            ->where($where)
//            ->field($field)
//            ->find();
//        $info['level'] = $parent['level_id'];
//        $this->ApiReturn(1, 'success', $info);
//    }

    /**
     * 支付失败--删订单、退优惠券
     * sunfan
     * 2018.8.3
     */
    public function payFailed()
    {
        $data = $this->data;
        $sn = $data['sn']??$this->ApiReturn(-1, '订单号不能为空');
        $order = M('OnlineClassOrder')->where(['sn'=>$sn])->find();
        if (!$order){
            $this->ApiReturn(-1, '订单不存在');
        }
        if ($order['status']!=1){
            $this->ApiReturn(-1, '订单已支付');
        }
        if (!empty($order['cr_id'])){
            M('CouponRecords')->where(['id'=>$order['cr_id'], 'uid'=>$order['parent_id']])->save(['cr_status'=>1]);
        }
        M('OnlineClassOrder')->where(['sn'=>$sn])->delete();
    }


    //课堂搜藏
    public function collection()
    {
        $data = $this->data;
        $uid = S($data['token'])?S($data['token']):$this->ApiReturn(2003,'token无效或已过期');
        $id = $data['id']?$data['id']:$this->ApiReturn(-1,'缺少课堂');
        $online_class_collection = M('online_class_collection');
        $where = ['oc_id'=>$id,'p_id'=>$uid];
        if ($id = $online_class_collection->where($where)->getField('id')) {
            if ($online_class_collection->delete($id)) {
                $this->ApiReturn(1,'取消成功');
            } else {
                $this->ApiReturn(-1,'取消失败');
            }
        } else {
            if ($online_class_collection->add($where)) {
                $this->ApiReturn(1,'收藏成功');
            } else {
                $this->ApiReturn(-1,'收藏失败');
            }
        }
    }

    /*
     * 线上课堂收藏列表
     */
    public function collection_list()
    {
        $data = $this->data;
        $id = S($data['token'])??$this->ApiReturn(2003,'token无效或已过期');
        $page = $data['page']?$data['page']:1;
        $online_class_collection = M('online_class_collection');
        $where = ['occ.p_id'=>$id,'ac.status'=>1,'ac.state'=>2];
        $list = $online_class_collection
            ->alias('occ')
            ->join('LEFT JOIN __ONLINE_CLASS__ ac ON ac.id = occ.oc_id')
            ->field('ac.id olid,ac.title,ac.img_url,ac.view,ac.price,ac.sale_num sale,u_type,tid')
            ->where($where)
            ->page($page,8)
            ->order('occ.id desc')
            ->select();
//        print_r($online_class_collection->getLastSql());die;
        if ($list) {
            foreach ($list as &$item){
                if ($item['u_type']==2) {    //用户类型  2.商户 3.后台
                    //商户
                    $shopInfo = M('SmallTable')->where(['id'=>$item['tid']])->field('s_name,s_img')->find();
                    $item['t_name'] = $shopInfo['s_name'];
                    $item['t_img'] = $shopInfo['s_img'];
                    unset($shopInfo);
                } else {
                    //平台
                    $item['t_name'] = M('Config')->getField('class_name');
                    $item['t_img'] = "/public/images/img_new.png";
                }
            }
            $this->ApiReturn(1,'success',$list);
        }
        $this->ApiReturn(0,'success',$list);
    }
}