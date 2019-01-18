<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/29 0029
 * Time: 下午 05:42
 */

namespace Managed\Controller;

use Think\Page;
use Think\Controller;

class VideoController extends ManagedBaseController
{

    //教育机构视频管理

    public function index()
    {
        $keyword = trim(I("keyword"));
        if($keyword){
            $where['sv.title'] = ['like',"%$keyword%"];
            $this->assign('keyword',$keyword);
        }
        $where['sv.status'] = ['neq', 3];
        $where['st_id'] = $_SESSION['small_id'];
        $oauth_user_model=M('SmallVideo');
        $count=$oauth_user_model
            ->alias('sv')
            ->join('LEFT JOIN fzm_small_table st ON st.id=sv.st_id')
            ->where($where)
            ->count();
        $page = $this->page($count, 15);
        $lists = $oauth_user_model
            ->alias('sv')
            ->join('LEFT JOIN fzm_small_table st ON st.id=sv.st_id')
            ->where($where)
            ->order("add_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('sv.*, st.s_name')
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign('list', $lists);
        $this->display();
    }

    public function del()
    {
        $id = $_REQUEST['id'];
        M('SmallVideo')->where(array('id'=>$id))->save(['status'=>3]);
        echo json_encode(['code'=>1]);
    }

    //视频上架、下架
    public function status()
    {
        $id = $_REQUEST['id'];
        $status = $_REQUEST['status'];  //视频当前状态 1.上架 2.下架 3.删除
        if ($status==1){
            M('SmallVideo')->where(array('id'=>$id))->save(['status'=>2]);
            echo json_encode(['code'=>1]);
            exit();
        }else{
            //去上架
            M('SmallVideo')->where(array('id'=>$id))->save(['status'=>1]);
            echo json_encode(['code'=>1]);
            exit();
        }
    }

    //视频置顶
    public function top()
    {
        $id = $_REQUEST['id'];
        $where['st_id'] = $_SESSION['small_id'];
        M('SmallVideo')->where($where)->save(['is_top'=>0]);
        M('SmallVideo')->where(['id'=>$id])->save(['is_top'=>1]);
        echo json_encode(['code'=>1]);
        exit();
    }

    public function upload()
    {
        $typeArr = array("mp4");
//允许上传文件格式
        $path = "./data/upload/video/";
//上传路径

        if (isset($_POST)) {
            $name = $_FILES['file']['name'];
            $size = $_FILES['file']['size'];
            $name_tmp = $_FILES['file']['tmp_name'];
            if (empty($name)) {
                echo json_encode(array("error" => "您还未选择视频"));
                exit ;
            }
            $type = strtolower(substr(strrchr($name, '.'), 1));
            //获取文件类型

            /*if (!in_array($type, $typeArr)) {
                echo json_encode(array("error" => "请上传MP4类型的视频！"));
                exit ;
            }*/
            /*if ($size > (10000 * 1024)) {
                echo json_encode(array("error" => "视频过大！"));
                exit ;
            }*/

            $pic_name = time() . rand(10000, 99999) . "." . $type;
            //文件名称
            $pic_url = $path . $pic_name;
            $pic_url1 = "/data/upload/video/" . $pic_name;
            $_SESSION['video_url'] = $pic_url1;
//            $id = M('Picture')->add(['url'=>$pic_url1]);
            //上传后图片路径+名称
            if (move_uploaded_file($name_tmp, $pic_url)) {//临时文件转移到目标文件夹
//                echo json_encode(array("error" => "0", "pic" => $pic_url1, "name" => $pic_name, "id" => $id));exit ;
                echo json_encode(array("error" => "0", "pic" => $pic_url1, "name" => $pic_name));exit ;
            } else {
                echo json_encode(array("error" => "上传有误，清检查服务器配置！"));exit ;
            }
        }
    }

    public function add()
    {
        if(IS_POST){
            $_POST['img_url'] = $_POST['smeta']['thumb'];
            $_POST['video_url'] = $_SESSION['video_url'];
            $_POST['add_time'] = date('Y-m-d H:i:s');
            $_POST['st_id'] = $_SESSION['small_id'];
            $add = M('SmallVideo')->add($_POST);
            $_SESSION['video_url']=null;
            $this->success("成功",U("Video/index"));
        }else{
            $this->display();
        }

    }

    public function edit()
    {
        if(IS_POST){
            $_POST['img_url'] = $_POST['smeta']['thumb'];
            if (!empty($_SESSION['video_url'])){
                $_POST['video_url'] = $_SESSION['video_url'];
            }
            $add = M('SmallVideo')->where(['id'=>I('id')])->save($_POST);
            $_SESSION['video_url']=null;
            $this->success("成功",U("Video/index"));
        }else{
            $id = I('id');
            $rs = M('SmallVideo')->where(['id'=>$id])->find();
            $this->assign('rs', $rs);
            $this->display();
        }

    }
}