<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2018/3/22
 * Time: 17:04
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class LevelController extends AdminbaseController
{

    public function index()
    {
        $db = M('Level');
        $count = $db->count();
        $page=$this->page($count,15);
        $list = $db->order('money')->limit($page->firstRow, $page->listRows)->select();
        $this->assign('list',$list);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }

    public function add()
    {
        if (IS_POST){
            M('Level')->add([
                'money'=>$_POST['money'],
                'time_length'=>$_POST['time_length'],
                'name'=>$_POST['name'],
                'icon'=>$_POST['icon']
            ]);
            $this->success('添加成功', U('Level/index'));
            exit();
        }
        $this->display();
    }

    public function edit()
    {
        if (IS_POST){
            $id = I('post.id');
            M('Level')->where(['id'=>$id])->save([
                'money'=>$_POST['money'],
                'time_length'=>$_POST['time_length'],
                'name'=>$_POST['name'],
                'icon'=>$_POST['icon']

            ]);
            $this->success('修改成功', U('Level/index'));
            exit();
        }
        $id = I('get.id');
        $rs = M('Level')->where(['id'=>$id])->find();
        $this->assign('rs', $rs);
        $this->display();
    }
}