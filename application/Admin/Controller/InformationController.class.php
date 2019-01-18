<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/24
 * Time: 14:01
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class InformationController extends AdminbaseController
{

    public function index()
    {
        if(IS_POST){
            $find = M('Config')->find();
            if($find){
                $save = M('Config')->where(array('id'=>$find['id']))->save($_POST);
            }else{
                $add = M('Config')->add($_POST);
            }
            $this->success("成功",U("Information/index"));
        }else{
            $find = M('Config')->find();
            if($find){
                $this->assign("find",$find);
            }
            $this->display();
        }
    }

    public function add()
    {
        if(IS_POST){
            $find = M('Config')->find();
            if($find){
                $save = M('Config')->where(array('id'=>$find['id']))->save($_POST);
            }else{
                $add = M('Config')->add($_POST);
            }
            $this->success("成功",U("Information/add"));
        }else{
            $find = M('Config')->find();
            if($find){
                $this->assign("find",$find);
            }
            $this->display();
        }
    }
    public function problem()
    {
        if(IS_POST){
            $find = M('Config')->find();
            if($find){
                $save = M('Config')->where(array('id'=>$find['id']))->save($_POST);
            }else{
                $add = M('Config')->add($_POST);
            }
            $this->success("成功",U("Information/problem"));
        }else{
            $find = M('Config')->find();
            if($find){
                $this->assign("find",$find);
            }
            $this->display();
        }
    }
    public function legal()
    {
        if(IS_POST){
            $find = M('Config')->find();
            if($find){
                $save = M('Config')->where(array('id'=>$find['id']))->save($_POST);
            }else{
                $add = M('Config')->add($_POST);
            }
            $this->success("成功",U("Information/legal"));
        }else{
            $find = M('Config')->find();
            if($find){
                $this->assign("find",$find);
            }
            $this->display();
        }
    }
    public function registered()
    {
        if(IS_POST){
            $find = M('Config')->find();
            if($find){
                $save = M('Config')->where(array('id'=>$find['id']))->save($_POST);
            }else{
                $add = M('Config')->add($_POST);
            }
            $this->success("成功",U("Information/registered"));
        }else{
            $find = M('Config')->find();
            if($find){
                $this->assign("find",$find);
            }
            $this->display();
        }
    }

    /**
     * 会员权益
     * 2018.4.8
     * sunfan
     */
    public function VIPBenefits()
    {
        if(IS_POST){
            $find = M('Config')->find();
            if($find){
                $save = M('Config')->where(array('id'=>$find['id']))->save($_POST);
            }else{
                $add = M('Config')->add($_POST);
            }
            $this->success("成功",U("Information/VIPBenefits"));
        }else{
            $find = M('Config')->find();
            if($find){
                $this->assign("find",$find);
            }
            $this->display();
        }
    }

    public function version_note(){
        if(IS_POST){
            $find = M('Config')->find();
            if($find){
                $save = M('Config')->where(array('id'=>$find['id']))->save($_POST);
            }else{
                $add = M('Config')->add($_POST);
            }
            $this->success("成功",U("Information/version_note"));
        }else{
            $find = M('Config')->find();
            if($find){
                $this->assign("find",$find);
            }
            $this->display();
        }
    }
    //教师注册协议
    public function teacher_agreement(){
        if(IS_POST){
            $find = M('Config')->find();
            if($find){
                $save = M('Config')->where(array('id'=>$find['id']))->save($_POST);
            }else{
                $add = M('Config')->add($_POST);
            }
            $this->success("成功",U("Information/teacher_agreement"));
        }else{
            $find = M("config")->find();
            if($find){
                $this->assign('find',$find);
            }
            $this->display();
        }
    }
    //商家注册协议
    public function business_agreement(){
        if(IS_POST){
            $find = M('Config')->find();
            if($find){
                $save = M('Config')->where(array('id'=>$find['id']))->save($_POST);
            }else{
                $add = M('Config')->add($_POST);
            }
            $this->success("成功",U("Information/teacher_agreement"));
        }else{
            $find = M("config")->find();
            if($find){
                $this->assign('find',$find);
            }
            $this->display();
        }
    }
    //商家placeholder
    public function placeholder(){
        if(IS_POST){
            $find = M('Config')->find();
            if($find){
                $save = M('Config')->where(array('id'=>$find['id']))->save($_POST);
            }else{
                $add = M('Config')->add($_POST);
            }
            $this->success("成功",U("Information/placeholder"));
        }else{
            $find = M("config")->find();
            if($find){
                $this->assign('find',$find);
            }
            $this->display();
        }
    }
}