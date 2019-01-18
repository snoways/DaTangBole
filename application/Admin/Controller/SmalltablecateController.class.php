<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/23
 * Time: 17:38
 */

namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class SmalltablecateController extends AdminbaseController
{

    public function index()
    {
        $small_table_cate = M('smalltable_cate');
        $count = $small_table_cate->count();
        $page = $this->page($count, 15);
        $info = $small_table_cate->order('sort')->select();
        $this->assign("list", $info);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }

    public function add()
    {
        if (IS_POST) {
            $small_table_cate = M('smalltable_cate');
            if ($data = $small_table_cate->create($_POST)) {
                if ($small_table_cate->add($data)) {
                    $this->success('成功');
                } else{
                    $this->error('失败');
                }
            } else {
                $this->success($small_table_cate->getError());
            }
        } else {
            $this->display();
        }
    }

//    public function edit()
//    {
//        $small_table_cate = M('smalltable_cate');
//        $id = I('id');
//        if ($id) {
//            if (IS_POST) {
//                $title = I('title');
//                $re = $small_table_cate->where(['id'=>$id])->setField('title',$title);
//                if ($re) {
//                    $this->success('成功');
//                } else{
//                    $this->error('失败');
//                }
//            } else {
//                $info = $small_table_cate->where(['id'=>$id])->find();
//                if (!$info) $this->error('错误');
//                $this->assign('',$info);
//                $this->display();
//            }
//        }
//    }

    public function delete()
    {
        M('smalltable_cate')->where(array('id' => $_GET['id']))->delete();
        $this->success("成功");
    }

    public function listorders()
    {
        $status = parent::_listorders(M('smalltable_cate'),'sort');
        if ($status) {
            $this->success("排序更新成功！");
        } else {
            $this->error("排序更新失败！");
        }
    }
}