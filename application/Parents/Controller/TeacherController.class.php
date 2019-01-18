<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/11/2
 * Time: 9:43
 */

namespace Parents\Controller;

use Client\Controller\UserApiBaseController;
use JPush\Client;
require('./application/Common/common/JPushSF.php');

class TeacherController extends UserApiBaseController
{

    public function index1()
    {
        $data = $this->data;
        $id = S($data['token']);

        S($id.'list', null);

        $page = $data['page']??1;
        if ($page!=1){
            $this->index2();
        }
        $where['status'] = 1;       //1正常  2冻结
        $where['state'] = 2;        //0 注册成功1.审核中 2.审核通过 3.驳回
        if (!empty($data['keyword']))
        {
            $where['name'] = ['like', '%'.$data['keyword'].'%'];
        }

        $order = [];

        if (!empty($data['price']))
        {
            if ($data['price']==1)           //课时费从低到高
            {
                $order['price'] = 'ASC';
            }
            if ($data['price']==2)           //课时费从高到低
            {
                $order['price'] = 'DESC';
            }
        }
        if (!empty($data['score']))          //评价从高到低
        {
            $order['score'] = 'DESC';
        }

        if (!empty($data['sex']))
        {
            $where['sex'] = $data['sex'];
        }

        if (!empty($data['pricel']) && !empty($data['pricer']))
        {
            $where['price'] = ['between', [$data['pricel'], $data['pricer']]];
        }

        if (!empty($data['province']) || !empty($data['city']) || !empty($data['area']))
        {
            $where['province'] = ['like', '%'.$data['province'].'%'];
            $where['city'] = ['like', '%'.$data['city'].'%'];
            $where['area'] = ['like', '%'.$data['area'].'%'];
        }


        $list = M('Teacher')
            ->where($where)
//            ->page($page)
            ->order($order)
            ->field('id as t_id, name, sex, age, t_img, t_grade, t_sub2 as subject, idcard1, idcard2, hand_card, academic, t_card, price, score, position')
            ->select();

        if (!$list){
            $this->ApiReturn(0, '没有数据');
        }

        $start = M('Parents')->where(['id'=>$id])->getField('p_xy');
        foreach ($list as $k=>$item)
        {
            if (!empty($data['subject']))
            {
                if (!M('TeacherSubject')->where(['sub1|sub2'=>['like', "%".$data['subject']."%"], 'teacher_id'=>$item['t_id']])->find())
                {
                    unset($list[$k]);
                    continue;
                }
            }

            $list[$k]['renzheng'] = -1;
            if (!empty($item['idcard1']) && !empty($item['idcard2']) && !empty($item['hand_card'])){
                $list[$k]['renzheng'] = 1;
            }
            unset($list[$k]['idcard1']);
            unset($list[$k]['idcard2']);
            unset($list[$k]['hand_card']);

            $list[$k]['zizhi'] = -1;
            if (!empty($item['t_card'])){
                $list[$k]['zizhi'] = 1;
            }
            unset($list[$k]['t_card']);

            $list[$k]['xueli'] = -1;
            if (!empty($item['academic'])){
                $list[$k]['xueli'] = 1;
            }
            unset($list[$k]['academic']);

            //如果有科目，把最后一个多余的逗号去掉
            if (!empty($item['subject']))
            {
//                dump($item['subject']);
                $list[$k]['subject'] = substr($item['subject'], 0, -1);
            }

            $dis = getDistance($start, $item['position']);
            $list[$k]['distance']=$dis['length'];
            $list[$k]['distance1']=$dis['length1'];
        }

        if (!$list){
            $this->ApiReturn(0, '没有数据');
        }

        $list=array_values($list);

        if (!empty($data['distance']))
        {
            if ($data['distance']==1){
                $sort = [
                    'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                    'field'     => 'distance1',       //排序字段
                ];
            }else{
                $sort = [
                    'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                    'field'     => 'distance1',       //排序字段
                ];
            }

            $arrSort = [];
            foreach($list AS $uniqid => $row){
                foreach($row AS $key=>$value){
                    $arrSort[$key][$uniqid] = $value;
                }
            }
            if($sort['direction']){
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $list);
            }
        }
        $relist = array_slice($list, 0, 10);

