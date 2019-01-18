<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/4 0004
 * Time: 下午 01:53
 */

namespace Managed\Controller;

use Think\Page;
use Think\Controller;

class CourseController extends ManagedBaseController
{

    public function index()
    {
        $where['status'] = ['neq', 3];
        $where['st_id'] = $_SESSION['small_id'];
        $request=I('request.');

        if(!empty($request['keyword'])){
            $keyword=$request['keyword'];
            $where['title'] = ['like', "%$keyword%"];
            $this->assign('keyword',$keyword);
        }

        $oauth_user_model=M('EducationalCourse');
        $count=$oauth_user_model
            ->where($where)
            ->count();
        $page = $this->page($count, 15);
        $lists = $oauth_user_model
            ->where($where)
            ->order("add_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $this->assign("page", $page->show('Admin'));
        $this->assign('lists', $lists);
        $this->display();
    }

    public function add()
    {
        if (IS_POST){
            if (!$_POST['sub1']){
                $this->error('请选择科目');
            }elseif (!$_POST['img']){
                $this->error('请上传图片');
            }else{
                $_POST['st_id'] = $_SESSION['small_id'];
                M('EducationalCourse')->add($_POST);
                $this->success('添加成功', U('Course/index'));
                exit();
            }
        }
        //年级
        $grade = M('Grade')->select();
        $this->assign('grade', $grade);

        //介绍的placeholder
        $placeholder = D("config")->where(['id'=>1])->getField('placeholder');
        $this->assign('placeholder',$placeholder);

        //课程
        $subject = M('smalltable_cate')->order('sort asc')->select();
        $this->assign('subject', $subject);

        $this->display();
    }


    public function del()
    {
        $id = $_REQUEST['id'];
        $del = M('EducationalCourse')->where(array('id'=>$id))->save(['status'=>3]);
        echo json_encode(['code'=>1]);
        exit();
    }

    public function edit()
    {
        if(IS_POST){
            if (!$_POST['sub1']){
                $this->error('请选择科目');
            }else{
                if (!$_POST['img']){
                    unset($_POST['img']);
                }

                M('EducationalCourse')->where(['id'=>$_POST['id']])->save($_POST);
                $this->success('编辑成功', U('Course/index'));
            }
        }else{
            $rs = M('EducationalCourse')->where(['id'=>$_GET['id']])->find();
            $this->assign('rs', $rs);

            //年级
            $grade = M('Grade')->select();
            $this->assign('grade', $grade);

            //一级课程
            $subject = M('smalltable_cate')->order('sort asc')->select();
            $this->assign('subject', $subject);
            //介绍的placeholder
            $placeholder = D("config")->where(['id'=>1])->getField('placeholder');
            $this->assign('placeholder',$placeholder);

            $this->display();
        }
    }
}