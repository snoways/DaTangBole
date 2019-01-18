<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/11/8
 * Time: 17:10
 */

namespace Portal\Controller;


use Common\Controller\AdminbaseController;

class CategoryController extends AdminbaseController
{

    public function index()
    {
        $oauth_user_model=M('NewsCategory');
        $lists = $oauth_user_model
            ->select();
        $this->assign('lists', $lists);
        $this->display();
    }

    public function edit(){
        if(IS_POST){
            $id = I("get.id",0,'intval');
            if(!$_FILES['cate_img']['error']) {
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize = 11048576;// 设置附件上传大小
                $upload->exts = array('jpg', 'png', 'jpeg', 'gif');// 设置附件上传类型
                $upload->rootPath = './data/upload/admin/'; // 设置附件上传根目录
                $upload->savePath = ''; // 设置附件上传（子）目录
                // 上传文件
                $info = $upload->upload();
                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                } else {
                    $pro['cate_img'] = "/data/upload/admin/" . $info['cate_img']['savepath'] . $info['cate_img']['savename'];
                }
            }
            $save = M('NewsCategory')->where(array('id'=>$id))->save($pro);
            if($save !== false){
                $this->success('保存成功！',U('Category/index'));
            }else{
                $this->error('网络错误！');
            }
        }else{
            $id = I('get.id');
            $oauth_user_model=M('NewsCategory');
            $lists = $oauth_user_model->where(array('id'=>$id))->find();
            $this->assign('lists', $lists);
            $this->display();
        }
    }
}