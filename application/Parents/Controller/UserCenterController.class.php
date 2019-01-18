<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/11/6
 * Time: 9:12
 */

namespace Parents\Controller;


use Client\Controller\UserApiBaseController;

class UserCenterController extends UserApiBaseController
{

    /**
     * 个人中心首页
     * sunfan
     * 2017.11.13
     */
    public function info()
    {
        $data = $this->data;
        $id = S($data['token']);
        $rs = M('Parents')->where(['id'=>$id])->find();
        if(empty($rs)){
            $this->ApiReturn(-1,'用户不存在');
        }
        $rs['p_img'] = $rs['p_img']??"/public/images/headicon.png";
        $rs['code'] = 0;
        $result = $this->timeMoney($id);
        $rs['money'] = $result['money'];
        $rs['week_money'] = $result['week_money'];
        $this->ApiReturn(1,'success',$rs);
    }
    private function timeMoney($id){

        //当前日期
        /*$sdefaultDate = date("Y-m-d");
//$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
        $first=1;
//获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        $w=date('w',strtotime($sdefaultDate));
//获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $week_start=date('Y-m-d',strtotime("$sdefaultDate -".($w ? $w - $first : 6).' days')).'00:00:00';
//本周结束日期
        $week_end=date('Y-m-d',strtotime("$week_start +6 days")).'23:59:59';*/

        $BeginDate=date('Y-m-01', strtotime(date("Y-m-d")))." 00:00:00";
        $EndDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"))." 23:59:59";
        //本周消费金额
        $week_money = 0;
        $week_money += M("order")->where(['parent_id'=>$id,'status'=>['in',[2,3]],'start_time'=>['between',[$BeginDate,$EndDate]]])->sum('money');
        $week_money += M("EducationalOrder")->where(['parent_id'=>$id,'status'=>['in',[2,3]],'o_time'=>['between',[$BeginDate,$EndDate]]])->sum("total_money");
        $week_money += M("HostingOrder")->where(['parent_id'=>$id,'status'=>['in',[2,3]],'o_time'=>['between',[$BeginDate,$EndDate]]])->sum('total_money');
        $week_money += M("ActivityOrder")->where(['uid'=>$id,'status'=>2,'sign_time'=>['between',[$BeginDate,$EndDate]]])->sum('pay_money');
        $week_money += M("OnlineClassOrder")->where(['parent_id'=>$id,'status'=>2,'o_time'=>['between',[$BeginDate,$EndDate]]])->sum("total_money");
        $week_money += M("VipOrder")->where(['uid'=>$id,'u_type'=>1,'status'=>2,'o_time'=>['between',[$BeginDate,$EndDate]]])->sum("money");

        //共消费金额
        $money = 0;
        $money += M("order")->where(['parent_id'=>$id,'status'=>['in',[2,3]]])->sum('money');
        $money += M("EducationalOrder")->where(['parent_id'=>$id,'status'=>['in',[2,3]]])->sum("total_money");
        $money += M("HostingOrder")->where(['parent_id'=>$id,'status'=>['in',[2,3]]])->sum('total_money');
        $money += M("ActivityOrder")->where(['uid'=>$id,'status'=>2])->sum('pay_money');
        $money += M("OnlineClassOrder")->where(['parent_id'=>$id,'status'=>2])->sum("total_money");
        $money += M("VipOrder")->where(['uid'=>$id,'u_type'=>1,'status'=>2])->sum("money");

        $arr = [
            'week_money'    =>  sprintf('%.2f',$week_money),
            'money'         =>  sprintf('%.2f',$money)
        ];
        return $arr;
    }

