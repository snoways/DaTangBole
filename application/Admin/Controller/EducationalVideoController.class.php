<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/29 0029
 * Time: 下午 05:42
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class EducationalVideoController extends AdminbaseController
{

    //教育机构视频管理

    public function index()
    {
        $where['sv.status'] = ['neq', 3];
        $oauth_user_model=M('SmallVideo');
//        if($this->role=='agent'){
//            $where['agent_id'] = $this->uid;
//        }
        if (!empty(I('keyword'))){
            $where['title'] = ['like', "%".I('keyword')."%"];
        }
        $count=$oauth_user_model
            ->alias('sv')
            ->join('LEFT JOIN fzm_small_table st ON st.id=sv.st_id')
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
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
}