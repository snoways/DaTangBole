<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/07 0013
 * Time: 下午 02:50
 */

namespace Managed\Controller;

use Think\Page;
use Think\Controller;

class TravelsController extends ManagedBaseController
{
    public function index()
    {
        $keyword = trim(I("keyword"));
        if ($keyword) {
            $where['title|author'] = ['like', "%$keyword%"];
            $this->assign('keyword', $keyword);
        }
        $where['agent_id'] = $_SESSION['small_id'];
        $oauth_user_model = M('Travels');
        $count = $oauth_user_model->where($where)->count();
        $page = $this->page($count, 15);
        $lists = $oauth_user_model
            ->where($where)
            ->order(['sort'=>'ASC','add_time'=>'DESC'])
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign('list', $lists);
        $this->display();
    }

    public function edit()
    {
        if (IS_POST) {
            if (empty($_POST['img1'])) {
                $this->error("请上传封面图片！");
            }
            if (strtotime($_POST['add_date']) > time()) {
                $this->error("发布日期错误！");
            }
            $_POST['agent_id'] = $_SESSION['small_id'];
            $_POST['img_url'] = $_POST['img1'];
            $_POST['add_time'] = date('Y-m-d H:i:s');
            $data = M('Travels')->where(array('id' => $_POST['id']))->find();
            if($data != null){
                if($data['status'] == -1){
                    $_POST['status'] = -1;
                }
                $result = M('Travels')->where(['id' => $data['id']])->save($_POST);
            }
            else{
                unset($_POST['id']);
                $_POST['is_home'] = 0;
                $_POST['sort'] = 99;
                $result = M('Travels')->add($_POST);
            }
            if ($result)
                $this->success('保存游记成功！', U('Travels/index'));

            $this->error("保存数据出现错误！");
            exit();
        }
        $id = I('get.id', 0);
        $data = M('Travels')->where(array('id' => $id))->find();
        if($data == null){
            $data['add_date'] = date('Y-m-d', time());
            $data['commentnum'] = 0;
            $data['status'] = 1;
        }
        $this->assign('info', $data);
        $this->display();
    }

    public function del()
    {
        $id = $_REQUEST['id'];
        $data = D('Travels')->where(array('id' => $id))->find();
        if ($data) {
            $imgs = explode(',', $data['img_list']);
            foreach ($imgs as $img) {
                unlink('.' . $img);
            }
            unlink('.' . $data['img_url']);
            if (D('Travels')->where(array('id' => $id))->delete()) {

                echo json_encode(['code' => 1]);
                exit();
            }
        }
        $this->error('删除失败');
    }

    //查看评论
    public function comment()
    {
        $id = I('id', 0, 'intval');
        $evaluate = D("TravelsComment");
        $where['t.tra_id'] = $id;
        $count = $evaluate->alias('t')
            ->join("LEFT JOIN fzm_parents p on t.user_id = p.id")
            ->where($where)
            ->count();
        $page = $this->page($count, 15);
        $list = $evaluate->alias('t')
            ->join("LEFT JOIN fzm_parents p on t.user_id = p.id")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order("t.create_time desc")
            ->where($where)
            ->field('t.id, t.create_time,t.content,p.parent_name,p.p_img')
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign('info', $list);
        $this->display();
    }


}
