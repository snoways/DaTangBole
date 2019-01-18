<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/4 0004
 * Time: 上午 10:21
 */

namespace Managed\Controller;

use Think\Page;
use Think\Controller;

class ActivityController extends ManagedBaseController
{

    public function index()
    {
//        if (M('SmallTable')->where(['id'=>$_SESSION['small_id']])->getField('level_id')<=1){
//            //不是会员
//            $this->display('updateTip');
//            exit();
//        }
        $where['a.status'] = ['neq', 4];
        $where['a.shop_id'] = $_SESSION['small_id'];
        if ($_REQUEST['keyword']){
            $where['a.title'] = ['like', ['%'.$_REQUEST['keyword'].'%']];
        }
        if ($_REQUEST['state'] != ''){
            $where['a.state'] = $_REQUEST['state'];

        }
        if ($_REQUEST['status']){
            $now = date('Y-m-d H:i:s');
            $where['a.status'] = $_REQUEST['status'];
            if ($_REQUEST['status']==1){
                //未结束
                $where['a.end'] = ['GT', $now];
            }elseif ($_REQUEST['status']==2){
                //已结束
                $where['a.end'] = ['ELT', $now];
            }
        }
        if ($_REQUEST['cate_id']){
            $where['a.cate_id'] = $_REQUEST['cate_id'];
        }
        $oauth_user_model=M('Activity');
        $count=$oauth_user_model
            ->alias('a')
            ->join('LEFT JOIN fzm_activity_cate ac ON ac.id=a.cate_id')
            ->join('LEFT JOIN fzm_small_table st ON st.id=a.shop_id')
            ->where($where)
            ->count();
        $page = $this->page($count, 15);
        $lists = $oauth_user_model
            ->alias('a')
            ->join('LEFT JOIN fzm_activity_cate ac ON ac.id=a.cate_id')
            ->join('LEFT JOIN fzm_small_table st ON st.id=a.shop_id')
            ->where($where)
            ->order("add_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.*, st.s_name, ac.name')
            ->select();

        //调取活动结束后返送给商家余额
//        activity_managed();

        $category = M('ActivityCate')->order('sort')->select();
        $category  = $this->cateDegree($category,0,1);
        $this->assign('category', $category);
        $this->assign("page", $page->show('Admin'));
        $this->assign('list', $lists);
        $this->display();
    }

//    public function add()
//    {
//        if (M('SmallTable')->where(['id'=>$_SESSION['small_id']])->getField('level_id')<=1){
//            //不是会员
//            $this->display('updateTip');
//            exit();
//        }
//        if(IS_POST){
//            $_POST['img'] = '/'.$_POST['smeta']['thumb'];
//            if(!$_POST['img'])$this->error('请上传缩略图！');
//            $_POST['shop_id'] = $_SESSION['small_id'];
//            $add = M('Activity')->add($_POST);
//            $this->success("成功",U("Activity/index"));
//        }else{
//            $category = M('ActivityCate')->select();
//            $this->assign('category', $category);
//            $this->display();
//        }
//
//    }



    public function getAreaList($pid){
        $list = D('Areas')->where(array('parentId'=>$pid))->select();
        return $list;
    }

    public function getAreas(){
        $id = I('get.pid',0);
        $list = $this->getAreaList($id);
        $this->ajaxReturn(array('code'=>1,'list'=>$list));
    }


    public function add()
    {
        if(IS_POST){
            if (!empty($_POST['age_id'])) {
                $_POST['age_id'] = ','.implode($_POST['age_id'],',').',';
            }
            if (!empty($_POST['target_place_id'])) {
                $_POST['target_place_id'] = ','.implode($_POST['target_place_id'],',').',';
            }
            $photos = $_POST['photos_url'];
            unset($_POST['photos_url']);
            $_POST['shop_id'] = $_SESSION['small_id'];
            $_POST['add_time'] = date('Y-m-d H:i:s');
            $data = M('Activity')->create($_POST);
            //不开启拼团则删除spell_max和spell_refund字段
            if (empty($_POST['target'])) $this->error('请设置活动目的地');
            if (empty($_POST['customer_tel'])) $this->error('请设置客服电话');
            if (empty($_POST['img'])) $this->error('请设置缩略图');
            if ($_POST['spell'] == 1) {
                if ($_POST['spell_max']<=0) $this->error('请设置成团人数');
                if (!$_POST['spell_refund']) $_POST['spell_refund'] = 0;
            } else {
                unset($data['spell_max']);
                unset($data['spell_refund']);
            }
            $id = M('Activity')->add($data);
            if($id){
                foreach ($photos as $photo){
                    D('Photos')->add(array('path'=>$photo,'item_id'=>$id,'type'=>1));
                }
                $this->success("成功",U("Activity/index"));
                exit();
            }
            $this->error('添加失败');
        }else{
            $category = M('ActivityCate')->order('sort')->select();
            $category  = $this->cateDegree($category,0,1);
            $this->assign('category', $category);
            $province = $this->getAreaList(0);
            $this->assign('province',$province);
            $ages = M('ages')->field('id,min,max')->order('id')->select();
            $target_place = D('target_place')->select();
            foreach ($ages as &$item) {
                if ($item['max'] == 0) {
                    $item['title'] = $item['min'].'岁以上';
                } else {
                    $item['title'] = $item['min'].'-'.$item['max'].'岁';
                }
                unset($item['min']);
                unset($item['max']);
            }
            $this->assign('ages',$ages);
            $this->assign('target_place',$target_place);
            $this->display();
        }

    }
    /*
        *  辅助方法 按层次关系排序分类
        */
    function cateDegree($list=array(),$pid=0,$tree=0){
        $temp = array();
        foreach ($list as $v){
            if($v['pid'] == $pid){
                if($tree > 0){
                    $tree--;
                    $pre = '';
                    if($tree > 0){
                        $pre = str_repeat('&nbsp;&nbsp;&nbsp;',$tree-1).'└';
                    }
                    $v['name'] = $pre.str_repeat('----',$tree).$v['name'];
                    $tree = $tree + 1;
                }
                $temp[] = $v;
                $temp = array_merge($temp,$this->cateDegree($list,$v['id'],$tree+1));
            }
        }
        return $temp;
    }

    public function del()
    {
        $id = $_REQUEST['id'];
        $find = M('ActivityOrder')->where(array('activity_id'=>$id, 'status'=>['in', [1,2,3]]))->find();
        if($find){
            echo json_encode(['code'=>-1, 'msg'=>"该活动已有人报名，不能删除"]);
            exit();
        }else{
            $del = M('Activity')->where(array('id'=>$id))->save(['status'=>4]);
            echo json_encode(['code'=>1]);
            exit();
        }
    }

    public function edit()
    {
        $aid = I('id');
        $find = M('ActivityOrder')->where(array('activity_id'=>$aid, 'status'=>['in', [1,2,3]]))->find();
        if($find){
            $this->error("该活动已有人报名，不能编辑");
        }
        if(IS_POST){
            if (!empty($_POST['age_id'])) {
                $_POST['age_id'] = ','.implode($_POST['age_id'],',').',';
            }
            if (!empty($_POST['target_place_id'])) {
                $_POST['target_place_id'] = ','.implode($_POST['target_place_id'],',').',';
            }
            $photos = $_POST['photos_url'];
            unset($_POST['photos_url']);
            $_POST['add_time'] = date('Y-m-d H:i:s');
            $id = M('Activity')->where(array('id'=>$_POST['id']))->save($_POST);
            try{
                D('Photos')->where(array('item_id'=>$id,'type'=>1))->delete();
                foreach ($photos as $photo){
                    D('Photos')->add(array('path'=>$photo,'item_id'=>$id,'type'=>1));
                }
                $this->success("保存成功",U("Activity/index"));
                exit();
            }catch (\Exception $e){
                $this->error('编辑失败');
            }
        }else{
            $id = I('get.id',0);
            $category = M('ActivityCate')->order('sort')->select();
            $category  = $this->cateDegree($category,0,1);
            $this->assign('category', $category);
            $data = D("Activity")->where(array('id'=>$id))->find();
            $data['age_id'] = explode(',',trim($data['age_id'],','));
            $data['target_place_id'] = explode(',',trim($data['target_place_id'],','));
            $province = $this->getAreaList(0);
            $city = $this->getAreaList($data['together_province']);
            $this->assign('province',$province);
            $this->assign('city',$city);
            $data['photo'] = D('Photos')->where(array('item_id'=>$id,'type'=>1))->select();
            $this->assign('data',$data);
            $ages = M('ages')->field('id,min,max')->order('id')->select();
            $target_place = D('target_place')->select();
            foreach ($ages as &$item) {
                if ($item['max'] == 0) {
                    $item['title'] = $item['min'].'岁以上';
                } else {
                    $item['title'] = $item['min'].'-'.$item['max'].'岁';
                }
                unset($item['min']);
                unset($item['max']);
            }
            $this->assign('ages',$ages);
            $this->assign('target_place',$target_place);

            $this->display();
        }
    }

    //活动上架、下架
    public function status()
    {
        $id = $_REQUEST['id'];
        $status = $_REQUEST['status'];  //活动当前状态 1.上架 2.下架 3.删除
        if ($status==1){
            //去下架
            M('Activity')->where(array('id'=>$id))->save(['state'=>2]);
            echo json_encode(['code'=>1]);
            exit();
        }else{
            //去上架
            //判断是否设置了出游日期
            if (!M('around_time')->where(['item_id'=>$id])->find()) {
                echo json_encode(['code'=>-1,'msg'=>'上架失败！请设置出游日期。']);
                exit();
            }
            //判断是否设置了活动出发地
            if (!M('activity_start')->where(['act_id'=>$id])->find()) {
                echo json_encode(['code'=>-1,'msg'=>'上架失败！请设置活动出发地。']);
                exit();
            }
            //判断是否设置了套餐
            if (!M('activity_attr')->where(['act_id'=>$id])->find()) {
                echo json_encode(['code'=>-1,'msg'=>'上架失败！请设置套餐。']);
                exit();
            }
            M('Activity')->where(array('id'=>$id))->save(['state'=>1]);
            echo json_encode(['code'=>1]);
            exit();
        }
    }

    //上传活动视频
    public function addVideo()
    {
        if(IS_POST){
            $_POST['video_cover'] = $_POST['smeta']['thumb'];
            if(!$_POST['video_cover'])$this->error('请上传缩略图！');
            $_POST['video_url'] = $_SESSION['avideo_url'];

            $add = M('Activity')->where(['id'=>$_POST['id']])->save($_POST);
            $_SESSION['avideo_url']=null;
            $this->success("成功",U("Activity/index"));
        }else{
            $id = $_GET['id'];
            $rs = M('Activity')->where(['id'=>$id])->find();
            $this->assign('rs', $rs);
            $this->display();
        }
    }

    public function upload()
    {
        $typeArr = array("mp4");
//允许上传文件格式
        $path = "./data/upload/video/";
//上传路径

        if (isset($_POST)) {
            $name = $_FILES['file']['name'];
            $size = $_FILES['file']['size'];
            $name_tmp = $_FILES['file']['tmp_name'];
            if (empty($name)) {
                echo json_encode(array("error" => "您还未选择视频"));
                exit ;
            }
            $type = strtolower(substr(strrchr($name, '.'), 1));
            //获取文件类型

            /*if (!in_array($type, $typeArr)) {
                echo json_encode(array("error" => "请上传MP4类型的视频！"));
                exit ;
            }*/
            /*if ($size > (10000 * 1024)) {
                echo json_encode(array("error" => "视频过大！"));
                exit ;
            }*/

            $pic_name = time() . rand(10000, 99999) . "." . $type;
            //文件名称
            $pic_url = $path . $pic_name;
            $pic_url1 = "/data/upload/video/" . $pic_name;
            $_SESSION['avideo_url'] = $pic_url1;
//            $id = M('Picture')->add(['url'=>$pic_url1]);
            //上传后图片路径+名称
            if (move_uploaded_file($name_tmp, $pic_url)) {//临时文件转移到目标文件夹
//                echo json_encode(array("error" => "0", "pic" => $pic_url1, "name" => $pic_name, "id" => $id));exit ;
                echo json_encode(array("error" => "0", "pic" => $pic_url1, "name" => $pic_name));exit ;
            } else {
                echo json_encode(array("error" => "上传有误，清检查服务器配置！"));exit ;
            }
        }
    }

    /*    订单 ----------------------------------------  */

    public function order(){
//        if (M('SmallTable')->where(['id'=>$_SESSION['small_id']])->getField('level_id')<=1){
//            //不是会员
//            $this->display('updateTip');
//            exit();
//        }
        $uid = $_SESSION['small_id'];
        $where = array(
            'o.st_id'=> intval($uid)
        );
        if($keyword = I('keyword')){
            $where['o.sn|p.parent_name|p.phone|a.title'] = array('like','%'.$keyword.'%');
            $this->assign('keyword',$keyword);
        }
        if($state = I('status')){
            if (in_array($state, [3, 4])){
                $where['o.status'] = 3;
                $where['refund_status'] = $state-1;
            }elseif ($state==5){
                $where['o.status'] = 4;
            }else{
                $where['o.status'] = $state;
            }
            $this->assign('status',$state);
        }
        if($activity = I('activity')){
            $where['o.activity_id'] = $activity;
            $this->assign('activityid',$activity);
        }
        $count = D('ActivityOrder')
            ->alias('o')
            ->join('left join __ACTIVITY__ a on o.activity_id = a.id')
            ->join('left join __PARENTS__ p on o.uid = p.id')
            ->where($where)
            ->count();
        $page = $this->page($count, 15);
        $list = D('ActivityOrder')
            ->alias('o')
            ->join('left join __ACTIVITY__ a on o.activity_id = a.id')
            ->join('left join __ACTIVITY_ATTR__ aa on o.attr_id = aa.id')
            ->join('left join __PARENTS__ m on o.uid = m.id')
            ->where($where)
            ->order('o.sign_time desc')
            ->limit($page->firstRow.','.$page->listRows)
            ->field('o.*,a.title as act_name,m.parent_name,m.phone as parent_phone,aa.title as attr_title')
            ->select();

        $activity = M('Activity')->where(['shop_id'=>$uid])->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign('activity',$activity);
        $this->assign('list',$list);
        $this->display();
    }

    public function view(){

        $id = I('get.id',0);
        $data = D('ActivityOrder')->where(array('id'=>$id))->find();
        $data['list'] = D('ActOrderDetail')->where(array('order_id'=>$id))->select();
        $this->assign('data',$data);
        $this->display();
    }

    public function refund(){
        $id = I('get.id',0);
        $state = I('get.status',0);
        $order = D('ActivityOrder')->where(array('id'=>$id))->find();
        if(D('ActivityOrder')->where(array('id'=>$id))->save(array('refund_status'=>3))){
            $this->success('成功');
            exit();
        }
        $this->error('失败');
    }

    //订单操作
    public function order_status()
    {
        $id = I('id',0);
        $type = I('type',0);
        if (in_array($type,[1,2,3])) {
            $ActivityOrder = D('ActivityOrder');
            $where = ['id'=>$id];
            $order = $ActivityOrder->where($where)->field('status,spell_id')->find();
            if ($order) {
                switch ($type) {
                    case 1:
                        $save = ['status'=>5];
                        break;
                    case 2:
                        if ($order['spell_id'] <= 0) $this->error('不是拼团订单');
                        $save = ['spell_id'=>0];
                        break;
                    case 3:
                        if ($order['status'] != 2) $this->error('操作失败');
                        $save = ['status'=>3];
                        break;
                }
                if ($save) {
                    if ($ActivityOrder->where($where)->setField($save)) {
                        $this->success('成功');
                    }
                }
            }
        }
    }

    //导出
    public function export()
    {
        $id = I('id');
        $title = M('Activity')->where(['id'=>$id])->getField('title');
        $lists = M('ActOrderDetail')
            ->alias('od')
            ->join('LEFT JOIN fzm_activity_order o ON o.id=od.order_id')
            ->where(['activity_id'=>$id, 'od.status'=>1, 'o.status'=>2])
            ->field('sn, member_name, member_phone')
            ->select();
        $str = '
		<table border="1">
		<tr style="background-color:#ccffcc;min-height:30px;">
			<th width="120" align="center">订单编号</th>
			<th align="center">报名用户</th>
			<th align="center">联系方式</th>
		</tr>
		';
        foreach ($lists as $key=>&$value) {
            if (($key%2)==0){
                $str .='<tr align="center" style="background-color:#fdffcc;min-height:30px;">';
            }else{
                $str .='<tr align="center" style="min-height:30px;">';
            }
            $num = 1;
            $str .= '
			<td width="120" rowspan='.$num.'>'.$value['sn'].'</td>
			<td rowspan='.$num.'>'.$value['member_name'].'</td>
			<td rowspan='.$num.'>'.$value['member_phone'].'</td>
			</tr>';
        }
        $str .="</table>";
        header('Content-Length: '.strlen($str));
        header("Content-type:application/vnd.ms-excel;charset=UTF-8");
        header("Content-Disposition:filename=活动".$title."报名明细表".date('Y-m-d H:i:s').".xls");
        echo $str;
    }


    /*
       *  报名时间段页
       */
    public function sign()
    {
        $id = I('id',0);
        $timeInterval = M('Activity')->where(array('id'=>$id))->field('start,end')->find();
        if (empty($timeInterval))  $this->error('活动不存在');
        if (IS_POST ) {
            $start = I('post.start',array());
            D('AroundTime')->where(array('item_id'=>$id,'item_type'=>1))->delete();
            $add = [];
            foreach ($start as $k => $s) {
                if ($s >= $timeInterval['start'] && $s<= $timeInterval['end']) {
                    $add[] = [
                        'item_type'=>1,
                        'item_id'=>I('post.id',0),
                        'start'=>$s,
                    ];
                }
            }
            if (!empty($add) && D('AroundTime')->addAll($add)) {
                $this->success('保存成功');
            }
        } else {

            $list = D('AroundTime')->where(array('item_type'=>1,'item_id'=>$id))->order('start')->select();
            $this->assign('list',$list);
            $this->assign('timeInterval',$timeInterval);
            $this->display();
        }
    }

    /*
     * 设置套餐页
     */
    public function attr(){
        $id = I('get.id',0);
        $list = D('ActivityAttr')->where(array('act_id'=>$id))->select();
        $this->assign('list',$list);
        $this->display();
    }

    /*
     * 添加套餐
     */
    public function addAttr(){
        $data = I('post.',array());
        $id = D('ActivityAttr')->add($data);
        if($id){
            $data['id'] = $id;
            $this->ajaxReturn(array('code'=>1,'data'=>$data));
        }
        $this->ajaxReturn(array('code'=>-1,'msg'=>'添加失败'));
    }

    /*
     *  删除套餐
     */
    public function deleteAttr(){
        $id = I('get.id',0);
        if(D('ActivityAttr')->where(array('id'=>$id))->delete()){
            $this->ajaxReturn(array('code'=>1,'删除成功'));
        }
        $this->ajaxReturn(array('code'=>-1,'msg'=>'删除失败'));
    }

    public function updateAttr(){
        $data = I('post.',array());
        $id = $data['id']?$data['id']:0;
        try{
            D('ActivityAttr')->where(array('id'=>$id))->save($data);
        }catch (\Exception $e){
            $this->ajaxReturn(array('code'=>-1,'msg'=>'保存失败'));
        }
        $this->ajaxReturn(array('code'=>1,'msg'=>'保存成功'));
    }



    /*
     *  活动出发地
     */

    public function start(){

        $id = I('get.id',0);

        $list = D('ActivityStart')
            ->alias('a')
            ->join('__AREAS__ p on a.province = p.areaId')
            ->join('__AREAS__ c on a.city = c.areaId')
            ->where(array('act_id'=>$id))
            ->field('a.*,p.areaName as provinceName,c.areaName as cityName')
            ->select();

        foreach ($list as &$item){
            $item['cities'] = D('Areas')->where(array('parentId'=>$item['province']))->select();
            $item['areas'] = D('Areas')->where(array('parentId'=>$item['city']))->select();
        }

        $this->assign('list',$list);
        $province = $this->getAreaList(0);
        $this->assign('province',$province);
        $this->display();
    }

    /*
     * 添加出发地
     */
    public function addStart(){
        $post = I("post.");
        $id = $post['act_id'];
        $tp = $post['tp'];
        $city = $post['city'];
        $area = $post['area'];
        if(D('ActivityStart')->add(array('act_id'=>$id,'province'=>$tp,'city'=>$city,'area'=>$area))){
            $this->ajaxReturn(array('code'=>1));
        }
        $this->ajaxReturn(array('code'=>-1,'msg'=>'添加失败'));
    }

    public function updateStart(){
        $post = I("post.");
        $id  = $post['id'];
        try{
            D('ActivityStart')->where(array('id'=>$id))->save($post);
            $this->ajaxReturn(array('code'=>1,'msg'=>'保存成功'));
        }catch (\Exception $e){
            $this->ajaxReturn(array('code'=>-1,'msg'=>$e->getMessage()));
        }
    }

    /*
     *  删除出发地
     */
    public function deleteStart(){
        $id = I('get.id',0);
        if(D('ActivityStart')->where(array('id'=>$id))->delete()){
            $this->ajaxReturn(array('code'=>1,'删除成功'));
        }
        $this->ajaxReturn(array('code'=>-1,'msg'=>'删除失败'));
    }
}