<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/11/15
 * Time: 9:41
 */
namespace Managed\Controller;


use Common\Controller\AdminbaseController;
use Think\Controller;

class AuthController extends Controller
{

    public function login(){
        unset ($_SESSION['small_id']);
        if(IS_POST){
            $small = M("small_table");
            $s_phone = I("s_phone");
            $s_passowrd = I('s_password');
            $password = MD5($s_passowrd);
            if(!($s_phone && $s_passowrd)){
                $arr['code'] = -1;  //请输入用户名和密码
                echo json_encode($arr);
                exit();
            }
            $msg = $small->where(array('s_phone'=>$s_phone,'s_type'=>['neq',4]))->find();
            if(!$msg){
                $arr['code'] = -2;; //用户不存在
                echo json_encode($arr);
                exit();
            }
            if($password != $msg['s_password']){
                $arr['code'] = -3;;  //密码错误
                echo json_encode($arr);
                exit();
            }
            if ($msg['s_status']==0){
                $arr['code'] = 5;;  //未上传资料
                echo json_encode($arr);
                exit();
            }elseif ($msg['s_status']==1){
                $arr['code'] = 6;;  //未审核
                echo json_encode($arr);
                exit();
            }elseif ($msg['s_status']==3){
                $arr['code'] = 7;;  //审核不通过
                echo json_encode($arr);
                exit();
            }
            if($msg['status'] != 1){
                $arr['code'] = 4;
                echo json_encode($arr);
                exit();
            }else{
                $_SESSION['small_id'] = $msg['id'];
                $_SESSION['small_table'] = $msg;
                $arr['code'] = 1;
                echo json_encode($arr);
                exit();
            }
        }else{
            $this->display();
        }
    }

}