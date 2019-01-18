<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/11/3
 * Time: 10:03
 */

namespace Parents\Controller;


use Client\Controller\UserApiBaseController;

class EducationalController extends UserApiBaseController
{

    /**
     * 教育机构列表
     * sunfan
     * 2017.11.3
     */
    public function index()
    {
        $data = $this->data;
        $page = $data['page']??1;
        $id = S($data['token']);
        $where['s_type'] = ['neq',4];
        $where['s_status'] = 2;
        $where['status'] = 1;
        if (!empty($data['keyword'])){
            $where['s_name|address|c_name|s_content|s_phone'] = ['like', '%'.$data['keyword'].'%'];
        }
        $order=[];
        //星级
        if (!empty($data['star'])) {
            $order['star'] = 'DESC';
        }
        if (!empty($data['subject'])) {
            $where['cate_id'] = ['like',"%,{$data['subject']},%"];
        }
        $list = M('SmallTable')
            ->where($where)
            ->page($page)
            ->field('id as st_id, s_name, s_img,s_type, address, c_name, s_content, s_phone, s_xy,view_num,star')
            ->order($order)
            ->select();

        $start = M('Parents')->where(['id'=>$id])->getField('p_xy');
        foreach ($list as $k=>$item)
        {
            $dis = getDistance($start, $item['s_xy']);
            $list[$k]['distance']=$dis['length'];
            $list[$k]['distance1']=$dis['length1'];
        }
        //距离
        if (!empty($data['distance'])) {
            array_multisort(array_column($list, 'distance1'), SORT_ASC, $list);
        }
        $this->ApiReturn(1, 'success', $list);
    }

//    public function index()
//    {
//        $data = $this->data;
//        $page = $data['page']??1;
//        $id = S($data['token']);
//        $where['s_type'] = 2;
//        $where['pid'] = $id;
//        if (!M('SmallTableSort')->where($where)->find()){
//            //如果这个人还没有数据，先拿别人的
//            $where['pid'] =  M('SmallTableSort')->where(['s_type'=>2])->getField('pid');
//        }
//        if (!empty($data['keyword'])){
//            $where['s_name|address|c_name|s_content|s_phone'] = ['like', '%'.$data['keyword'].'%'];
//        }
//        $order=[];
//        if (!empty($data['distance']))          //距离
//        {
//            $order['distance1'] = 'ASC';
//        }
//        if (!empty($data['star']))          //星级
//        {
//            $order['star'] = 'DESC';
//        }
//
//        if (!empty($data['subject']) && $data['subject']!="全部"){
//            if ($page!=1){
//                $this->ApiReturn(0, 'success');
//            }
//            $list = M('SmallTableSort')
//                ->where($where)
//                ->field('sid as st_id, s_name, s_img, address, c_name, s_content, s_phone, distance, star, view_num')
//                ->order($order)
//                ->select();
////            //科目筛选
//            if ($list)foreach ($list as $k=>$item){
//                if (!M('EducationalCourse')->where(['sub1|sub2'=>['like', "%".$data['subject']."%"], 'st_id'=>$item['st_id']])->find())
//                {
//                    unset($list[$k]);
//                    continue;
//                }
//            }
//        }else{
//            $list = M('SmallTableSort')
//                ->where($where)
//                ->page($page)
//                ->field('sid as st_id, s_name, s_img, address, c_name, s_content, s_phone, distance, star, view_num')
//                ->order($order)
//                ->select();
//        }
//        $list = array_values($list);
//
//        if (!$list){
//            $this->ApiReturn(0, 'success', $list);
//        }else{
//            $this->ApiReturn(1, 'success', $list);
//        }
//    }

