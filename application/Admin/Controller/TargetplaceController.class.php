<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/29
 * Time: 13:57
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class TargetplaceController extends AdminbaseController
{
    /**
     * 年龄段管理
     */
    public function index(){
        $list = D('target_place')->select();
        $this->assign('list',$list);
        $this->display();
    }

    /*
     *  添加年龄段
     */
    public function add(){
        if(IS_POST){
            $data = I('post.');
            if(D('target_place')->add($data)){
                $this->success('添加成功');
                exit();
            }
            $this->error('添加失败');
        }
        $this->display();
    }

    /**
     *   编辑年龄段
     */
    public function edit(){
        if(IS_POST){
            $post = I('post.');
            $id = $post['id'];
            try{
                D('target_place')->where(array('id'=>$id))->save($post);
                $this->success('保存成功');
                exit();
            }catch (\Exception $e){
                $this->error($e->getMessage());
            }
        }
        $id = I('get.id',0);
        $data = D('target_place')->where(array('id'=>$id))->find();
        $this->assign('data',$data);
        $this->display();
    }

    /*
     *  删除年龄段
     */
    function delete(){
        $id = I('get.id',0);
        if(D('target_place')->where(array('id'=>$id))->delete()){
            $this->success('删除成功');
            exit();
        }
        $this->error('删除失败');
    }
}