        S($id.'list', $list);

        if (!$relist){
            $this->ApiReturn(0, 'success', $relist);
        }else{
            $this->ApiReturn(1, 'success', $relist);
        }
    }

    public function index2()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']-1;
        $list = S($id.'list');
        $output = array_slice($list, $page*10, 10);
        if (!$output){
            $this->ApiReturn(0, 'success', $output);
        }else{
            $this->ApiReturn(1, 'success', $output);
        }

    }


    public function index()
    {
        $data = $this->data;
        $id = S($data['token']);
        $where['pid'] = $id;
        $where['status'] = 1;       //1正常  2冻结
        $where['state'] = 2;        //0 注册成功1.审核中 2.审核通过 3.驳回
        $page = $data['page']??1;
        if (!empty($data['keyword']))
        {
            $where['t.name'] = ['like', '%'.$data['keyword'].'%'];
        }
        $order['sort'] = 'asc';

        if (!empty($data['distance']))
        {
            $order=[];
            if ($data['distance']==1){
                $order['distance1'] = "asc";
            }else{
                $order['distance1'] = "desc";
            }
        }

        if (!empty($data['price']))
        {
            if ($data['price']==1)           //课时费从低到高
            {
                $order['price'] = 'ASC';
            }
            if ($data['price']==2)           //课时费从高到低
            {
                $order['price'] = 'DESC';
            }
        }
        if (!empty($data['score']))          //评价从高到低
        {
            $order['score'] = 'DESC';
        }

        if (!empty($data['sex']))
        {
            $where['sex'] = $data['sex'];
        }

        if (!empty($data['pricel']) && !empty($data['pricer']))
        {
            $where['price'] = ['between', [$data['pricel'], $data['pricer']]];
        }

        if (!empty($data['province']) || !empty($data['city']) || !empty($data['area']))
        {
            $where['province'] = ['like', '%'.$data['province'].'%'];
            $where['city'] = ['like', '%'.$data['city'].'%'];
            $where['area'] = ['like', '%'.$data['area'].'%'];
        }

        if (!empty($data['identity'])){
            $where['t_type'] = $data['identity'];   //1大学生(家教） 2老师 3.外教
        }

        if ((!empty($data['subject']) && $data['subject']!="全部") || (!empty($data['grade']) && $data['grade']!="全部")){
            if ($page!=1){
                $this->ApiReturn(0, 'success');
            }
            $list = M('TeacherSort')
                ->alias('t')
                ->join('LEFT JOIN fzm_level l ON l.id=t.level_id')
                ->where($where)
                ->order($order)
                ->field('tid as t_id, t.name, sex, age, t_img, t_grade, t_sub2 as subject, price, score, position, distance, view_num, t_type, level_id, l.name as level_name, l.icon')
                ->select();
        }else{
            $list = M('TeacherSort')
                ->alias('t')
                ->join('LEFT JOIN fzm_level l ON l.id=t.level_id')
                ->where($where)
                ->page($page)
                ->order($order)
                ->field('tid as t_id, t.name, sex, age, t_img, t_grade, t_sub2 as subject, price, score, position, distance, view_num, t_type, level_id, l.name as level_name, l.icon')
                ->select();
        }


        if (!$list){
            $this->ApiReturn(0, 'success', $list);
        }
        foreach ($list as $k=>$item)
        {
            //科目筛选
            if (!empty($data['subject']) && $data['subject']!="全部")
            {
                if (!M('TeacherSubject')->where(['sub1|sub2'=>['like', "%".$data['subject']."%"], 'teacher_id'=>$item['t_id']])->find())
                {
                    unset($list[$k]);
                    continue;
                }
            }

            //年级筛选
            if (!empty($data['grade']) && $data['grade']!="全部")
            {
                if (!M('TeacherSubject')->where(['grade'=>['like', "%".$data['grade']."%"], 'teacher_id'=>$item['t_id']])->find())
                {
                    unset($list[$k]);
                    continue;
                }
            }

            $list[$k]['renzheng'] = 1;
            $list[$k]['zizhi'] = 1;
            $list[$k]['xueli'] = 1;

            //如果有科目，把最后一个多余的逗号去掉
            if (!empty($item['subject']))
            {
                $list[$k]['subject'] = substr($item['subject'], 0, -1);
            }
        }
        $list = array_values($list);

        if (!$list){
            $this->ApiReturn(0, 'success', $list);
        }else{
            $this->ApiReturn(1, 'success', $list);
        }
    }



    /**
     * 老师详情页
     * sunfan
     * 2017.11.2
     */
    public function detail()
    {
        $data = $this->data;
        $id = S($data['token']);
        $teacher_id = $data['t_id'];
        $info = M('Teacher')
            ->alias('t')
            ->join('LEFT JOIN fzm_level l ON l.id=t.level_id')
            ->where(['t.id'=>$teacher_id])
            ->field('t.id as t_id, phone, t.name, sex, age, t_img, idcard1, idcard2, hand_card, academic, t_card, score, evaluation, experience, features, price, school, health, t_type, view_num, label_id,sale_num, level_id, l.name as level_name, l.icon')
            ->find();
        $info['renzheng'] = -1;
        if (!empty($info['idcard1']) && !empty($info['idcard2']) && !empty($info['hand_card'])){
            $info['renzheng'] = 1;
        }

        $info['zizhi'] = -1;
        if (!empty($info['t_card'])){
            $info['zizhi'] = 1;
        }
        $info['level_name'] = D("level")->where(['id'=>$info['level_id']])->getField('name');
        $info['xueli'] = -1;
        if (!empty($info['academic'])){
            $info['xueli'] = 1;
        }

        $info['ceping'] = -1;
        if (!empty($info['evaluation'])){
            $info['ceping'] = 1;
        }

        $info['jiankang'] = -1;
        if (!empty($info['health'])){
            $info['jiankang'] = 1;
        }

        $orders = M('Order')->where(['teacher_id'=>$teacher_id])->select();

        //学生数
        $info['student_num'] = count($orders);

        //授课时长
        $info['teach_time'] = 0;
        foreach ($orders as $order)
        {
            $info['teach_time'] += $order['class_num']*30;
        }

        //评价数
        $info['assess_num'] = 0;
        $assess = M('Assess')
            ->join('LEFT JOIN fzm_order ON fzm_order.id=fzm_assess.order_id')
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_assess.u_id')
            ->where(['t_id'=>$teacher_id, 'type'=>1])
            ->field('child_name, p_img, subject, content, a_time, fzm_assess.status, img1, img2, img3')
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
        $info['assess_num'] = count($assess);

        //教授科目
        $info['subject']=[];
        $subjects = M('TeacherSubject')->where(['teacher_id'=>$teacher_id])->select();
        foreach ($subjects as $k=>$sub)
        {
            $info['subject'][$k]['subject_name'] = $sub['grade'].$sub['sub2'];
            $info['subject'][$k]['grade'] = $sub['grade'];
            $info['subject'][$k]['subject'] = $sub['sub2'];
            $info['subject'][$k]['subject_id'] = $sub['id'];
            $info['subject'][$k]['price'] = $sub['price'];
            $info['subject'][$k]['description'] = $sub['description'];
            $info['subject'][$k]['sale'] = $sub['sale'];
        }

        //空闲时间
        $info['time'] = M('TeacherTime')->where(['teacher_id'=>$teacher_id,'status'=>1])->field('id,day, start, end')->select();

        //评论
        $info['assess'] = $assess;

        //所获荣誉
        $info['honor'] = M('TeacherHonor')->where(['teacher_id'=>$teacher_id])->field('url, msg')->select();

        //收藏
        $info['collection'] = -1;
        if (M('Collection')->where(['parent_id'=>$id, 'item_id'=>$teacher_id])->find())
        {
            $info['collection'] = 1;
        }

        $info['label'] = [];
        //标签
        if (!empty($info['label_id'])){
            $label_ids = explode(',', $info['label_id']);
            if ($label_ids)foreach ($label_ids as $lid){
                $name = M('Label')->where(['id'=>$lid])->getField('lname');
                array_push($info['label'], $name);
            }
        }
        unset($info['label_id']);

        //线上课堂
        $info['online_class'] = M('OnlineClass')
            ->where(['tid'=>$teacher_id,'status'=>1])
            ->limit(1)
            ->field('id as oc_id, img_url, video_url')
            ->order('oc_time desc')
            ->select();

        $info['accid'] = "t".$teacher_id;


        //增加一条访客记录
        $this->addVisitor($id, $teacher_id, 1);

        //增加浏览量
        M('Teacher')->where(['id'=>$teacher_id])->setInc('view_num');
        M('TeacherSort')->where(['t_id'=>$teacher_id])->setInc('view_num');

        $this->ApiReturn(1, 'success', $info);
    }


    /**
     * 立即预约
     * sunfan
     * 2017.11.2
     */
    public function reservation()
    {
        $data = $this->data;
        $id = S($data['token']);
        $teacher_id = $data['teacher_id']??$this->ApiReturn(-1, '老师id不能为空');
        $class_num = $data['class_num']??1;
        $subject_id = $data['subject_id']??$this->ApiReturn(-1, '科目id不能为空');
        $teach_date = $data['teach_date'];
        $teach_time = $data['teach_time'];
        $teacher_time_id = $data['teacher_time_id'];
        $address_id = $data['address_id']??$this->ApiReturn(-1, '授课地址不能为空');
        $coupon_id = $data['cr_id'];//优惠券id

        $teacher = M('Teacher')->where(['id'=>$teacher_id])->find();
        if (!$teacher)$this->ApiReturn(-1, '老师账号异常，请选择其他老师');
        if($teacher['status'] == 2){
            $this->ApiReturn(-1, '老师账号异常，请选择其他老师');
        }
        $subject = M('TeacherSubject')->where(['teacher_id'=>$teacher_id, 'id'=>$subject_id])->find();
        if (!$subject)$this->ApiReturn(-1, '科目异常，请选择其他科目');
        $address = M('ParentsAddress')->where(['id'=>$address_id])->find();
        if (!$address)$this->ApiReturn(-1, '地址无效，请选择其他地址');

        $config = M('Config')->find();
        $total = $subject['price']*$class_num;
        $order_sn = "D";
        $order_sn .= sp_get_order_sn();

        //使用优惠券
        $coupon_money=0;
        if (!empty($coupon_id)){
            $coupon_money = $this->useCoupon($id, 1, $total, $coupon_id);
        }

        $total -= $coupon_money;

        $oid = M('Order')->add([
            'sn'            =>  $order_sn,
            'parent_id'     =>  $id,
            'class_num'     =>  $class_num,
            'teacher_id'    =>  $teacher_id,
            'total_money'   =>  $total,
            'platform_money'=>  $total*$config['radio'],
            'money'         =>  $total*(1-$config['radio']),
            'coupon_money'  =>  $coupon_money??0.00,
            'start_time'    =>  date('Y-m-d H:i:s'),
            'subject'       =>  $subject['grade'].$subject['sub2'],
            'o_price'       =>  $subject['price'],
            'teacher_time_id'=> $teacher_time_id,
            'teach_date'    =>  $teach_date,
            'teach_time'    =>  $teach_time,
            'address'       =>  $address['province'].$address['city'].$address['area'].$address['address'],
            'address_other' =>  $address['other']
        ]);
        if ($oid){
//            M('TeacherTime')->where(array('id'=>$teacher_time_id))->save(array('status'=>2));

            $alias = 'teacher'.$teacher_id;
            //给老师推送
            $push = new \JPushSF(C('JPush.TAPPID'),C('JPush.TAPPSECRET'));
            $receive = array('alias'=>[$alias]);//别名
            $push->push($receive,1, $oid);

            //给老师发短信
            $teacher_phone = M('Teacher')->where(['id'=>$teacher_id])->getField('phone');
            send_message($teacher_phone,'SMS_126866868');

            $this->ApiReturn(1, 'success', ['order_sn'=>$order_sn]);
        }else{
            $this->ApiReturn(-1, '失败', ['kefu_tel'=>$config['kefu_tel']]);
        }
    }

    /**
     * 地址列表
     * sunfan
     * 2017.11.2
     */
    public function addressList()
    {
        $data = $this->data;
        $id = S($data['token']);
        $list = M('ParentsAddress')
            ->where(['parents_id'=>$id])
            ->field('id as a_id, pa_name as name, pa_tel as tel, province, city, area, address,other')
            ->select();
        $this->ApiReturn(1, 'success', $list);
    }

    /**
     * 修改地址
     * sunfan
     * 2017.11.6
     */
    public function editAddress()
    {
        $data = $this->data;
        $id = S($data['token']);
        $a_id = $data['a_id']??$this->ApiReturn(-1, '请选择要修改的地址');
        $province = $data['province']??$this->ApiReturn(-1, '请选择所在省');
        $city = $data['city']??$this->ApiReturn(-1, '请选择所在市');
        $area = $data['area']??$this->ApiReturn(-1, '请选择所在区');
        $address = $data['address']??$this->ApiReturn(-1, '请输入详细地址');
        $name = $data['name']??$this->ApiReturn(-1, '请输入联系人');
        $tel = $data['tel']??$this->ApiReturn(-1, '请输入联系电话');
        $other = $data['other']??$this->ApiReturn(-1, '请输入门牌号');
        M('ParentsAddress')->where(['id'=>$a_id, 'parents_id'=>$id])->save([
           'province'   =>  $province,
            'city'      =>  $city,
            'area'      =>  $area,
            'address'   =>  $address,
            'pa_name'   =>  $name,
            'pa_tel'    =>  $tel,
            'other'    =>  $other
        ]);
        $this->ApiReturn(1, 'success');
    }

    /**
     * 新增地址
     * sunfan
     * 2017.11.6
     */
    public function addAddress()
    {
        $data = $this->data;
        $id = S($data['token']);
        $province = $data['province']??$this->ApiReturn(-1, '请选择所在省');
        $city = $data['city']??$this->ApiReturn(-1, '请选择所在市');
        $area = $data['area']??$this->ApiReturn(-1, '请选择所在区');
        $address = $data['address']??$this->ApiReturn(-1, '请输入详细地址');
        $name = $data['name']??$this->ApiReturn(-1, '请输入联系人');
        $tel = $data['tel']??$this->ApiReturn(-1, '请输入联系电话');
        $other = $data['other']??$this->ApiReturn(-1, '请输入门牌号');
        M('ParentsAddress')->add([
            'parents_id'=> $id,
            'province'  =>  $province,
            'city'      =>  $city,
            'area'      =>  $area,
            'address'   =>  $address,
            'pa_name'   =>  $name,
            'pa_tel'    =>  $tel,
            'other'    =>  $other
        ]);
        $this->ApiReturn(1, 'success');
    }

    /**
     * 删除地址
     * sunfan
     * 2017.11.10
     */
    public function delAddress()
    {
        $data = $this->data;
        $id = S($data['token']);
        $a_id = $data['a_id']??$this->ApiReturn(-1, '请选择要修改的地址');
        M('ParentsAddress')->where(['id'=>$a_id, 'parents_id'=>$id])->delete();
        $this->ApiReturn(1, '删除成功');
    }

    /**
     * 收藏老师
     * sunfan
     * 2017.11.17
     */
    public function collectTeacher()
    {
        $data = $this->data;
        $id = S($data['token']);
        $teacher_id = $data['teacher_id']??$this->ApiReturn(-1, '老师id不能为空');
        if (M('Collection')->where(['parent_id'=>$id, 'item_id'=>$teacher_id,'type'=>1])->find())
        {
            $this->ApiReturn(-1, '已经收藏过了');
        }

        M('Collection')->add([
            'parent_id'     =>  $id,
            'item_id'       =>  $teacher_id,
            'c_time'        =>  date('Y-m-d H:i:s'),
            'type'          =>  1
        ]);
        $this->ApiReturn(1, '收藏成功');
    }

    /**
     * 老师banner
     * sunfan
     * 2017.12.14
     */
    public function banner()
    {
        $data = $this->data;
        $id = S($data['token']);
        $teacher_id = $data['teacher_id']??$this->ApiReturn(-1, '老师id不能为空');
        $rs = M('TeacherBanner')->where(['teacher_id'=>$teacher_id])->select();
        if (!$rs)
        {
            $this->ApiReturn(0, '没有数据');
        }
        $this->ApiReturn(1, 'success', $rs);
    }

}