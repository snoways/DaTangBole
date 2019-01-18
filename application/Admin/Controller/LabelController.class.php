<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/28 0028
 * Time: 下午 05:33
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class LabelController extends AdminbaseController
{
    public function index()
    {
        $where=array();
        if (I('post.keyword')){
            $where['lname'] = ['like', "%".I('post.keyword')."%"];
        }
        $oauth_user_model=M('Label');
        $count=$oauth_user_model
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $lists = $oauth_user_model
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $this->assign("page", $page->show('Admin'));
        $this->assign('lists', $lists);
        $this->display();
    }

    public function add()
    {
        if(IS_POST)
        {
            $map['lname'] = $_POST['lname'];
            M('Label')->add($map);
            $this->success('添加成功！');
        }
        $this->display();
    }

    public function delete()
    {
        M('Label')->where(['id'=>I('get.id')])->delete();
        $this->success('删除成功！');
    }

}