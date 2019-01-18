<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/30
 * Time: 13:58
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class StartController extends AdminbaseController
{

    public function index()
    {
        $config=M('Config');
        if(IS_POST){
            if(!empty($_FILES))
            {
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize = 11048576;// 设置附件上传大小
                $upload->exts = array('jpg', 'png', 'jpeg', 'gif');// 设置附件上传类型
                $upload->rootPath = './public/images/'; // 设置附件上传根目录
                $upload->savePath = ''; // 设置附件上传（子）目录
                // 上传文件
                $info = $upload->upload();
                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                } else {
                    $_POST['start_img'] = "/public/images/" . $info['img']['savepath'] . $info['img']['savename'];
                }
            }
            $config->create();
            $config->where(['id'=>1])->save($_POST);
            $this->success('保存成功！');
        }else{
            $config = M('Config')->find();
            $img = $config['start_img'];
            $this->assign('img',$img);
            $this->display();
        }
    }
}