    /**
     * 二期新增-
     * 教育机构详情
     * sunfan
     * 2018.5.2
     */
    public function detail()
    {
        $data = $this->data;
        $uid = S($data['token']);
        $id = $data['st_id']??$this->ApiReturn(-1, 'id不能为空');
        $rs = M('SmallTable')
            ->where(['id'=>$id])
            ->field('id as st_id, s_img, level_id, star, view_num, address, s_name, s_phone, s_xy, introduction, s_content')
            ->find();

        if (!$rs){
            $this->ApiReturn(-1, '托管异常：');
        }

        //会员等级图标
        $rs['icon'] = M('Level')->where(['id'=>$rs['level_id']])->getField('icon');

        //收藏
        $rs['collection'] = -1;
        if (M('Collection')->where(['parent_id'=>$uid, 'item_id'=>$id])->find())
        {
            $rs['collection'] = 1;
        }

        //视频
            $rs['video'] = M('SmallVideo')->where(['st_id' => $id, 'status' => 1])->order('is_top desc')->limit(1)->field('id as vid, img_url')->select();

        //形象展示
        $rs['imgs'] = M('SmallImages')->where(['st_id'=>$id])->field('url')->order('sort')->select();

        //增加一条访客记录
        $this->addVisitor($uid, $id, 2);

        //增加浏览量
        M('SmallTable')->where(['id'=>$id])->setInc('view_num');
        M('SmallTableSort')->where(['sid'=>$id])->setInc('view_num');

        //云信id
        $rs['accid'] = "s".$rs['st_id'];


        $circle = D("circle");
        //亲子时光
        $where['uid'] = $id;
        $where['u_type'] = 3;
        $where['option'] = 1;
        $rs['crafts'] = $circle->where($where)->count();

        //校园日记
        $sql['uid'] = $id;
        $sql['u_type'] = 3;
        $sql['option'] = 2;
        $rs['campus'] = $circle->where($sql)->count();

        //线上课堂
//        if ($id=471){
//            $rs['online_list']=[];
//        }else{
            $online_class = D("OnlineClass");
            $on['u_type'] = 2;
            $on['status'] = ['NOT IN',[3,2]];
            $on['tid'] = $id;
            $rs['online_list'] = $online_class->where($on)->count();
//        }

        //师资列表
        $sma['st_id'] = $id;
        $rs['small_teacher'] = M("small_teacher")->where($sma)->count();
        $rs['small_teacher_list'] = M("small_teacher")->limit(4)->order('top desc')->field('id teacher_id,name,desc,img')->where($sma)->select();

        //优惠券
        $rs['coupon'] = M('Coupon')->where(['u_id'=>$id])->order('create_time desc')->field('money, min_consumption')->find();

        $this->ApiReturn(1, 'success', $rs);
    }

    /**
     * 托管师资列表
     * sunfan
     * 2018.7.24
     */
    public function smallTeacherList()
    {
        $data = $this->data;
        $uid = S($data['token']);
        $id = $data['st_id']??$this->ApiReturn(-1, 'id不能为空');
        $rs = M('SmallTable')
            ->where(['id'=>$id])
            ->field('id as st_id, s_img, level_id, star, view_num, address, s_name, s_phone, s_xy, introduction, s_content')
            ->find();
        if (!$rs){
            $this->ApiReturn(-1, '托管异常');
        }

        //云信id
        $rs['accid'] = "s".$rs['st_id'];

        //会员等级图标
        $rs['icon'] = M('Level')->where(['id'=>$rs['level_id']])->getField('icon');

        //收藏
        $rs['collection'] = -1;
        if (M('Collection')->where(['parent_id'=>$uid, 'item_id'=>$id])->find())
        {
            $rs['collection'] = 1;
        }
        //师资列表
        $sma['st_id'] = $id;
        $rs['small_teacher_list'] = M("small_teacher")->order('top desc')->field('id teacher_id,name,desc,img')->where($sma)->select();

        $this->ApiReturn(1, 'success', $rs);
    }

    public function smallTeacherDetail()
    {
        $data = $this->data;
        $uid = S($data['token']);
        $id = $data['st_id']??$this->ApiReturn(-1, 'id不能为空');
        $teacher_id = $data['teacher_id']??$this->ApiReturn(-1, 'id不能为空');
        $rs = M('SmallTable')
            ->where(['id'=>$id])
            ->field('id as st_id, s_phone')
            ->find();
        if (!$rs){
            $this->ApiReturn(-1, '托管异常');
        }

        //云信id
        $rs['accid'] = "s".$rs['st_id'];

        //收藏
        $rs['collection'] = -1;
        if (M('Collection')->where(['parent_id'=>$uid, 'item_id'=>$id])->find())
        {
            $rs['collection'] = 1;
        }
        //师资列表
        $sma['st_id'] = $id;
        $sma['id'] = $teacher_id;
        $rs['small_teacher_list'] = M("small_teacher")->field('id teacher_id,name,desc,img, content')->where($sma)->find();

        $this->ApiReturn(1, 'success', $rs);
    }