    /**
     * 我的课程
     * sunfan
     * 2017.11.6
     */
    public function orders()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $where = ['parent_id'=>$id];
        if (!empty($data['status']))
        {
            $where['fzm_order.status'] = $data['status'];
        }
        $orders = M('Order')
            ->join('LEFT JOIN fzm_teacher t ON t.id = fzm_order.teacher_id')
            ->where($where)
            ->field('fzm_order.id as oid, sn as order_sn, class_num, now_num, fzm_order.status, total_money, start_time, subject, o_price, t_img, t_type, teacher_id')
            ->order('start_time DESC')
            ->page($page)
            ->select();
        foreach ($orders as $k=>$order)
        {
            $orders[$k]['tui_status'] = -1;
            $orders[$k]['assess'] = -1;
            if ($order['status']==5)
            {
                $orders[$k]['tui_status'] = M('Application')->where(['oid'=>$order['oid'], 'o_type'=>1])->getField('status');
            }
            if ($order['status']!=3)
            {
                //订单进行中 返回当前这节课的状态 1.家长未确认 2.家长确认 老师未评价 3.老师已评价
                $orders[$k]['check'] = 1;
                $comment = M('OrderComments')->where(['order_id'=>$order['oid'], 'num'=>$order['now_num']])->find();
                if ($comment){
                    if ($comment['status']==1)
                    {
                        $orders[$k]['check'] = 2;
                    }else{
                        $orders[$k]['check'] = 3;
                    }
                }
            }

            if ($order['status']==3)
            {
                if (M('Assess')->where(['order_id'=>$order['oid'],'type'=>1])->find())
                {
                    $orders[$k]['assess'] = 1;
                }
            }
        }
        if (!$orders){$this->ApiReturn(0, '没有数据');}

