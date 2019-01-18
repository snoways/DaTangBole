<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/6
 * Time: 14:55
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class FeedbackController extends AdminbaseController
{

    public function index()
    {
        $count = M('Feedback')->count();
        $page=$this->page($count,15);
        $list = M('Feedback')->order('f_time desc')->limit($page->firstRow, $page->listRows)->select();
        foreach($list as $k=>$v){
            if($v['type'] == 1){   //家长
                $find = M('Parents')->where(array('id'=>$v['uid']))->find();
                $list[$k]['name'] = $find['parent_name'];
            }else{
                $find = M('Teacher')->where(array('id'=>$v['uid']))->find();
                $list[$k]['name'] = $find['name'];
            }
        }
        $this->assign('list',$list);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }
}