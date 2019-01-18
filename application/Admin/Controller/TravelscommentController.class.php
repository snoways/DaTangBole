<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/15 0015
 * Time: 22:28
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class TravelscommentController extends AdminbaseController
{

    /*
     *  评论列表
     */
    public function index(){

        $where = array();

        if($key = $_REQUEST['keyword']){
            $where['m.phone|m.parent_name'] = array('like',"%".$key."%");
        }
        if($_REQUEST['title']){
            $where['c.title'] = array('like','%'.$_REQUEST['title'].'%');
        }

        $count = M('travels_comment')
            ->alias('c')
            ->join('left join __PARENTS__ m on c.user_id = m.id and c.to_user_id = 0')
            ->join('__TRAVELS__ a on a.id = c.tra_id')
            ->where($where)
            ->count();

        $page =    $this->page($count,12);


        $list = M('travels_comment')
            ->alias('c')
            ->join('left join __PARENTS__ m on c.user_id = m.id and c.to_user_id = 0')
            ->join('__TRAVELS__ a on a.id = c.tra_id')
            ->where($where)
            ->field('c.*,a.title,m.parent_name as user_name')
            ->order("c.create_time desc")
            ->select();

        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
        $this->display();
    }

    public function delete(){
        $ids = I("request.ids",array());
        $add = 0;
        foreach ($ids as $id){
            if(D('travels_comment')->where(array('id'=>$id))->delete()){
                $add++;
            }
        }
        if($add){
            $this->success('删除成功');
            exit();
        }
        $this->error('删除失败');
    }
}