    /**
     * 二期增加
     * 教育机构订单
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
            $where['eo.status'] = $data['status'];
        }
        $orders = M('EducationalOrder')
            ->alias('eo')
            ->join('LEFT JOIN fzm_small_table st ON st.id = eo.st_id')
            ->join('LEFT JOIN fzm_educational_course ec ON ec.id = eo.ec_id')
            ->where($where)
            ->field('eo.id as oid, sn as order_sn, eo.status, total_money, subject, class_num, ec.img, ec.title, tui_price, no_class, o_time, ec_id')
            ->order('o_time DESC')
            ->page($page)
            ->select();
        foreach ($orders as $k=>$order)
        {
            $orders[$k]['tui_status'] = -1;
            $orders[$k]['assess'] = -1;
            if ($order['status']==5)
            {
                $orders[$k]['tui_status'] = M('Application')->where(['oid'=>$order['oid'], 'o_type'=>2])->getField('status');
            }
            if ($order['status']==3)
            {
                if (M('Assess')->where(['order_id'=>$order['oid'],'type'=>2])->find())
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
     * 教育机构订单--详情页
     * sunfan
     * 2018.5.7
     */
    public function getOrderDetail()
    {
        $data = $this->data;
        $id = S($data['token']);
        $where = ['parent_id'=>$id];
        !empty($data['oid'])?$where['eo.id'] = $data['oid']:$this->ApiReturn(-1, '请选择要查看的订单');

        $orders = M('EducationalOrder')
            ->alias('eo')
            ->join('LEFT JOIN fzm_small_table st ON st.id = eo.st_id')
            ->join('LEFT JOIN fzm_educational_course ec ON ec.id = eo.ec_id')
            ->where($where)
            ->field('eo.id as oid, sn as order_sn, eo.status, total_money, subject, class_num, ec.img, ec.title, o_time, tui_price, no_class, ec_id , ec.refunds coupon_refund')
            ->find();
        $orders['tui_status'] = -1;
        if ($orders['status']==5)
        {
            $tui = M('Application')->where(['oid'=>$orders['oid'], 'o_type'=>2])->find();
            $orders['tui_status'] = $tui['status'];
            $orders['t_reason'] = $tui['t_reason'];  //申请退款原因
            $orders['b_reason'] = $tui['reason'];  //退款驳回原因
        }

        $orders['assess'] = -1;  //未评价
        if ($orders['status']==3)
        {
            if (M('Assess')->where(['order_id'=>$orders['oid'],'type'=>2])->find())
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
     * 教育机构订单--申请售后
     * sunfan
     * 2018.5.7
     */
    public function refundOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $where = ['parent_id'=>$id];
        !empty($data['oid'])?$where['id'] = $data['oid']:$this->ApiReturn(-1, '请选择要退款的订单');
        $order = M('EducationalOrder')->where($where)->find();
        $class_num = $data['class_num']??$this->ApiReturn(-1, '退款课时不能为空');
        $reason = $data['reason']??$this->ApiReturn(-1, '退款原因不能为空');
        if (!$order){$this->ApiReturn(-1, '订单异常');}
        if ($order['status']!=2){$this->ApiReturn(-1, '订单状态不允许退款');}

        $course = M('EducationalCourse')->where(['id'=>$order['ec_id']])->find();
        $num = $course['class']-$class_num;//上了xx节课
        if ($num>$course['no_class']){
            $this->ApiReturn(-1, '超过可退款课时');
        }

        $money = $class_num*$course['tui_price'];
        M('EducationalOrder')->where($where)->save(['status'=>5, 'refunds_money'=>$money]);

        M('Application')->add([
            'uid'   =>  $id,
            'u_type'=>  1,
            'money' =>  $money,
            'a_time'=>  date('Y-m-d H:i:s'),
            'type'  =>  1,
            't_reason'=>$reason,
            'oid'   =>$order['id'],
            'o_type'=>2,    //1.老师课程订单 2.教育机构订单 3.托管订单
        ]);
        $this->ApiReturn(1, '申请成功，请等待工作人员处理');
    }

    /**
     * 二期增加
     * 教育机构订单--取消订单
     * sunfan
     * 2018.5.7
     */
    public function cancelOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $oid = $data['oid']??$this->ApiReturn(-1, '订单id不能为空');
        $order = M('EducationalOrder')->where(['id'=>$oid, 'parent_id'=>$id])->find();
        if (!$order){
            $this->ApiReturn(-1, '订单异常');
        }
        if ($order['status']>1){
            $this->ApiReturn(-1, '订单状态不允许取消');
        }
        M('EducationalOrder')->where(['id'=>$oid])->save(['status'=>4]);
        $this->ApiReturn(1, '取消成功');
    }

    /**
     * 二期增加
     * 教育机构订单--完成
     * sunfan
     * 2018.5.7
     */
    public function finishOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $oid = $data['oid']??$this->ApiReturn(-1, '订单id不能为空');
        $order = M('EducationalOrder')
            ->where(['id'=>$oid, 'parent_id'=>$id])
            ->find();
        if (!$order){
            $this->ApiReturn(-1, '订单异常');
        }
        if ($order['status']!=2){
            $this->ApiReturn(-1, '订单状态异常');
        }
        M('EducationalOrder')->where(['id'=>$oid])->save(['status'=>3]);
        $this->ApiReturn(1, '操作成功');
    }

}