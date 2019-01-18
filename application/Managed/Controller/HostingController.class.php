<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/4 0004
 * Time: 下午 05:20
 */

namespace Managed\Controller;

use Think\Page;
class HostingController extends ManagedBaseController
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

        $oauth_user_model=M('HostingCourse');
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
            if (!$_POST['img']){
                $this->error('请上传图片');
            }else{
                $_POST['img'] = '/'.$_POST['img'];
                $_POST['st_id'] = $_SESSION['small_id'];

                M('HostingCourse')->add($_POST);
                $this->success('添加成功', U('Hosting/index'));
                exit();
            }
        }
        $placeholder = D("config")->where(['id'=>1])->getField('placeholder2');
        $this->assign('placeholder',$placeholder);
        $this->display();
    }
    public function del()
    {
        $id = $_REQUEST['id'];
        $del = M('HostingCourse')->where(array('id'=>$id))->save(['status'=>3]);
        echo json_encode(['code'=>1]);
        exit();
    }

    public function edit()
    {
        if(IS_POST){
            if ($_POST['img']){
                $_POST['img'] = '/'.$_POST['img'];
            }else{
                unset($_POST['img']);
            }

            M('HostingCourse')->where(['id'=>$_POST['id']])->save($_POST);
            $this->success('编辑成功', U('Hosting/index'));
        }else{
            $placeholder = D("config")->where(['id'=>1])->getField('placeholder2');
            $this->assign('placeholder',$placeholder);
            $rs = M('HostingCourse')->where(['id'=>$_GET['id']])->find();
            $this->assign('rs', $rs);

            $this->display();
        }
    }

}