<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/10/25
 * Time: 11:39
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class VideoController extends AdminbaseController
{
    public function index()
    {
        $where['status'] = ['neq', 3];
//        if($this->role == 'agent'){
//            $where['agent_id'] = $this->uid;
//        }
        if (!empty(I('keyword'))){
            $where['title'] = ['like', "%".I('keyword')."%"];
        }
        $oauth_user_model=M('Video');
        $count=$oauth_user_model
            ->alias('v')
            ->join('LEFT JOIN fzm_video_cate vc ON vc.id=v.category_id')
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $lists = $oauth_user_model
            ->alias('v')
            ->join('LEFT JOIN fzm_video_cate vc ON vc.id=v.category_id')
            ->where($where)
            ->order("add_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('v.*, vc.name as category_name')
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign('list', $lists);
        $this->display();
    }

    public function add()
    {
        if(IS_POST){
           $_POST['img_url'] = '/'.$_POST['smeta']['thumb'];
//            $_POST['video_url'] = $_SESSION['video_url'];
            if($this->role=='agent'){
                $_POST['agent_id'] = $this->uid;
            }
            $add = M('Video')->add($_POST);
            $_SESSION['video_url']=null;
            $this->success("成功",U("Video/index"));
        }else{
            if ($this->role == 'admin'){
                $agent = D('Users')
                    ->alias('u')
                    ->join('fzm_role_user as r on u.id = r.user_id')
                    ->where(array('r.role_id'=>17))
                    ->select();
                $this->assign('agent',$agent);
            }else{
                $this->assign('agent',false);
            }
            $category = M('VideoCate')->field('id, name')->select();
            $this->assign('category',$category);
            $this->display();
        }

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
                echo json_encode(array("error" => "上传有误，请检查服务器配置！"));exit ;
            }
        }
    }

    public function del()
    {
        $id = $_REQUEST['id'];
        $find = M('Banner')->where(array('type'=>2,'item_id'=>$id))->find();
        if($find){
            echo json_encode(['code'=>-1, 'msg'=>"视频已在使用，不能删除"]);
            exit();
        }else{
            $del = M('video')->where(array('id'=>$id))->save(['status'=>3]);
            echo json_encode(['code'=>1]);
            exit();
        }
    }

    public function edit()
    {
        if(IS_POST){
            if ($_POST['smeta']['thumb']){
                $_POST['img_url'] = '/'.$_POST['smeta']['thumb'];
            }

//            if ($_SESSION['video_url']){
//                $_POST['video_url'] = $_SESSION['video_url'];
//            }

            $_SESSION['video_url']=null;
            $add = M('Video')->where(['id'=>$_POST['vid']])->save($_POST);
            $this->success("成功",U("Video/index"));
        }else{
            $id = $_GET['id'];
            $video = M('Video')->where(['id'=>$id])->find();
            $this->assign('video', $video);

            $category = M('VideoCate')->field('id, name')->select();
            $this->assign('category',$category);

            $this->display();
        }
    }

    //视频上架、下架
    public function status()
    {
        $id = $_REQUEST['id'];
        $status = $_REQUEST['status'];  //视频当前状态 1.上架 2.下架 3.删除
        $find = M('Banner')->where(array('type'=>2,'item_id'=>$id))->find();
        if ($status==1){
            //去下架
            if($find){
                echo json_encode(['code'=>-1, 'msg'=>"视频已在使用，不能下架"]);
                exit();
            }else{
                M('video')->where(array('id'=>$id))->save(['status'=>2]);
                echo json_encode(['code'=>1]);
                exit();
            }
        }else{
            //去上架
            M('video')->where(array('id'=>$id))->save(['status'=>1]);
            echo json_encode(['code'=>1]);
            exit();
        }
    }

    //活动分类
    public function category()
    {
        $where=[];
        $oauth_user_model=M('VideoCate');
        $count=$oauth_user_model
            ->where($where)
            ->count();
        $page = $this->page($count, 15);
        $lists = $oauth_user_model
            ->where($where)
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign('list', $lists);
        $this->display();
    }

    public function addCate()
    {
        if(IS_POST){
            $add = M('VideoCate')->add($_POST);
            $this->success("成功",U("Video/category"));
        }else{
            $this->display();
        }
    }

    public function editCate()
    {
        if(IS_POST){
            $add = M('VideoCate')->where(['id'=>$_POST['id']])->save($_POST);
            $this->success("成功",U("Video/category"));
        }else{
            $id = $_GET['id'];
            $rs = M('VideoCate')->where(['id'=>$id])->find();
            $this->assign('rs', $rs);
            $this->display();
        }
    }

    public function delCate()
    {
        $id = $_REQUEST['id'];
        $del = M('VideoCate')->where(array('id'=>$id))->delete();
        echo json_encode(['code'=>1]);
        exit();
    }

    /*
     *  排序
     */

    public function sort(){
        $ids = I('post.id',array());
        $sort = I('post.sort',array());
        try{
            foreach ($ids as $k => $id){
                D('Video')->where(array('id'=>$id))->save(array('sort'=>$sort[$k]));
            }
            $this->success('排序成功',U('Video/index'));
        }catch (\Exception $e){
            $this->error('排序失败');
        }
    }
}