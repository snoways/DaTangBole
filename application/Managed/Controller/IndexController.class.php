<?php
namespace Managed\Controller;

use Admin\Controller\SmalltableController;
use Common\Controller\AdminbaseController;
use Think\Controller;

class IndexController extends ManagedBaseController
{
	public function index()
    {
        //今日数据统计
        $jiazhang_count = M("parents")->where(['p_time' => ['like', date('Y-m-d') . '%']])->count();
        $jiaoyujigou_count = M("small_table")->where(['s_type' => 2, 's_status' => ['LT', 2], 's_time' => ['like', date('Y-m-d') . '%']])->count();
        $tuoguanjigou_count = M("small_table")->where(['s_type' => 1, 's_status' => ['LT', 2], 's_time' => ['like', date('Y-m-d') . '%']])->count();
        $kecheng_count = M("educational_course")->where(['add_time' => ['like', date('Y-m-d') . '%']])->count();
        $huodong_count = M("activity")->where(['status' => 1, 'add_time' => ['like', date('Y-m-d') . '%']])->count();
        $dailishangtixian_count = M("application")->where(['u_type' => 3, 'type' => 2, 'status' => 1, 'a_time' => ['like', date('Y-m-d') . '%']])->count();
        $huodong_total_amount = M("ActivityOrder")->where(['status' => 2, 'sign_time' => ['like', date('Y-m-d') . '%']])->sum('pay_money');
        $huodong_fenrun_amount = M("ActivityOrder")->where(['status' => 2, 'sign_time' => ['like', date('Y-m-d') . '%']])->sum('platform_money');

        $jinri_statistic = [
            jiazhang_count => $jiazhang_count,
            jiaoyujigou_count => $jiaoyujigou_count,
            tuoguanjigou_count => $tuoguanjigou_count,
            kecheng_count => $kecheng_count,
            huodong_count => $huodong_count,
            dailishangtixian_count => $dailishangtixian_count,
            huodong_total_amount => $huodong_total_amount ?? 0,
            huodong_fenrun_amount => $huodong_fenrun_amount ?? 0
        ];
        $this->assign('jinri_statistic', $jinri_statistic);

        $this->display();
    }



