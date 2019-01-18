<?php
/**
 * Created by PhpStorm.
 * User: Sunfan
 * Date: 2017/12/26 0026
 * Time: 上午 10:41
 */

namespace Managed\Controller;



class TeacherController extends ManagedBaseController
{
    //师资


    public function index(){
        $where = array();
        $where['st_id'] = $_SESSION['small_id'];
        $model = M("SmallTeacher");
        $count = $model
            ->where($where)
            ->count();
        $page = $this->page($count,15);
        $info = $model
            ->where($where)
            ->order('create_time desc')
            ->limit($page->firstRow,$page->listRows)
            ->select();

        $this->assign('page',$page->show('Admin'));
        $this->assign('info',$info);
        $this->display();
    }

    public function add()
    {
        if(IS_POST){
            $_POST['st_id'] = $_SESSION['small_id'];
            $add = M('SmallTeacher')->add($_POST);
            $this->success("成功",U("Teacher/index"));
        }else{
            $this->display();
        }
    }

    public function edit()
    {
        if(IS_POST){
            $add = M('SmallTeacher')->where(['id'=>I('id')])->save($_POST);
            $this->success("成功",U("Teacher/index"));
        }else{
            $info = M('SmallTeacher')->where(['id'=>I('id')])->find();
            $this->assign('info', $info);
            $this->display();
        }
    }

    //活动上架、下架
    public function recommend()
    {
        $id = $_REQUEST['id'];
        $top = $_REQUEST['top'];
        M('SmallTeacher')->where(array('id'=>$id))->save(['top'=>$top]);
        echo json_encode(['code'=>1]);
        exit();
    }

    public function del()
    {
        $id = $_REQUEST['id'];
        $del = M('SmallTeacher')->where(array('id'=>$id))->delete();
        echo json_encode(['code'=>1]);
        exit();
    }
}