<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/18 0018
 * Time: 9:58
 */

namespace Managed\Controller;


class ActivitycommentController extends ManagedBaseController
{

    public function index(){

        $where = array(
          'a.shop_id' =>  $_SESSION['small_id']
        );

        if($key = $_REQUEST['keyword']){
            $where['m.phone|m.parent_name'] = array('like',"%".$key."%");
        }
        if($_REQUEST['title']){
            $where['c.title'] = array('like','%'.$_REQUEST['title'].'%');
        }

        $count = D('ActivityComment')
            ->alias('c')
            ->join('left join __PARENTS__ m on c.user_id = m.id and c.to_user_id = 0')
            ->join('__ACTIVITY__ a on a.id = c.act_id')
            ->where($where)
            ->count();

        $page =  $this->page($count,12);


        $list = D('ActivityComment')
            ->alias('c')
            ->join('left join __PARENTS__ m on c.user_id = m.id and c.to_user_id = 0')
            ->join('__ACTIVITY__ a on a.id = c.act_id')
            ->where($where)
            ->field('c.*,a.title,m.parent_name as user_name')
            ->order("c.create_time desc")
            ->select();

        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        $this->display();
    }
//
//    public function delete(){
//        $ids = I("request.ids",array());
//        $add = 0;
//        foreach ($ids as $id){
//            if(D('ActivityComment')->where(array('id'=>$id))->delete()){
//                $add++;
//            }
//        }
//        if($add){
//            $this->success('删除成功',U('Activitycomment/index'));
//            exit();
//        }
//        $this->error('删除失败');
//    }
}