    /**
     * 亲子活动订单状态统计
     * */
    public function getHuoDongPieData()
    {
        $startDate = I('post.startDate');
        $endDate = I('post.endDate');
        if ($this->diffBetweenTwoDays($startDate, $endDate) > 60) {
            echo "-1";
            exit();
        }
		$is_y = M("activity")->where(['shop_id' => $_SESSION['small_id']])
			->field('id')
            ->select();
		foreach($is_y as $k=>$v){
			$is_yarr[]=$v['id'];
			}	
		$is_yid=implode(',',$is_yarr);
        $where = [
			'activity_id'=>['in',$is_yid],
            'status' => ['in', [1, 2, 3, 4, 5]],
            'sign_time' => ['between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]
        ];
        $pie_data = [];
        $list_data = M("activity_order")->where($where)
            ->field('activity_id,status, count(1) as total')
            ->group('status')->select();
        $order_status = [1 => '待支付', 2 => '报名成功', 3 => '申请退款', 4 => '已退款', 5 => '已取消'];
        foreach ($list_data as $key => $value) {
			$pie_data[$key]['value'] = $list_data[$key]['total'];
            $pie_data[$key]['name'] = $order_status[$list_data[$key]['status']];
			
            
        }
        echo json_encode($pie_data);
    }

    /**
     * 在线课程订单状态统计
     * */
    public function getKeChengPieData()
    {
        $startDate = I('post.startDate');
        $endDate = I('post.endDate');
        if ($this->diffBetweenTwoDays($startDate, $endDate) > 60) {
            echo "-1";
            exit();
        }
        $where = [
			'st_id' => $_SESSION['small_id'],
            'status' => ['in', [1, 2, 3, 4, 5]],
            'o_time' => ['between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]
        ];
        $pie_data = [];
        $list_data = M("educational_order")->where($where)
            ->field('status, count(1) as total')
            ->group('status')->select();
        $order_status = [1 => '待支付', 2 => '进行中', 3 => '已完成', 4 => '已取消', 5 => '已退款'];
        foreach ($list_data as $key => $value) {
            $pie_data[$key]['value'] = $list_data[$key]['total'];
            $pie_data[$key]['name'] = $order_status[$list_data[$key]['status']];
        }
        echo json_encode($pie_data);
    }

    /**
     * 托管订单状态统计
     * */
    public function getTuoGuanPieData()
    {
        $startDate = I('post.startDate');
        $endDate = I('post.endDate');
        if ($this->diffBetweenTwoDays($startDate, $endDate) > 60) {
            echo "-1";
            exit();
        }
        $where = [
			'st_id' => $_SESSION['small_id'],
            'status' => ['in', [1, 2, 3, 4, 5]],
            'o_time' => ['between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]
        ];
        $pie_data = [];
        $list_data = M("hosting_order")->where($where)
            ->field('status, count(1) as total')
            ->group('status')->select();
        $order_status = [1 => '待支付', 2 => '进行中', 3 => '已完成', 4 => '已取消', 5 => '已退款'];
        foreach ($list_data as $key => $value) {
            $pie_data[$key]['value'] = $list_data[$key]['total'];
            $pie_data[$key]['name'] = $order_status[$list_data[$key]['status']];
        }
        echo json_encode($pie_data);
    }

    /**
     * 各个订单金额统计
     * */
    public function getOrderAmountData()
    {
        $startDate = I('post.startDate');
        $endDate = I('post.endDate');
        if ($this->diffBetweenTwoDays($startDate, $endDate) > 60) {
            echo "-1";
            exit();
        }
		$is_y = M("activity")->where(['shop_id' => $_SESSION['small_id']])
			->field('id')
            ->select();
		foreach($is_y as $k=>$v){
			$is_yarr[]=$v['id'];
			}	
		$is_yid=implode(',',$is_yarr);
        $huodong_amount_data = M("activity_order")->where(['status' => 2, 'sign_time' => ['between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']],'activity_id'=>['in',$is_yid]])
            ->field("date_format(sign_time,'%Y-%m-%d') as date, sum(pay_money) as amount")
            ->group("date_format(sign_time,'%Y-%m-%d')")->select();

        $kecheng_amount_data = M("educational_order")->where(['st_id'=>$_SESSION['small_id'], 'status' => 2, 'o_time' => ['between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]])
            ->field("date_format(o_time,'%Y-%m-%d') as date, sum(total_money) as amount")
            ->group("date_format(o_time,'%Y-%m-%d')")->select();

        $tuoguan_amount_data = M("hosting_order")->where(['st_id'=>$_SESSION['small_id'], 'status' => 2, 'o_time' => ['between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]])
            ->field("date_format(o_time,'%Y-%m-%d') as date, sum(total_money) as amount")
            ->group("date_format(o_time,'%Y-%m-%d')")->select();

        $line_data = [];
        $start_time = strtotime($startDate);
        $end_time = strtotime($endDate);
        for ($day = $start_time; $day <= $end_time; $day += 24 * 3600) {
            $date = date('Y-m-d', $day);
            $line_data['huodong'][] = [
                'date' => $date,
                'amount' => $this->getDataFromArray($huodong_amount_data, $date) / 10000
            ];
            $line_data['kecheng'][] = [
                'date' => $date,
                'amount' => $this->getDataFromArray($kecheng_amount_data, $date) / 10000
            ];
            $line_data['tuoguan'][] = [
                'date' => $date,
                'amount' => $this->getDataFromArray($tuoguan_amount_data, $date) / 10000
            ];
        }
//        echo json_encode(M("educational_order")->getLastSql());
        echo json_encode($line_data);
    }


    private function getDataFromArray($array, $date)
    {
        foreach ($array as $key => $value) {
            if ($date == $value['date']) {
                return $value['amount'];
            }
        }
        return 0;
    }

    /**
     * 求两个日期之间相差的天数
     * (针对1970年1月1日之后，求之前可以采用泰勒公式)
     * @param string $day1
     * @param string $day2
     * @return number
     */
    function diffBetweenTwoDays($day1, $day2)
    {
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);

        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        return ($second1 - $second2) / 86400;
    }
    public function person(){
        $small_id = $_SESSION['small_id'];
        if(IS_POST){
            if($_POST['smeta']['thumb']){
                $_POST['s_img'] = $_POST['smeta']['thumb'];
            }
            $add = M('small_table')->where(array('id'=>$small_id))->save($_POST);
            unset($_POST['id']);
            $add = M('small_table_sort')->where(array('sid'=>$small_id))->save($_POST);
            if($add !== false){
                $this->success("保存成功！",U("index/person"));
            }
        }else{
            $info = M('small_table')->where(array('id'=>$small_id))->find();
            $this->assign('info',$info);

            $level = M('Level')->select();
            $this->assign('level', $level);
            $this->display();
        }
    }

    public function add_small()
    {
        $small_id = $_SESSION['small_id'];
        $small_table = M('small_table');
        if (IS_POST && $small_id) {
            $_POST['agent_parent_id'] = $small_id;
            $parent_ids = SmalltableController::getParentIs($small_id);
            $_POST['agent_path'] = '0_' . implode('_', $parent_ids) . "_";
            if (!$_POST['smeta']['thumb']) $this->error('请上传logo');
            if (!preg_match('/^1[0-9]{10}$/',$_POST['s_phone'])) $this->error('请输入正确的手机号');
            if ($small_table->where(['s_phone'=>$_POST['s_phone']])->find())  $this->error('手机号已存在');
            $_POST['s_img'] = strpos($_POST['smeta']['thumb'], '/') == 0 ? $_POST['smeta']['thumb'] : '/' . $_POST['smeta']['thumb'];
            $_POST['s_password'] = md5(substr($_POST['s_phone'], -6));
            $_POST['s_status'] =1;//待审核
            $_POST['s_time'] = date('Y-m-d H:i:s');
            if ($_POST['id']) {
                if ($info = $small_table->where(['id'=>$_POST['id']])->find()) {
                    if ($info['s_status'] == 3) {
                        $add = $small_table->save($_POST);
                    } else {
                        $add = $small_table->add($_POST);
                    }
                }
            } else {
                $add = $small_table->add($_POST);
            }
            if($add !== false){
                $this->success("保存成功！",U("index/sub_list"));
            }
        } else {
            $id = I('id');
            if ($id) {
                $info = $small_table->where(['id'=>$id])->find();
                if ($info['s_status'] == 3) {
                    $this->assign('info',$info);
                }
            }
            $this->display();
        }
    }

    public function sub_list()
    {
        $where['st.agent_parent_id'] = $_SESSION['small_id'];
        if (I('keyword')) {
            $where['st.address|st.s_name|st.s_phone|st.c_name'] = ['like', '%' . I('keyword') . '%'];
        }
        if (I('s_type')) {
            $where['st.s_type'] = I('s_type');
        }
        if (I('s_status')) {
            $where['st.s_status'] = I('s_status');
        }
        if (I('is_home')) {
            $where['st.is_home'] = I('is_home');
        }
        $count = M('small_table')->alias('st')->where($where)->count();
        $page = $this->page($count, 10);
        $list = M('small_table')
            ->alias('st')
            ->join('LEFT JOIN fzm_level l ON l.id=st.level_id')
            ->join('LEFT JOIN fzm_small_table st2 ON st.agent_parent_id=st2.id')
            ->where($where)
            ->order(['st.sort' => 'asc', 'st.s_time' => 'desc'])
            ->limit($page->firstRow, $page->listRows)
            ->field('st.*, st2.id as parent_id, st2.s_name as parent_name, l.name as level_name')
            ->select();
        foreach ($list as $key => $value) {
            $list[$key]['parent_name'] = $value['parent_name'] == null ? '平台' : $value['parent_name'];
            switch ($value['s_type']) {
                case 1:
                    $list[$key]['s_type'] = "托管机构";
                    break;
                case 2:
                    $list[$key]['s_type'] = "教育机构";
                    break;
                case 3:
                    $list[$key]['s_type'] = "合作代理商";
                    break;
                case 4:
                    $list[$key]['s_type'] = "直属代理商";
                    break;
            }
        }
        $this->assign("list", $list);
        $this->assign("page", $page->show('Admin'));
        $this->display();
        $this->display();
    }

    public function updatePwd(){
        $small_id = $_SESSION['small_id'];
        if(IS_POST){
            $o_password = I('post.o_password');
            $s_password = I('post.s_password');
            $s_repassword = I('post.s_repassword');
//            dump($_POST);die;
            $msg = M('small_table')->where(array('id'=>$small_id))->find();
            if(md5($o_password) !== $msg['s_password']){
                $this->error('原密码输入错误！');
            }
            if($s_password != $s_repassword){
                $this->error('两次密码不一致！');
            }
            if(!strlen($s_password) > 6 ) {
                $this->error('密码位数不能少于6位！');
            }
            $data['s_password'] = md5($s_password);
            $info = M('small_table')->where(array('id'=>$small_id))->save($data);
            if($info !== false){
                $this->success("保存成功！",U("index/updatePwd"));
            }
        }else{
            $info = M('small_table')->where(array('id'=>$small_id))->find();
            $this->assign('info',$info);
            $this->display();
        }
    }
    public function img_add(){
        $id = $_SESSION['small_id'];
        $list = M('SmallImages')->where(['st_id'=>$id])->select();
        if($list[0]['st_id']){
            $image = $list;
        }else{
            $image = '';
        }
        $info = M('small_table')->where(array('id'=>$id))->find();
        $this->assign('info',$info);
        $this->assign('image',$image);
        $this->display();
    }
    public function insert(){
        $id = $_SESSION['small_id'];
        if(!$_FILES['url']['error'][0]){
            //实例化上传文件的类
            $upload = new \Think\Upload();
            //选择上传文件的大小
            $upload->maxSize = 3145728 ;
            //选择上传文件的类型
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            //将上传的图片存放在指定文件夹下
            $upload->rootPath = "./data/upload/admin/";
            $fileName=$_FILES['url']['name'];//这样就能够取得上传的文件名
            $fileExtensions=strrchr($fileName, '.');//通过对$fileName的处理，就能够取得上传的文件的后缀名
            $serverFileName=basename($fileName,$fileExtensions)."_".uniqid().$fileExtensions;
            $upload->saveRule=$serverFileName;
            //调用上传文件类中的upload方法
            $info = $upload -> upload();
            //判断调用是否成功
            $coun = count($_FILES['url']['name']);
            for ($i=0;$i<$coun;$i++){
                //得到要存入数据库中的地址
                $pic = $info[$i]["savepath"].$info[$i]['savename'];
                $data['url'] = "/data/upload/admin/".$pic;
                $data['st_id'] = $id;
                $data['url'] = image($data['url']);
                $rees = M('small_images')->add($data);
            }
            if($rees!==false){
                $this -> success("添加成功",U('index/img_add'));
            }else{
                $this -> error("添加失败");
            }
        }else{
            $this->error('没有图片被上传！');
        }
    }
    public function modlunbo(){
        $id = $_SESSION['small_id'];
        $st_id = $_POST['id'];
        if(!$_FILES['url']['error']){
            //实例化上传文件的类
            $upload = new \Think\Upload();
            //选择上传文件的大小
            $upload->maxSize = 3145728 ;
            //选择上传文件的类型
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            //将上传的图片存放在指定文件夹下
            $upload->rootPath = "./data/upload/admin/";
            $info = $upload -> upload();
            //得到要存入数据库中的地址
            if(!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            }else {// 上传成功 获取上传文件信息
                $data['url'] = "/data/upload/admin/" . $info['url']['savepath'] . $info['url']['savename'];
            }
        }
        $rees = M('small_images')->where(array('id'=>$id))->save($data);
//        echo M('small_images')->getLastSql();die;
        if ($rees !== false) {
            $this->success("保存成功");
        } else {
            $this->success("保存成功");
        }
    }

    public function delurl(){
        $pro = M('small_images');
        $st_id = I("st_id",0,'intval');
        $id = $_GET['id'];
        $tu1 = $pro->field('url')->find($id);
        $rootpath1 = './Date/Upload/admin/';
        //删除文件夹中的图片
        unlink('.'.$tu1['url']);
        $del = $pro->delete($id);
        if($del!==false){
            $this->success('删除成功');
        }else {
            $this->error("添加失败");
        }
    }

    //修改手机号
    public function editphone()
    {
        $data = $_POST;
        $id = $_SESSION['small_id'];
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
        $find = $user->where(array('id'=>$id))->find();
        if(!$find){
            $this->ApiReturn(-1, '用户不存在');
        }

        if ($user->where(array('s_phone'=>$data['newphone'], 'id'=>['neq', $find['id']]))->find()){
            $this->ApiReturn(-1, '手机号已存在');
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

}