        $this->ApiReturn(1, 'success', $orders);
    }
    
    /**
     * 取消订单
     * sunfan
     * 2017.11.6
     */
    public function cancleOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $order_sn = $data['order_sn']??$this->ApiReturn(-1, '订单编号不能为空');
        $order = M('Order')->where(['sn'=>$order_sn, 'parent_id'=>$id])->find();
        if (!$order){
            $this->ApiReturn(-1, '订单异常');
        }
        if ($order['status']>1){
            $this->ApiReturn(-1, '订单状态不允许取消');
        }
        M('Order')->where(['id'=>$order['id']])->save(['status'=>4]);
        M('TeacherTime')->where(array('id'=>$order['teacher_time_id']))->save(array('status'=>1));
        $this->ApiReturn(1, '取消成功');
    }

    /**
     * 申请售后
     * sunfan
     * 2017.11.6
     */
    public function aftermarket()
    {
        $data = $this->data;
        $id = S($data['token']);
        $order_sn = $data['order_sn']??$this->ApiReturn(-1, '订单编号不能为空');
        $order = M('Order')->where(['sn'=>$order_sn, 'parent_id'=>$id])->find();
//        $money = $data['money']??$this->ApiReturn(-1, '退款金额不能为空');
        $reason = $data['reason']??"无";
        if (!$order){
            $this->ApiReturn(-1, '订单异常');
        }
        if ($order['status']<2){
            $this->ApiReturn(-1, '订单状态不支持申请售后');
        }
        if ($order['status']==5){
            $this->ApiReturn(-1, '订单已经申请过售后');
        }
        $num = $order['now_num'] - 1;
        /*$num = $order['class_num'] - $order['now_num'] + 1;
        $m = $num*$order['o_price'];
        if ($money>$m){
            $this->ApiReturn(-1, '超过最多可退款金额');
        }*/
        $money = $order['total_money']-(sprintf("%.2f", $order['total_money']/$order['class_num'])*$num);
        M('Order')->where(['id'=>$order['id']])->save(['status'=>5]);

        M('Application')->add([
            'uid'   =>  $id,
            'u_type'=>  1,
            'money' =>  $money,
            'a_time'=>  date('Y-m-d H:i:s'),
            'type'  =>  1,
            't_reason'=>$reason,
            'oid'=>$order['id']
        ]);
        $this->ApiReturn(1, '申请成功，请等待工作人员处理');
    }

    /**
     * 订单详情
     * sunfan
     * 2017.11.6
     */
    public function detail()
    {
        $data = $this->data;
        $id = S($data['token']);
        $order_sn = $data['order_sn']??$this->ApiReturn(-1, '订单编号不能为空');
        $order = M('Order')
            ->join('LEFT JOIN fzm_teacher t ON t.id=fzm_order.teacher_id')
            ->where(['sn'=>$order_sn, 'parent_id'=>$id])
            ->field('fzm_order.id as oid, fzm_order.teacher_id, sn as order_sn, class_num, now_num, fzm_order.status, total_money, start_time, subject, o_price, t_img, teach_date, teach_time, fzm_order.address,fzm_order.address_other, t_type, teacher_id')
            ->find();
        if (!$order){
            $this->ApiReturn(-1, '订单异常');
        }
        $order['tui_status'] = -1;
        if ($order['status']==5)
        {
            $tui = M('Application')->where(['oid'=>$order['oid']])->find();
            $order['tui_status'] = $tui['status'];
            $order['t_reason'] = $tui['t_reason'];  //申请退款原因
            $order['b_reason'] = $tui['reason'];  //退款驳回原因
        }

        $order['assess'] = -1;  //未评价
        if ($order['status']==3)
        {
            if (M('Assess')->where(['order_id'=>$order['oid'],'type'=>1])->find())
            {
                $order['assess'] = 1;
            }
        }

        if ($order['status']!=3)
        {
            //订单进行中 返回当前这节课的状态 1.家长未确认 2.家长确认 老师未评价 3.老师已评价
            $order['check'] = 1;
            $comment = M('OrderComments')->where(['order_id'=>$order['oid'], 'num'=>$order['now_num']])->find();
            if ($comment){
                if ($comment['status']==1)
                {
                    $order['check'] = 2;
                }else{
                    $order['check'] = 3;
                }
            }
        }

        $order['accid'] = "t".$order['teacher_id'];

        $this->ApiReturn(1, 'success', $order);
    }

    /**
     * 课节 - 确认完成
     * sunfan
     * 2017.11.6
     */
    public function finishClass()
    {
        $data = $this->data;
        $id = S($data['token']);
        $order_sn = $data['order_sn']??$this->ApiReturn(-1, '订单编号不能为空');
        $class = $data['class']??$this->ApiReturn(-1, '请选择确认完成哪节课');
        $order = M('Order')
            ->where(['sn'=>$order_sn, 'parent_id'=>$id])
            ->find();
        if (!$order){
            $this->ApiReturn(-1, '订单异常');
        }
        if ($class<$order['now_num']){
            $this->ApiReturn(-1, '该节课已完成');
        }
        if ($class>$order['now_num']){
            $this->ApiReturn(-1, '前面还有未完成的课节');
        }

        $ocid = M('OrderComments')->add([
            'order_id'      =>  $order['id'],
            'num'           =>  $class
        ]);

        if ($class==$order['class_num']){
            //订单完成
        }else{
//            M('Order')
//                ->where(['sn'=>$order_sn, 'parent_id'=>$id])
//                ->setInc('now_num');
        }
        $this->ApiReturn(1, '操作成功');
    }

    /**
     * 查看评语
     * sunfan
     * 2017.11.6
     */
    public function comment()
    {
        $data = $this->data;
        $id = S($data['token']);
        $order_sn = $data['order_sn']??$this->ApiReturn(-1, '订单编号不能为空');
        $class = $data['class']??$this->ApiReturn(-1, '请选择要查看的课节');
        $order = M('OrderComments')
            ->join('LEFT JOIN fzm_order o ON o.id=fzm_order_comments.order_id')
            ->where(['sn'=>$order_sn, 'parent_id'=>$id, 'num'=>$class])
            ->field('lesson_plan, comments')
            ->find();
        if (!$order){
            $this->ApiReturn(-1, '订单异常');
        }
        $this->ApiReturn(1, 'success', $order);
    }

    /**
     * 评价订单
     * sunfan
     * 2017.11.6
     */
    public function assess()
    {
        $data = $this->data;
        $id = S($data['token']);
        $order_sn = $data['order_sn']??$this->ApiReturn(-1, '订单编号不能为空');
        $order = M('Order')->where(['sn'=>$order_sn, 'parent_id'=>$id])->find();
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
        if (M('Assess')->where(['order_id'=>$order['id'], 'type'=>1])->find()){
            $this->ApiReturn(-1, '订单已评价');
        }
        $teacher = M('Teacher')->where(['id'=>$order['teacher_id']])->find();
        $score = round(($star+$teacher['score'])/2);
//        M('Teacher')->where(['id'=>$order['teacher_id']])->save(['score'=>$score, 'balance'=>$teacher['balance']+$order['money']]);
        M('Teacher')->where(['id'=>$order['teacher_id']])->save(['score'=>$score]);
        M('Assess')->add([
            'order_id'   =>  $order['id'],
            'content'=>  $content,
            'u_id' =>  $id,
            't_id' =>  $order['teacher_id'],
            'a_time'=>  date('Y-m-d H:i:s'),
            'star'  =>  $star,
            'status'=>  $status,
            'img1'=>  $img1,
            'img2'=>  $img2,
            'img3'=>  $img3,
            'type'=>1       //1家长（给老师的评价）  2.家长给机构课程的评价 3.家长给托管课程的评价
        ]);
        $this->ApiReturn(1, '评价成功');
    }

    /**
     * 我的收藏--收藏的老师
     * sunfan
     * 2017.11.6
     */
    public function collectionTeacher()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $where = ['parent_id'=>$id];
        $where['type'] = 1;
        $list = M('Collection')
            ->join('LEFT JOIN fzm_teacher ON fzm_teacher.id=fzm_collection.item_id')
            ->where($where)
            ->page($page)
            ->order('c_time DESC')
            ->field('fzm_collection.id as collection_id, fzm_teacher.id as t_id, name, sex, age, t_img, t_grade, t_sub2 as subject, idcard1, idcard2, hand_card, academic, t_card, price, score')
            ->select();
        if (!$list){
            $this->ApiReturn(0, '没有收藏');
        }
        $this->ApiReturn(1, 'success', $list);
    }

    /**
     * 我的收藏--收藏的托管
     * sunfan
     * 2017.11.6
     */
    public function collectionManaged()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $where = ['parent_id'=>$id];
        $where['type'] = 2;
        $list = M('Collection')
            ->join('LEFT JOIN fzm_small_table ON fzm_small_table.id=fzm_collection.item_id')
            ->where($where)
            ->page($page)
            ->order('c_time DESC')

            ->field('fzm_collection.id as collection_id, fzm_small_table.id as st_id, s_name, s_img, address, c_name, s_content, s_phone')
            ->select();
        if (!$list){
            $this->ApiReturn(0, '没有收藏');
        }
        $this->ApiReturn(1, 'success', $list);
    }

    /**
     * 收藏家长
     * sunfan
     * 2018.6.29
     */
    public function collectParents()
    {
        $data = $this->data;
        $id = S($data['token']);
        $pid = $data['pid']??$this->ApiReturn(-1, '老师id不能为空');
        if (M('Collection')->where(['parent_id'=>$id, 'item_id'=>$pid,'type'=>3])->find())
        {
            $this->ApiReturn(-1, '已经收藏过了');
        }

        M('Collection')->add([
            'parent_id'     =>  $id,
            'item_id'       =>  $pid,
            'c_time'        =>  date('Y-m-d H:i:s'),
            'type'          =>  3
        ]);
        $this->ApiReturn(1, '收藏成功');
    }

    /**
     * 二期增加
     * 我的收藏--收藏的家长
     * sunfan
     * 2017.11.6
     */
    public function collectionParent()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $where = ['fzm_collection.parent_id'=>$id];
        $where['type'] = 3;
        $list = M('Collection')
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_collection.item_id')
            ->where($where)
            ->page($page)
            ->order('c_time DESC')
            ->field('fzm_collection.id as collection_id, fzm_parents.id as pid, parent_name, p_img')
            ->select();
        if (!$list){
            $this->ApiReturn(0, '没有收藏');
        }
        $this->ApiReturn(1, 'success', $list);
    }

    /**
     * 取消收藏
     * sunfan
     * 2017.11.6
     */
    public function cancelCollection()
    {
        $data = $this->data;
        $id = S($data['token']);
        $collection_id = $data['collection_id'];
        M('Collection')->where(['id'=>$collection_id, 'parent_id'=>$id])->delete();
        $this->ApiReturn(1, '取消成功');
    }

    /**
     * 取消收藏老师
     * sunfan
     * 2017.11.20
     */
    public function cancelColTeacher()
    {
        $data = $this->data;
        $id = S($data['token']);
        $teacher_id = $data['teacher_id'];
        M('Collection')->where(['item_id'=>$teacher_id, 'parent_id'=>$id, 'type'=>1])->delete();
        $this->ApiReturn(1, '取消成功');
    }

    /**
     * 取消收藏托管
     * sunfan
     * 2017.11.20
     */
    public function cancelColManaged()
    {
        $data = $this->data;
        $id = S($data['token']);
        $managed_id = $data['managed_id'];
        M('Collection')->where(['item_id'=>$managed_id, 'parent_id'=>$id, 'type'=>2])->delete();
        $this->ApiReturn(1, '取消成功');
    }

    /**
     * 取消收藏家长
     * sunfan
     * 2018.5.4
     */
    public function cancelColParents()
    {
        $data = $this->data;
        $id = S($data['token']);
        $parent_id = $data['parent_id'];
        M('Collection')->where(['item_id'=>$parent_id, 'parent_id'=>$id, 'type'=>3])->delete();
        $this->ApiReturn(1, '取消成功');
    }

    /**
     * 修改手机号
     * sunfan
     * 2017.11.6
     */
    public function editPhone()
    {
        $data = $this->data;
        $id = S($data['token']);
        $pw = $data['password']??$this->ApiReturn(-1, '密码不能为空');
        $code = $data['code']??$this->ApiReturn(-1, '验证码不能为空');
        $new_phone = $data['new_phone']??$this->ApiReturn(-1, '新手机号不能为空');
        $info = M('Parents')->where(['id'=>$id, 'password'=>$pw])->find();
        if (!$info)
        {
            $this->ApiReturn(-1, '密码不正确');
        }
        if ($new_phone == $info['phone'])
        {
            $this->ApiReturn(-1, '不能与原手机号相同');
        }
        checkPhoneCode($new_phone, $code);        //--------TODO::验证短信验证码 ---------
        if (M('Parents')->where(['phone'=>$new_phone, 'id'=>['neq', $id]])->find())
        {
            $this->ApiReturn(-1, '手机号已经被占用');
        }
        M('Parents')->where(['id'=>$id])->save(['phone'=>$new_phone]);
        $this->ApiReturn(1, '修改成功');
    }

    /**
     * 修改个人信息
     * sunfan
     * 2017.11.6
     */
    public function editInfo()
    {
        $data = $this->data;
        $id = S($data['token']);
        $map=[];
        if (!empty($data['img']))
        {
            $map['p_img'] = $data['img'];
        }
        if (!empty($data['child_name']))
        {
            $map['child_name'] = $data['child_name'];
        }
        if (!empty($data['child_sex']))
        {
            $map['child_sex'] = $data['child_sex'];
        }
        if (!empty($data['relationship']))
        {
            $map['relationship'] = $data['relationship'];
        }
        if (!empty($data['parent_name']))
        {
            $map['parent_name'] = $data['parent_name'];
        }
        if (!empty($data['child_school']))
        {
            $map['child_school'] = $data['child_school'];
        }
        if (!empty($data['child_grade']))
        {
            $map['child_grade'] = $data['child_grade'];
        }
        M('Parents')->where(['id'=>$id])->save($map);
        $this->ApiReturn(1, '修改成功');
    }

    /**
     * 我的评价
     * sunfan
     * 2017.11.7
     */
    public function myAssess()
    {
        $data = $this->data;
        $type = $data['type']??1;
        if ($type==1){
            $this->getTeacherAssess($data);
        }elseif($type==2){
            $this->getEducationAssess($data);
        }else{
            $this->getHosingAssess($data);
        }
    }

    //给老师的评价
    protected function getTeacherAssess($data){
        $page = $data['page']??1;
        $id = S($data['token']);
        $list = M('Assess')
            ->join('LEFT JOIN fzm_order ON fzm_order.id=fzm_assess.order_id')
            ->join('LEFT JOIN fzm_teacher ON fzm_teacher.id=fzm_order.teacher_id')
            ->where(['u_id'=>$id, 'type'=>1])
            ->field('t_img, sex, name as teacher_name, t_grade as grade, t_sub2 as subject, star, content, subject as class, a_time, img1, img2, img3')
            ->page($page, 20)
            ->select();
        if (!$list){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $list);
        }
    }

    //给教育机构的评价
    protected function getEducationAssess($data){
        $page = $data['page']??1;
        $id = S($data['token']);
        $list = M('Assess')
            ->join('LEFT JOIN fzm_educational_order eo ON eo.id=fzm_assess.order_id')
            ->join('LEFT JOIN fzm_educational_course ec ON ec.id=eo.ec_id')
            ->where(['u_id'=>$id, 'type'=>2])
            ->field('img, title, grade, sub1, sub2, class, total_money, fzm_assess.star, fzm_assess.content, a_time, img1, img2, img3')
            ->page($page, 20)
            ->select();
        if (!$list){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $list);
        }
    }

    //给托管的评价
    protected function getHosingAssess($data){
        $page = $data['page']??1;
        $id = S($data['token']);
        $list = M('Assess')
            ->join('LEFT JOIN fzm_hosting_order ho ON ho.id=fzm_assess.order_id')
            ->join('LEFT JOIN fzm_hosting_course hc ON hc.id=ho.hc_id')
            ->where(['u_id'=>$id, 'fzm_assess.type'=>3])
            ->field('img, title, date_period, total_money, fzm_assess.star, fzm_assess.content, a_time, img1, img2, img3')
            ->page($page, 20)
            ->select();
        if (!$list){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $list);
        }
    }

    /**
     * 意见反馈
     * sunfan
     * 2017.11.7
     */
    public function feedback()
    {
        $data = $this->data;
        $id = S($data['token']);
        $state = $data['type']??$this->ApiReturn(-1, '请选择类型');
        $title = $data['title']??$this->ApiReturn(-1, '请输入主题');
        $content = $data['content']??$this->ApiReturn(-1, '请输入内容');
        M('Feedback')->add([
           'uid'        =>  $id,
            'type'      =>  1,
            'content'   =>  $content,
            'f_time'    =>  date('Y-m-d H:i:s'),
            'state'     =>  $state,
            'title'     =>  $title
        ]);
        $this->ApiReturn(1, '提交成功');
    }

    /**
     * 修改密码
     * sunfan
     * 2017.11.7
     */
    public function editPw()
    {
        $data = $this->data;
        $id = S($data['token']);
        $pw = $data['password']??$this->ApiReturn(-1, '密码不能为空');
        $new_pw = $data['new_pw']??$this->ApiReturn(-1, '新密码不能为空');
        $info = M('Parents')->where(['id'=>$id, 'password'=>$pw])->find();
        if (!$info)
        {
            $this->ApiReturn(-1, '密码不正确');
        }
        M('Parents')->where(['id'=>$id])->save(['password'=>$new_pw]);
        $this->ApiReturn(1, '修改成功');
    }

    /**
     * 邀请码
     * sunfan
     * 2018.5.14
     */
    public function inviteCode()
    {
        $data = $this->data;
        $id = S($data['token']);
        $info = M('Parents')->where(['id'=>$id])->find();
//        if (empty($info['code'])){
//            //生成邀请码
//            $return['code'] = getInvitationCode($id);
//            M('Parents')->where(['id'=>$id])->save(['code'=>$return['code']]);
//        }else{
//            $return['code'] = $info['code'];
//        }
        $parent_id = '';
        if ($info['st_id'] > 0) {
            $parent_id = $info['id'];
        }

        $return['url'] = "http://".$_SERVER['HTTP_HOST']."/fzh_teacherweb/twoqi/sign.html?parent_id=".$parent_id;
        $return['image'] = qrcode($return['url'], $id);
        $this->ApiReturn(1, 'success', $return);
    }

    /**
     * 购买会员
     * sunfan
     * 2018.5.14
     */
    public function addVipOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $db = M('Level');

        $lid = !empty($data['lid'])?$data['lid']:$this->ApiReturn(-1, '请选择会员等级');
        $level = $db->where(['id'=>$lid])->find();

        $user = M('Parents')->where(['id'=>$id])->find();
        $olevel = $db->where(['id'=>$user['level_id']])->find();
        if ($level['money']<=$olevel['money']){
            $this->ApiReturn(-1, '请选择更高等级的会员');
        }

        //如果有相同的会员等级，未支付的订单，用那个，否则生成一个新的
        $old_order = M('VipOrder')->where([
            'uid'   =>  $id,
            'u_type'=>  1,      //1家长 2老师 3托管/教育机构
            'vip_level_id'=>$lid,
            'status'=>  1,  //1 未支付 2已支付
        ])->find();
        if ($old_order){
//            $id = $old_order['id'];
//            $sn = $old_order['sn'];
            M('VipOrder')->delete($old_order['id']);
        }
        $sn = 'V'.sp_get_order_sn();
        $id = M('VipOrder')->add([
            'sn'    =>  $sn,
            'uid'   =>  $id,
            'u_type'=>  1,
            'vip_level_id'=>$lid,
            'o_time'=>  date('Y-m-d H:i:s'),
            'money' =>  $level['money'],
            'time_length' =>  $level['time_length']
        ]);
        if ($id){
            $this->ApiReturn(1, 'success', ['sn'=>$sn]);
        }else{
            $this->ApiReturn(-1, '系统错误');
        }
    }
    
}