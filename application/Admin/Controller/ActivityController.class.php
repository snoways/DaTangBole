<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2018/3/23
 * Time: 10:23
 */

namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class ActivityController extends AdminbaseController
{
    public function index()
    {
        $where['a.status'] = ['neq', 4];
        if ($_REQUEST['keyword']){
            $where['a.title'] = ['like', ['%'.$_REQUEST['keyword'].'%']];
        }
        if ($_REQUEST['state'] != '') {
            $where['a.state'] = $_REQUEST['state'];
        }
        if ($_REQUEST['add_time']==1) {
            $where['a.add_time'] = ['between',[date('Y-m-d 00:00:01'),date('Y-m-d H:i:s')]];
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
            $where['a.cate_id'] = ['in',$_REQUEST['cate_id']];
            $ids = M('activity_cate')
                ->where(array('status'=>1,'pid'=>$_REQUEST['cate_id']))
                ->order('sort')
                ->getField('id',true);
            if ($ids) {
                array_unshift($ids,$_REQUEST['cate_id']);
                $where['a.cate_id'][1] = $ids;
            }
        }
//        print_r($where);die;
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
        $category = M('ActivityCate')->order('sort')->select();
        $category  = $this->cateDegree($category,0,1);
        $this->assign('category', $category);
        $this->assign("page", $page->show('Admin'));
        $this->assign('list', $lists);
        $this->display();
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
            $_POST['add_time'] = date('Y-m-d H:i:s');
            if (empty($_POST['target'])) $this->error('请设置活动目的地');
            if (empty($_POST['customer_tel'])) $this->error('请设置客服电话');
            if (empty($_POST['img'])) $this->error('请设置缩略图');
            if ($_POST['spell'] == 1) {
                if ($_POST['spell_max']<=0) $this->error('请设置成团人数');
                if (!$_POST['spell_refund']) $_POST['spell_refund'] = 0;
            } else {
                $_POST['spell_max'] = 0;
                $_POST['spell_refund'] = 0;
            }
            $id = M('Activity')->add($_POST);
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
        if(IS_POST){
            if (!empty($_POST['age_id'])) {
                $_POST['age_id'] = ','.implode($_POST['age_id'],',').',';
            }
            if (!empty($_POST['target_place_id'])) {
                $_POST['target_place_id'] = ','.implode($_POST['target_place_id'],',').',';
            }
            $photos = $_POST['photos_url'];
            $_POST['add_time'] = date('Y-m-d H:i:s');
            $id = M('Activity')->where(array('id'=>$_POST['id']))->save($_POST);
            try{
                D('Photos')->where(array('item_id'=>$_POST['id'],'type'=>1))->delete();
//                print_r($photos);die;
                foreach ($photos as $photo){
                    D('Photos')->add(array('path'=>$photo,'item_id'=>$_POST['id'],'type'=>1));
                }
                $this->success("保存成功");
            }catch (\Exception $e){
                $this->error('添加失败');
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

    //活动分类
    public function category()
    {
        $where=[];
        $oauth_user_model=M('ActivityCate');
        $count = $oauth_user_model
            ->where($where)
            ->count();
        $page = $this->page($count, 18);
        $lists = $oauth_user_model
            ->where($where)
            ->order("sort")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        $cates  = $this->cateDegree($lists,0,1);
        $this->assign("page", $page->show('Admin'));
        $this->assign('list', $cates);
        $this->display();
    }
    public function addCate()
    {
        if(IS_POST){
             M('ActivityCate')->add($_POST);
            $this->success("成功",U("Activity/category"));
        }else{
            $list = D('ActivityCate')->where(['pid'=>0])->select();
            $this->assign('list',$list);
            $this->display();
        }
    }

    public function editCate()
    {
        if(IS_POST){
            M('ActivityCate')->where(['id'=>$_POST['id']])->save($_POST);
            $this->success("成功",U("Activity/category"));
        }else{
            $id = $_GET['id'];
            $rs = M('ActivityCate')->where(['id'=>$id])->find();
            $this->assign('rs', $rs);
            $list = D('ActivityCate')->where(['pid'=>0])->select();
            $this->assign('list',$list);
            $this->display();
        }
    }

    public function delCate()
    {
        $id = $_REQUEST['id'];
        $item = D('ActivityCate')->where(array('id'=>$id))->find();
        $del = M('ActivityCate')->where(array('id'=>$id))->delete();
        if($del){
            D('ActivityCate')->where(array('pid'=>$id))->save(array('pid'=>$item['pid']));
            $this->success('删除成功');
            exit();
        }
        $this->error('删除失败');
    }
    public function is_selected()
    {
        $id = $_REQUEST['id'];
        $ActivityCate = M('ActivityCate');
        $ActivityCate->where(['is_selected'=>1])->setField('is_selected',0);
        $re = $ActivityCate->where(['id'=>$id])->setField('is_selected',1);
        if($re){
            $this->success('操作成功');
        }
        $this->error('操作失败');
    }

    public function getAreaList($pid){
        $list = D('Areas')->where(array('parentId'=>$pid))->select();
        return $list;
    }

    public function getAreas(){
        $id = I('get.pid',0);
        $list = $this->getAreaList($id);
        $this->ajaxReturn(array('code'=>1,'list'=>$list));
    }

    public function sort(){
        $ids = I('post.id',array());
        $sort = I('post.sort',array());
        try{
            foreach ($ids as $k => $id){
                D('ActivityCate')->where(array('id'=>$id))->save(array('sort'=>$sort[$k]));
            }
            $this->success('排序成功');
        }catch (\Exception $e){
            $this->error('排序失败');
        }
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


    /**
     * 年龄段管理
     */
    public function age(){
        $list = D('Ages')->select();
        $this->assign('list',$list);
        $this->display();
    }

    /*
     *  添加年龄段
     */
    public function addAge(){
        if(IS_POST){
           $data = I('post.');
           if(D('Ages')->add($data)){
               $this->success('添加成功',U('Activity/age'));
               exit();
           }
           $this->error('添加失败');
        }
        $this->display();
    }

    /**
     *   编辑年龄段
     */
    public function editAge(){
        if(IS_POST){
           $post = I('post.');
           $id = $post['id'];
           try{
               D('Ages')->where(array('id'=>$id))->save($post);
               $this->success('保持成功',U('Activity/age'));
               exit();
           }catch (\Exception $e){
               $this->error($e->getMessage());
           }
        }
        $id = I('get.id',0);
        $data = D('Ages')->where(array('id'=>$id))->find();
        $this->assign('data',$data);
        $this->display();
    }

    /*
     *  删除年龄段
     */
    function deleteAge(){
        $id = I('get.id',0);
        if(D('Ages')->where(array('id'=>$id))->delete()){
            $this->success('删除成功',U('Activity/age'));
            exit();
        }
        $this->error('删除失败');
    }
}