<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/23
 * Time: 10:38
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class BannerController extends AdminbaseController
{


    public function index()
    {
        $where = array('status'=>1);
        if($this->role=='agent'){
            $where['agent_id'] = $this->uid;
        }
        $count=M('Banner')->where($where)->count();
        $page = $this->page($count, 15);
        $list = M('Banner')->where($where)->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign('list',$list);
        $this->display();
    }
    public function add()
    {
        if(IS_POST){
            if(!empty($_FILES))
            {
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
//                        $pro['type'] = $_POST['type'];
                    $pro['url'] = "/data/upload/admin/" . $info['img']['savepath'] . $info['img']['savename'];
                }
            }else{
                $this->error("没有选择图片文件！");
            }
            $pro['position'] = $_POST['position'];
            $pro['type'] = $_POST['type'];
            $pro['item_id'] = $_POST['item_id'];
            if($pro['type']){
                if(empty($pro['item_id'])){
                    $this->error('必传参数不存在！');
                }
            }

            if($this->role=='agent'){
                $pro['agent_id'] = $this->uid;
            }
            $add = M('Banner')->add($pro);
            if($add){
                $this->success("添加成功！",U("Banner/index"));
            }
        }else{
            $this->display();
        }
    }

    public function edit()
    {
        if(IS_POST){
//            dump($_POST);die;
            if($_POST['val'] == $_POST['type'] && empty($_FILES)){
                $this->error("您还没有做修改！");
            }
            if(!$_FILES['img']['error'])
            {
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
                    $pro['url'] = "/data/upload/admin/" . $info['img']['savepath'] . $info['img']['savename'];
                }
            }

            $id = $_POST['id'];
            if($id){
                $pro['item_id'] = $id;
            }

            $pro['type'] = $_POST['type'];
            $pro['item_id'] = $_POST['item_id'];
            $pro['position'] = $_POST['position'];

            try{
                $save = M('Banner')->where(array('id'=>$_GET['id']))->save($pro);
                $this->success("保存成功！",U("Banner/index"));
                exit();
            }catch (\Exception $e){
                $this->error('操作失败');
            }
        }else{
            $id = $_GET['id'];
            $info = M('Banner')->where(array('id'=>$id))->find();
            if($info['type'] == 1){
                $info['teacher'] = M('teacher')->where(array('id'=>$info['item_id']))->find();
            }elseif($info['type'] == 2){
                $info['video'] = M('video')->where(array('id'=>$info['item_id']))->find();
            }elseif($info['type'] == 3){
                $info['news'] = M('news')->alias('n')->join('fzm_news_category c on n.cate_id = c.id')->field('n.*,c.cate_name')->where(array('n.id'=>$info['item_id'],'n.cate_id'=>1))->find();
            }elseif($info['type'] == 4){
                $info['news'] = M('news')->alias('n')->join('fzm_news_category c on n.cate_id = c.id')->field('n.*,c.cate_name')->where(array('n.id'=>$info['item_id'],'n.cate_id'=>2))->find();
            }elseif ($info['type'] == 5){
                $info['class'] = M('OnlineClass')->alias('n')->join("LEFT JOIN fzm_teacher t on t.id = n.tid")
                    ->order('n.oc_time desc')
                    ->where(array('n.id'=>$info['item_id']))
                    ->field('n.*, t.name')
                    ->find();
            }elseif ($info['type'] == 6){
                $info['class'] = M('Activity')
                    ->alias('a')
                    ->join('LEFT JOIN fzm_activity_cate ac ON ac.id=a.cate_id')
                    ->join('LEFT JOIN fzm_small_table st ON st.id=a.shop_id')
                    ->where(['a.id'=>$info['item_id']])
                    ->order("add_time DESC")
                    ->field('a.*, st.s_name, ac.name')
                    ->find();
            }
            $this->assign('info',$info);
            $this->display();
        }
    }
    public function delete()
    {
        $id = $_GET['id'];
        $info = M('Banner')->where(array('id'=>$id))->delete();
        if($info){
          $this->success();
        }
    }
    public function type(){
        $type = I("post.type");
        $t_name = trim(I('post.name'));
        $title = trim(I('post.title'));
        $news_title = trim(I('post.news_title'));
        if($type == 1){
            $where['name'] = array('like','%'.$t_name.'%');
            $where['status'] = 1;
            $where['state'] = 2;
            $info = M('teacher')->where($where)->order('t_time desc')->select();
            echo json_encode($info);
            exit();
        }elseif($type == 2){
            $where['title'] = array('like','%'.$title.'%');
            $info = M('video')->where($where)->order('add_time desc')->select();
            echo json_encode($info);
            exit();
        }elseif($type == 3){
            $where['n.news_title'] = array('like','%'.$news_title.'%');
            $where['n.cate_id'] = 1;
            if($this->role=='agent'){
                $where['n.agent_id'] = $this->uid;
            }
            $info = M('news')->alias('n')->join('fzm_news_category c on n.cate_id = c.id')->order('n.news_time desc')->where($where)->field('n.*,c.cate_name')->select();
            echo json_encode($info);
            exit();
        }elseif($type == 4){
            $where['n.news_title'] = array('like','%'.$news_title.'%');
            $where['n.cate_id'] = 2;
            if($this->role=='agent'){
                $where['n.agent_id'] = $this->uid;
            }
            $info = M('news')->alias('n')->join('fzm_news_category c on n.cate_id = c.id')->order('n.news_time desc')->where($where)->field('n.*,c.cate_name')->select();
            echo json_encode($info);
            exit();
        }elseif($type == 5){
            $where['n.title'] = array('like','%'.$news_title.'%');
            if($this->role=='agent'){
                $where['n.agent_id'] = $this->uid;
            }
            $info = M('OnlineClass')
                ->alias('n')
                ->order('n.oc_time desc')
                ->where($where)
                ->select();
            foreach ($info as &$item){
                if ($item['u_type']==1){    //用户类型 1.老师 2.商户 3.后台
                    //老师
                    $item['name'] = M('Teacher')->where(['id'=>$item['tid']])->getField('name');
                    $item['t_img'] = M('Teacher')->where(['id'=>$item['tid']])->getField('t_img');
                }elseif ($item['u_type']==1){    //用户类型 1.老师 2.商户 3.后台
                    //商户
                    $item['name'] = M('SmallTable')->where(['id'=>$item['tid']])->getField('s_name');
                    $item['t_img'] = M('SmallTable')->where(['id'=>$item['tid']])->getField('s_img');
                }else{
                    //平台
                    $item['name'] = "平台";
                    $item['t_img'] = "/public/images/img_new.png";
                }
            }
            echo json_encode($info);
            exit();
        }else{
            $where['a.status'] = ['neq', 3];
            $where['a.title'] = array('like','%'.$news_title.'%');
            $info = M('Activity')
                ->alias('a')
                ->join('LEFT JOIN fzm_activity_cate ac ON ac.id=a.cate_id')
                ->join('LEFT JOIN fzm_small_table st ON st.id=a.shop_id')
                ->where($where)
                ->order("add_time DESC")
                ->field('a.*, st.s_name, ac.name')
                ->select();
            echo json_encode($info);
            exit();
        }
    }

}