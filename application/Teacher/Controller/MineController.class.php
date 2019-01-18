<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/6
 * Time: 17:41
 */
namespace Teacher\Controller;

use Client\Controller\UserApiBaseController;

class MineController extends UserApiBaseController
{

    ///////////////             二期新增
    //我的访客
    public function visitor(){
        $data = $this->data;
        $page = $data['page']?$data['page']:1;
        $id = S($data['token']);
        $info = M("visitor")->where(['owner_id'=>$id,'type'=>1])->order('visit_time desc')->limit(($page-1)*10,10)->select();
        if(!empty($info)){
            foreach($info as $k=>$v){
                $arr[$k] = M("parents")->where(['id'=>$v['user_id']])->field('id,p_img,phone,parent_name')->find();
                $arr[$k]['time'] = $v['visit_time'];
                $arr[$k]['accid'] = "p".$v['user_id'];
            }
            $code = 1;
            $msg = 'ok';
        }else{
            $code = 0;
            $msg = '无数据';
        }
        $this->ApiReturn($code,$msg,$arr);
    }
    //全部标签 + 我的标签
    public function all_label(){
        $data = $this->data;
        $list = M("label")->select();
        $id = S($data['token']);
        $mine_list = M("teacher")->where(['id'=>$id])->getField("label_id");
        $m = explode(',',$mine_list);
//        print_r($mine_list);die;
        foreach($list as $k=>&$v){
            if(in_array($v['id'],$m)){
                $v['status'] = 1;
            }else{
                $v['status'] = 2;
            }
        }
        $this->ApiReturn(1,'ok',$list);

    }
    //提交 我的标签
    public function mine_label(){
        $data = $this->data;
        $id = S($data['token']);
        $ids = $data['id'];          //用逗号分开
        $info = M("teacher")->where(['id'=>$id])->save(['label_id'=>$ids]);
        if($info){
            $this->ApiReturn(1,'保存成功！');
        }else{
            $this->ApiReturn(0,'您没有做修改');
        }
    }


    ///////////////             二期新增  end
    //老师传banner
    public function Teacher_banner()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['url'] ==""){
            $this->ApiReturn(-1, '图片地址不存在');
        }
        $save = M('TeacherBanner')->add(array('url'=>$data['url'],'teacher_id'=>$id));
        if(!$save){
            $this->ApiReturn(-1, '失败');
        }
        $this->ApiReturn(1, '成功');
    }

    //消除banner
    public function delete_banner()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['id'] ==""){
            $this->ApiReturn(-1, '图片id不存在');
        }
        $del = M('TeacherBanner')->where(array('id'=>$data['id']))->delete();
        if(!$del){
            $this->ApiReturn(-1, '失败');
        }
        $this->ApiReturn(1, '成功');

    }

    //修改手机号
    public function editphone()
    {
        $data = $this->data;
        $id = S($data['token']);
        /*if($data['phone'] ==""){
            $this->ApiReturn(-1, '电话不存在');
        }*/
        if($data['password'] ==""){
            $this->ApiReturn(-1, '密码不存在');
        }
        if($data['newphone'] ==""){
            $this->ApiReturn(-1, '新电话不存在');
        }
        if($data['code'] ==""){
            $this->ApiReturn(-1, 'code不存在');
        }
        checkPhoneCode($data['newphone'], $data['code']);
        $find = M('Teacher')->where(array('id'=>$id))->find();
        if(!$find){
            $this->ApiReturn(-1, '用户不存在');
        }

        if (M('Teacher')->where(['phone'=>$data['newphone'], 'id'=>['neq', $id]])->find()){
            $this->ApiReturn(-1, '该手机号已被注册');
        }
        if($find['password'] != md5($data['password'])){
            $this->ApiReturn(-1, '用户密码不正确');
        }
        $save = M('Teacher')->where(array('id'=>$id))->save(array('phone'=>$data['newphone']));
        if(!$save){
            $this->ApiReturn(-1, '修改失败');
        }
        $this->ApiReturn(1, '修改成功');
    }
    /////////////////////////////////////////////////////////////////    二期修改
    //我的钱包
    public function mywallet()
    {
        $data = $this->data;
        $id = S($data['token']);
        if(!in_array($data['type'],[1,2]))$this->ApiReturn(-1,'参数错误');
        $info = M('teacher')->where(array('id'=>$id))->find();
        $page = $data['page']?$data['page']:1;
        if($data['keyword']){
            $keyword = trim($data['keyword']);
            $where['o.sn'] = ['like',"%$keyword%"];
        }

        $list = array();
        $list['balance'] = $info['balance'];
        $count = 0;
        $money = 0;
        //上课收入
        if($data['type'] == 1){
            $where['o.teacher_id'] = $id;
            $where['o.status'] = array('in',('3,5'));
            $where['o.money'] = array('gt',0);
            if($data['start_time'] && $data['end_time']){
                $where['o.start_time'] = ['between',[$data['start_time'],$data['end_time']]];
            }
            $list['count'] = M('Order')->alias('o')
                ->join('left join fzm_parents p on o.parent_id=p.id')
                ->where($where)
                ->count();

            $list['list'] = M('Order')->alias('o')
                ->join('left join fzm_parents p on o.parent_id=p.id')
                ->field('o.id,o.sn,o.money as platform_money,o.end_time,p.child_name,o.subject')
                ->order('o.end_time desc')
                ->limit(($page-1)*10,10)
                ->where($where)
                ->select();
            $list['money'] = M('Order')->alias('o')
                ->join('left join fzm_parents p on o.parent_id=p.id')
                ->where($where)
                ->sum('o.money');
        }else{
            $where['o.tid'] = $id;
            $where['o.status'] = 2;
            if($data['start_time'] && $data['end_time']){
                $where['o.o_time'] = ['between',[$data['start_time'],$data['end_time']]];
            }
            $list['count'] = M("OnlineClassOrder")->alias('o')
                ->join('left join fzm_online_class c on c.id = o.oc_id')
                ->where($where)
                ->count();
            $list['list'] = M("OnlineClassOrder")->alias('o')
                ->join('left join fzm_online_class c on c.id = o.oc_id')
                ->field("o.id,o.sn,o.money platform_money,o.o_time as end_time,c.title child_name, c.t_sub1, c.t_sub2")
                ->where($where)
                ->limit(($page-1)*10,10)
                ->order('o.o_time desc')
                ->select();
            $list['money'] = M("OnlineClassOrder")->alias('o')
                ->join('left join fzm_online_class c on c.id = o.oc_id')
                ->where($where)
                ->sum('o.money');
            foreach ($list['list'] as &$item){
                $item['subject'] = $item['t_sub1']." ".$item['t_sub2'];
                unset($item['t_sub1']);
                unset($item['t_sub2']);
            }
        }
        if($list['balance'] == 0 && empty($list['list'])){
            $code = 0 ;
            $msg = '无数据';
        }else{
            $code = 1;
            $msg = '成功';
        }


//        $arr = array_merge($list,$order_list);
//        $re = $this->mymArrsort($arr,'');
//        $res['list'] = $this->arrPage($re,$page,10);

        $this->ApiReturn($code,$msg,$list);

    }
    //二维数组分页
    function arrPage($arr, $page, $indexinpage) {
        $page = is_int($page) != 0 ? $page : 1; //当前页数
        $indexinpage = is_int($indexinpage) != 0 ? $indexinpage : 5; //每页显示几条
        $newarr = array_slice($arr, ($page - 1) * $indexinpage, $indexinpage);
        return $newarr;
    }
    //二维数组进行排序
    function mymArrsort($result, $var) {
        foreach ($result as $key => $val) {
            $pj[$key] = $val['end_time'];
            array_multisort($pj, SORT_NUMERIC, SORT_DESC, $result);
        }
        return $result;
    }

    //我的视频课
    public function video_class(){
        $data = $this->data;
        $tid = S($data['token']);
        $where = [];
        $where['tid'] = $tid;
        $where['status'] = ['neq',3];
        $where['u_type'] = 1;
        $t_sub1 = $data['t_sub1'];
        if($t_sub1 && $t_sub1!="全部"){
            $where['t_sub1'] = ['like',"%$t_sub1%"];
        }
        $level = M("teacher")->alias('m')->join("left join fzm_level l on m.level_id = l.id")->where(['m.id'=>$tid])->field('l.id,l.name')->find();
        $field = 'id,title,img_url,sale_num,price,status';
        $page = $data['page']?$data['page']:1;
        $arr['list'] = M("online_class")->where($where)->limit(($page-1)*10,10)->field($field)->order("oc_time desc")->select();

        $arr['subject'][0] = ['s_name'=>"全部"];
        $subject = M("subject")->where(['parentid'=>0])->order('sort asc')->field('s_name')->select();
        foreach ($subject as $item){
            array_push($arr['subject'], $item);
        }

        $this->ApiReturn(1,'ok',$arr);
    }

    /**
     * 发布视频课
     * sunfan
     * 2018.6.14
     */
    public function publishVideo()
    {
        $data = $this->data;
        $id = S($data['token']);
        $title = !empty($data['title'])?$data['title']:$this->ApiReturn(-1, '标题不能为空');
        $img_url = !empty($data['img_url'])?$data['img_url']:'/public/images/videopic.jpg';
        $video_url = !empty($data['video_url'])?$data['video_url']:$this->ApiReturn(-1, '视频不能为空');
        $price = !empty($data['price'])?$data['price']:$this->ApiReturn(-1, '价格不能为空');
        $content = !empty($data['content'])?$data['content']:$this->ApiReturn(-1, '课程说明不能为空');
        $t_sub1 = !empty($data['sub1'])?$data['sub1']:$this->ApiReturn(-1, '一级分类不能为空');
        $t_sub2 = !empty($data['sub2'])?$data['sub2']:$this->ApiReturn(-1, '二级分类不能为空');

        $config = M('Config')->find();
        M('OnlineClass')->add([
            'title'         =>  $title,
            'tid'           =>  $id,
            'u_type'        =>  1,      //用户类型 1.老师 2.商户 3.后台
            'img_url'       =>  $img_url,
            'video_url'     =>  $video_url,
            'oc_time'       =>  date('Y-m-d H:i:s'),
            'price'         =>  $price,
            'vip_price'     =>  $price*$config['vip_radio'],
            'content'       =>  $content,
            't_sub1'        =>  $t_sub1,
            't_sub2'        =>  $t_sub2,
        ]);
        $this->ApiReturn(1, '上传成功');
    }
    //下架或删除
    public function shelves(){
        $data = $this->data;
        $tid = S($data['token']);
        $type = $data['type'];
        $id = $data['class_id'];
        if(!$id)$this->ApiReturn(-1,'缺少必传参数class_id');
        if(!in_array($type,[1,2,3]))$this->ApiReturn(-1,'参数错误');      //1下架  2删除  3.上架
        if($type == 1){
            //status 1.上架 2.下架 3.删除
            $info = M("online_class")->where(['id'=>$id,'tid'=>$tid, 'u_type'=>1])->save(['status'=>2]);
        }elseif($type == 2){
            $info = M('online_class')->where(['id'=>$id,'tid'=>$tid, 'u_type'=>1])->save(['status'=>3]);
        }else{
            $info = M("online_class")->where(['id'=>$id,'tid'=>$tid, 'u_type'=>1])->save(['status'=>1]);
        }
        if($info){
            $this->ApiReturn(1,'操作成功');
        }else{
            $this->ApiReturn(-1,'操作失败');
        }
    }
    //我的视频课  订单详情
    public function class_detail(){
        $data = $this->data;
        $tid = S($data['token']);
        $id = $data['class_id'];

        if(!$id)$this->ApiReturn(-1,'缺少必传参数class_id');
        $level = M("teacher")->alias('m')->join("left join fzm_level l on m.level_id = l.id")->where(['m.id'=>$tid])->field('l.id,l.name')->find();
        $field = 'id,title,img_url, video_url,sale_num,price, content';
        $arr = M("online_class")->where(['id'=>$id])->field($field)->find();
        $this->ApiReturn(1,'ok',$arr);
    }

    /**
     * 线上课堂订单
     * sunfan
     * 2018.8.6
     */
    public function getClassOrder()
    {
        $data = $this->data;
        $tid = S($data['token']);
        $id = $data['class_id'];
        $where['o.status'] = 2;
        $where['o.oc_id'] = $id;
        $page = $data['page']?$data['page']:1;
        $list = M("OnlineClassOrder")->alias('o')
            ->join("left join fzm_parents p on o.parent_id = p.id")
            ->where($where)
            ->order('o.o_time desc')
            ->page($page)
            ->field('o.sn,p.parent_name as child_name ,p.p_img,o.o_time')
            ->select();
        if (!$list){
            $this->ApiReturn(0, 'success');
        }
        $this->ApiReturn(1, 'success', $list);
    }

    //评论列表
    public function getClassEva(){
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $evaluate = D("OnlineEvaluate");
        $where['o.c_id'] = $olid = $data['class_id']??$this->ApiReturn(-1, 'class_id不能为空');
        $list = $evaluate->alias('o')
            ->join("LEFT JOIN fzm_parents p on o.uid = p.id")
            ->page($page)
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
        $where['u_type'] = 2;
        $where['type'] = 2;
        $info = M('teacher')->where(array('id'=>$id))->find();
        $return['balance'] = $info['balance'];

        $List = M('Application')
            ->limit(($page-1)*10,10)
            ->order('a_time desc')
            ->field('uid,money,a_time,number,status,reason')
            ->where($where)->select();
        if(!$List){
            $this->ApiReturn(1,'没有记录',$return);
        }
        $return['info'] = $List;
        $this->ApiReturn(1,'成功',$return);
    }

    //选择年级
    public function chose()
    {
        $data = $this->data;
        $id = S($data['token']);
        $list = M('Grade')->select();
        if(!$list){
            $this->ApiReturn(0,'没有年级列表');
        }
        $this->ApiReturn(1,'成功',$list);
    }

    //选择科目列表
    public function subject()
    {
        $list = M('Subject')->where(array('parentid'=>0, 'show'=>1))->order('sort asc')->select();
        foreach($list as &$v){
            $v['child'] = M('Subject')->where(array('parentid'=>$v['id']))->order('sort asc')->select();
            foreach ($v['child'] as &$value) {
                $value['child'] = M('Subject')->where(array('parentid'=>$value['id']))->order('sort asc')->select();
            }
        }
        if(!$list){
            $this->ApiReturn(0,'没有科目列表');
        }
        $this->ApiReturn(1,'成功',$list);
    }

    //添加科目
    public function add_sbj()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['grade'] ==""){
            $this->ApiReturn(-1, '年级不存在');
        }
        if($data['sub1'] ==""){
            $this->ApiReturn(-1, '分类1不存在');
        }
        if($data['sub2'] ==""){
            $this->ApiReturn(-1, '分类2不存在');
        }
        if($data['price'] ==""){
            $this->ApiReturn(-1, '课时费不存在');
        }
        if($data['price'] >1000000){
            $this->ApiReturn(-1, '课时费太高，不符合授课要求');
        }
        /*if(float($data['price']) ==0){
            $this->ApiReturn(-1, '课时费不能为0');
        }*/

        if($data['description'] ==""){
            $this->ApiReturn(-1, '描述不能为空');
        }

        $add = M('TeacherSubject')->add(array('teacher_id'=>$id,'grade'=>$data['grade'],'sub1'=>$data['sub1'],'sub2'=>$data['sub2'],'price'=>$data['price'], 'description'=>$data['description']));

        $find = M('Teacher')->where(array('id'=>$id))->find();   //科目
        $where['t_sub2'] = $find['t_sub2'].$data['sub2'].',';

        $price = M('TeacherSubject')->where(array('teacher_id'=>$id))->order('price asc')->find();  //价格
        if($price){
            $where['price'] = $price['price'];
        }

        M('Teacher')->where(array('id'=>$id))->save($where);
         if(!$add){
            $this->ApiReturn(-1,'添加失败');
        }
        $this->ApiReturn(1,'添加成功');
    }

    //编辑科目
    public function editSbj()
    {
        $data = $this->data;
        $id = S($data['token']);
        $sid = $data['id']??$this->ApiReturn(-1, 'id必传');
        !empty($data['grade'])?$map['grade'] = $data['grade']:1;
        !empty($data['sub1'])?$map['sub1'] = $data['sub1']:1;
        !empty($data['sub2'])?$map['sub2'] = $data['sub2']:1;
        !empty($data['price'])?$map['price'] = $data['price']:1;
        !empty($data['description'])?$map['description'] = $data['description']:1;
        !empty($data['price'])&&$data['price']>1000000?$this->ApiReturn(-1, '课时费太高，不符合授课要求'):1;
        $old = M('TeacherSubject')->where(['id'=>$sid])->find();
        M('TeacherSubject')->where(['id'=>$sid])->save($map);

        $find = M('Teacher')->where(array('id'=>$id))->find();   //科目
        $where['t_sub2'] = str_replace($old['t_sub2'], $data['sub2'], $find['t_sub2']);

        $price = M('TeacherSubject')->where(array('teacher_id'=>$id))->order('price asc')->find();  //价格
        if($price){
            $where['price'] = $price['price'];
        }

        M('Teacher')->where(array('id'=>$id))->save($where);
        $this->ApiReturn(1,'成功');
    }

    /**
     * 获取科目详情
     * sunfan
     * 2018.8.6
     */
    public function getSubjectDetail()
    {
        $data = $this->data;
        $id = S($data['token']);
        $sub_id = $data['id']??$this->ApiReturn(-1, 'id不能为空');
        $rs = M('TeacherSubject')->where(['id'=>$sub_id])->field('id, grade, sub1, sub2, price, description')->find();
        $this->ApiReturn(1, 'success', $rs);
    }

    //科目设置列表

    public function sbj_list()
    {
        $data = $this->data;
        $id = S($data['token']);
        $list = M('TeacherSubject')->where(array('teacher_id'=>$id))->select();
        if(!$list){
            $this->ApiReturn(0,'列表空');
        }
        $this->ApiReturn(1,'成功',$list);
    }

    //科目删除
    public function del_sbj()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['id'] ==""){
            $this->ApiReturn(-1, 'id不存在');
        }
        $sub2 = "";
        $del = M('TeacherSubject')->where(array('id'=>$data['id']))->delete();

        $list = M('TeacherSubject')->where(array('teacher_id'=>$id))->select();
        if($list){
            foreach($list as $k=>$v){
                $sub2 =$sub2.$v['sub2'].',';
            }
        }
        $where['t_sub2'] = $sub2;

        $price = M('TeacherSubject')->where(array('teacher_id'=>$id))->order('price asc')->find();
        if($price){
            $where['price'] =  $price['price'];
        }else{
            $where['price'] =  0;
        }
        M('Teacher')->where(array('id'=>$id))->save($where);

        if(!$del){
            $this->ApiReturn(-1,'失败');
        }
        $this->ApiReturn(1,'成功');
    }

    //时间设置列表
    public function date_list()
    {
        $data = $this->data;
        $id = S($data['token']);
        $list = M('TeacherTime')->field('id,teacher_id,day')->where(array('teacher_id'=>$id))->group('day')->select();
        foreach($list as $k=>$v){
            $list[$k]['date'] = M('TeacherTime')->where(array('teacher_id'=>$v['teacher_id'],'day'=>$v['day']))->select();
        }
        if(!$list){
            $this->ApiReturn(0,'空列表');
        }
        $this->ApiReturn(1,'成功',$list);
    }

    //时间设置添加
    public function date_add()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['day'] ==""){
            $this->ApiReturn(-1, '日期不存在');
        }
        if($data['start'] ==""){
            $this->ApiReturn(-1, '开始时间不存在');
        }
        if($data['end'] ==""){
            $this->ApiReturn(-1, '结束时间不存在');
        }
        $add = M('TeacherTime')->add(array('teacher_id'=>$id,'day'=>$data['day'],'start'=>$data['start'],'end'=>$data['end']));
        if(!$add){
            $this->ApiReturn(-1,'失败');
        }
        $this->ApiReturn(1,'成功');
    }

    //时间消除
    public function date_del()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['id'] ==""){
            $this->ApiReturn(-1, 'id不存在');
        }
        $del = M('TeacherTime')->where(array('id'=>$data['id']))->delete();
        if(!$del){
            $this->ApiReturn(-1,'失败');
        }
        $this->ApiReturn(1,'成功');
    }

    //批量时间消除
    public function dat_batch_del()
    {
        $data = $this->data;
        $id = S($data['token']);
        $idstr = $data['ids']??$this->ApiReturn(-1, 'ids不存在');
        $idattr = explode(',', $idstr);
        foreach ($idattr as $item){
            $del = M('TeacherTime')->where(array('id'=>$item))->delete();
        }
        $this->ApiReturn(1,'成功');
    }

    //添加银行卡
    public function bank_add()
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
        $add = M('Bank')->add(array('teacher_id'=>$id,'card'=>$data['card'],'name'=>$data['name'],'address'=>$data['address'],'phone'=>$data['phone']));
        if(!$add){
            $this->ApiReturn(-1,'失败');
        }
        $this->ApiReturn(1,'成功');
    }

    //银行卡列表
    public function bank_list()
    {
        $data = $this->data;
        $id = S($data['token']);
        $list =  M('Bank')->where(array('teacher_id'=>$id))->select();
        if(!$list){
            $this->ApiReturn(0,'没有信息');
        }
        $this->ApiReturn(1,'成功',$list);
    }

    //银行卡编辑

    public function bank_edit()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['id'] ==""){
            $this->ApiReturn(-1, 'id不存在');
        }
        $del = M('Bank')->where(array('teacher_id'=>$id,'id'=>array('in',$data['id'])))->delete();
        if(!$del){
            $this->ApiReturn(-1,'编辑失败');
        }
        $this->ApiReturn(1,'成功');
    }


    //提现申请获取手机号
    public function phone()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['id'] ==""){
            $this->ApiReturn(-1, '银行卡id不存在');
        }
        $info = M('Bank')->where(array('id'=>$data['id']))->find();
        if(!$info){
            $this->ApiReturn(0,'没有信息');
        }
        $this->ApiReturn(1,'成功',$info);
    }

    //提现申请提交
    public function apply()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['money'] ==""){
            $this->ApiReturn(-1, '金额不存在');
        }
        if($data['money'] <= 0){
            $this->ApiReturn(-1, '金额输入有误,请重新输入！');
        }
        /*if($data['card'] ==""){
            $this->ApiReturn(-1, '卡号不存在');
        }*/
        $card_num = $data['card']??$this->ApiReturn(-1, '请选择银行卡');
        if($data['phone'] ==""){
            $this->ApiReturn(-1, '手机号不存在');
        }
        if($data['code'] ==""){
            $this->ApiReturn(-1, '验证码不存在');
        }
        $config = M('Config')->find();
        $find = M('Teacher')->where(array('id'=>$id))->find();

        if($find['balance'] < $config['quota']){
            $this->ApiReturn(-1, '您的余额不满足最低提现额度');
        }

        if($find['balance'] < $data['money']){
            $this->ApiReturn(-1, '余额不足，不能提现');
        }
        checkPhoneCode($data['phone'], $data['code']);

        $card = M('Bank')->where(['teacher_id'=>$id, 'card'=>$card_num])->find();
        if (!$card){
            $this->ApiReturn(-1, '银行卡不存在');
        }

        $add = M('Application')->add([
            'uid'=>$id,
            'u_type'=>2,
            'money'=>$data['money'],
            'a_time'=>date('Y-m-d H:i:s'),
            'number'=>$card['card'],
            'status'=>1,
            'type'=>2,
            'card_name'=>$card['name'],
            'address'=>$card['address'],
            'phone'=>$card['phone'],
            'sn'    =>  'W'.sp_get_order_sn()
        ]);
       //冻结金额
        $find = M('Teacher')->where(array('id'=>$id))->find();
        $other = $find['balance']- $data['money'];
        $freeze = M('Teacher')->where(array('id'=>$id))->save(array('balance'=>$other,'freeze'=>$data['money']));
        if(!$add){
            $this->ApiReturn(-1,'提交失败');
        }
       $this->ApiReturn(1,'提交成功');
    }
    //提现到支付宝
    public function ali_apply()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['number'] ==""){
            $this->ApiReturn(-1, '请输入支付宝账号');
        }
        if($data['card_name'] ==""){
            $this->ApiReturn(-1, '请输入用户名');
        }
        if($data['money'] ==""){
            $this->ApiReturn(-1, '金额不存在');
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
        $find = M('Teacher')->where(array('id'=>$id))->find();

        if($find['balance'] < $config['quota']){
            $this->ApiReturn(-1, '您的余额不满足最低提现额度');
        }

        if($find['balance'] < $data['money']){
            $this->ApiReturn(-1, '余额不足，不能提现');
        }
        checkPhoneCode($data['phone'], $data['code']);

        $add = M('Application')->add([
            'uid'=>$id,
            'u_type'=>2,
            'money'=>$data['money'],
            'a_time'=>date('Y-m-d H:i:s'),
            'number'=>$data['number'],
            'status'=>1,
            'type'=>2,
            'card_name'=>$data['card_name'],
            'phone'=>$data['phone'],
            't_type'=>2,
            'sn'    =>  'W'.sp_get_order_sn()
        ]);
        //冻结金额
        $find = M('Teacher')->where(array('id'=>$id))->find();
        $other = $find['balance']- $data['money'];
        $freeze = M('Teacher')->where(array('id'=>$id))->save(array('balance'=>$other,'freeze'=>$data['money']));
        if(!$add){
            $this->ApiReturn(-1,'提交失败');
        }
        $this->ApiReturn(1,'提交成功');
    }

    //是否可以提交申请
    public function check_apply()
    {
        $data = $this->data;
        $id = S($data['token']);
        $find = M('Application')->where(array('uid'=>$id,'status'=>1,'type'=>2, 'u_type'=>2))->find();
        if($find){
            $arr['type'] = 1;   //不可以
        }else{
            $arr['type'] = 2;    //可以
        }
        $this->ApiReturn(1,'成功',$arr);
    }

    //修改密码
    public function edit_paw()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['password'] ==""){
            $this->ApiReturn(-1, '原密码不存在');
        }
        if($data['newpassword'] ==""){
            $this->ApiReturn(-1, '新密码不存在');
        }
        if($data['checkpassword'] ==""){
            $this->ApiReturn(-1, '确认密码不存在');
        }
        if($data['checkpassword'] != $data['newpassword']){
            $this->ApiReturn(-1, '俩次密码不一致');
        }
        $find = M('Teacher')->where(array('id'=>$id))->find();
        if($find['password'] != md5($data['password'])){
            $this->ApiReturn(-1, '原密码不正确');
        }
        $save = M('Teacher')->where(array('id'=>$id))->save(array('password'=>md5($data['newpassword'])));
        $this->ApiReturn(1,'修改成功');
    }

    //基础资料
    public function author()
    {
        $data = $this->data;
        $id = S($data['token']);
        $info = M('Teacher')->field('id,name,province,city,area,school,t_img,age,teach_age,sex,position,address,other,t_grade')->where(array('id'=>$id))->find();
        if(!$info){
            $this->ApiReturn(0,'信息不存在');
        }
        $this->ApiReturn(1,'success',$info);
    }

    public function auth_edit()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['name'] == ""){
            $this->ApiReturn(-1, '名字不存在');
        }
        if($data['province'] == ""){
            $this->ApiReturn(-1, '省不存在');
        }
        if($data['city'] == ""){
            $this->ApiReturn(-1, '市不存在');
        }
        if($data['area'] == ""){
            $this->ApiReturn(-1, '区不存在');
        }
        if($data['position'] == ""){
            $this->ApiReturn(-1, '位置不存在');
        }
        if($data['address'] == ""){
            $this->ApiReturn(-1, '详细地址不存在');
        }
        if($data['age'] != ""){
            $where['age'] = $data['age'];
        }
        if($data['sex'] != ""){
            $where['sex'] = $data['sex'];
        }
        if($data['teach_age'] != ""){
            $where['teach_age'] = $data['teach_age'];
        }
        if($data['t_img'] != ""){
            $where['t_img'] = $data['t_img'];
        }
        if($data['school'] != ""){
            $where['school'] = $data['school'];
        }
        if($data['t_grade'] != ""){
            $where['t_grade'] = $data['t_grade'];
        }
        $where['name'] = $data['name'];
        $where['province'] = $data['province'];
        $where['city'] = $data['city'];
        $where['area'] = $data['area'];
        $where['other'] = $data['other'];
        $where['position'] = $data['position'];
        $where['address'] = $data['address'];

        $save = M('Teacher')->where(array('id'=>$id))->save($where);
        $this->ApiReturn(1,'提交成功');
    }

    //师资简介
    public function introduction()
    {
        $data = $this->data;
        $id = S($data['token']);
        $info = M('Teacher')->field('experience,profession,features,school,t_grade,t_sub2')->where(array('id'=>$id))->find();
        foreach($info as $k=>$v){
            if($v == null){
                $info[$k] = "";
            }
        }
        $honor = M('TeacherHonor')->where(array('teacher_id'=>$id))->select();
        $info['honor'] = $honor;
        $banner = M('TeacherBanner')->where(array('teacher_id'=>$id))->select();
        $info['banner'] = $banner;
        if(!$info){
            $this->ApiReturn(-1,'师资简介为空');
        }
        $this->ApiReturn(1,'师资简介成功',$info);
    }

    //毕业院校
    public function school()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['school'] != ""){
            $where['school'] = $data['school'];
        }
        if($data['experience'] != ""){
            $where['experience'] = $data['experience'];
        }
        if($data['profession'] != ""){
            $where['profession'] = $data['profession'];
        }
        if($data['features'] != ""){
            $where['features'] = $data['features'];
        }
        if($data['t_grade'] != ""){
            $where['t_grade'] = $data['t_grade'];
        }
        if($data['t_sub2'] != ""){
            $where['t_sub2'] = $data['t_sub2'];
        }
        $save = M('Teacher')->where(array('id'=>$id))->save($where);
        if(!$save){
            $this->ApiReturn(-1,'失败');
        }
        $this->ApiReturn(1,'成功');
    }

    //所获荣誉
    public function honor()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['url'] == ""){
            $this->ApiReturn(-1, '图片不存在');
        }
        if($data['msg'] == ""){
            $this->ApiReturn(-1, '描述不存在');
        }
        $url = image($data['url']);
        $save = M('TeacherHonor')->add(array('teacher_id'=>$id,'url'=>$url,'msg'=>$data['msg']));
        if(!$save){
            $this->ApiReturn(-1,'失败');
        }
        $this->ApiReturn(1,'成功');
    }
    //所获荣誉删除
    public function honor_del()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['id'] == ""){
            $this->ApiReturn(-1, 'id不存在');
        }
        $del = M('TeacherHonor')->where(array('teacher_id'=>$id,'id'=>$data['id']))->delete();
        if(!$del){
            $this->ApiReturn(-1,'失败');
        }
        $this->ApiReturn(1,'成功');
    }

    //用户信息
    public function info()
    {
        $data = $this->data;
        $token = $data['token']??$this->ApiReturn(-1, 'token无效或已过期');
        $id = S($token);
        $db_user=M("Teacher");
        $info = $db_user->where('id='.$id)->find();
        $arr=array();
        $arr['phone'] = $info['phone'];
        $arr['name'] = $info['name'];
        $arr['gender'] = $info['sex'];
        $arr['age'] = $info['age'];
        $arr['score'] = $info['score'];
        $arr['teach_age'] = $info['teach_age'];
        $arr['headImg'] = $info['t_img'];
        $arr['state'] = $info['state'];
        if($arr['state'] == 3){
            $arr['fail_reason'] = $info['fail_reason'];
        }
        $arr['t_type'] = $info['t_type'];
        $arr['level'] = $info['level_id'];
        $arr['level_name'] = M('Level')->where(['id'=>$info['level_id']])->getField('name');
        $arr['level_icon'] = M('Level')->where(['id'=>$info['level_id']])->getField('icon');

        $order = M('Order');
        $arr['student_num'] = $order->where(array('teacher_id'=>$id, 'status'=>array('in', array(1,2,3))))->count();       //订单数（学生数）
        $find = $order->where(array('teacher_id'=>$id, 'status'=>array('in', array(1,2,3))))->select();  //总课时
        $tomal = "";
        if($find){
            foreach($find as $k=>$v){
                $tomal +=$v['class_num'] *30;
            }
            $arr['class_num']  = $tomal;
        }else{
            $arr['class_num']  = 0;
        }

        $star = $order->where(array('teacher_id'=>$id, 'status'=>array('in', array(-1,1,2))))->count();  //进行课时
        /*$star_class = "";
        foreach($star as $k1=>$v1){
            $star_class +=$v1['class_num'];
        }
        if($star_class){
            $arr['star_class']  = $star_class;
        }else{
            $arr['star_class']  = 0;
        }*/
        $arr['star_class'] = $star;

        $select = M('assess')->select();         //评价个数
        $count = 0;
        if($select){
            foreach($select as $k=>$v){
                $find = M('Order')->where(array('id'=>$v['order_id'],'teacher_id'=>$id))->find();
                if($find){
                    $count++;
                }

            }
        }
        $arr['pingjia'] = $count;
        //认证
        if ($info['t_type']==1 || $info['t_type']==2){
            if(!empty($info['idcard1']) &&!empty($info['idcard2'])&&!empty($info['hand_card'])){
                $arr['certification'] = 1;
            }else{
                $arr['certification'] = -1;
            }
        }else{
            if(!empty($info['t_card']) &&!empty($info['academic'])&&!empty($info['nationality'])){
                $arr['certification'] = 1;
            }else{
                $arr['certification'] = -1;
            }
        }

        //资质
        if(!empty($info['t_card'])){
            $arr['qualification'] = 1;
        }else{
            $arr['qualification'] = -1;
        }
        //学历
        if(!empty($info['academic'])){
            $arr['education'] = 1;
        }else{
            $arr['education'] = -1;
        }
        if(!empty($info['evaluation'])){
            $arr['evaluation'] = 1;
        }else{
            $arr['evaluation'] = -1;
        }

        //会员是否开启的功能
        $arr['tvip_option'] = M('Config')->getField('tvip_option');
        $this->ApiReturn(1, '成功', $arr);
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

        //如果有相同的会员等级，未支付的订单，用那个，否则生成一个新的
        $old_order = M('VipOrder')->where([
            'uid'   =>  $id,
            'u_type'=>  2,      //1家长 2老师 3托管/教育机构
            'vip_level_id'=>$lid,
            'status'=>  1,  //1 未支付 2已支付
        ])->find();
        if ($old_order){
            $id = $old_order['id'];
            $sn = $old_order['sn'];
        }else{
            $sn = 'V'.sp_get_order_sn();
            $id = M('VipOrder')->add([
                'sn'    =>  $sn,
                'uid'   =>  $id,
                'u_type'=>  2,
                'vip_level_id'=>$lid,
                'o_time'=>  date('Y-m-d H:i:s'),
                'money' =>  $level['money'],
                'time_length' =>  $level['time_length']
            ]);
        }
        if ($id){
            $this->ApiReturn(1, 'success', ['sn'=>$sn]);
        }else{
            $this->ApiReturn(-1, '系统错误');
        }
    }
}