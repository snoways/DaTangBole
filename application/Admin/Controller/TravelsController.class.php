<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/15
 * Time: 21:06
 */

namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class TravelsController extends AdminbaseController
{
    public function index()
    {
        $keyword = trim(I("keyword"));
        if ($keyword) {
            $where['t.title|t.author|st.s_name'] = ['like', "%$keyword%"];
            $this->assign('keyword', $keyword);
        }
        if (I('status')) {
            $where['t.status'] = I('status');
        }
        if (I('s_type')) {
            $where['st.s_type'] = I('s_type');
        }
        $count = M('Travels')->alias('t')->join('LEFT JOIN fzm_small_table st ON st.id=t.agent_id')->where($where)->count();
        $page = $this->page($count, 15);
        $list = M('Travels')->alias('t')->join('LEFT JOIN fzm_small_table st ON st.id=t.agent_id')->where($where)
            ->order("t.is_home desc, t.sort asc, t.add_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('t.*, st.s_name as agent_name, st.s_type')->select();
        foreach ($list as $key => $value) {
            switch ($value['s_type']) {
                case 1:
                    $list[$key]['s_type'] = "托管机构";
                    break;
                case 2:
                    $list[$key]['s_type'] = "教育机构";
                    break;
                case 3:
                    $list[$key]['s_type'] = "合作代理商";
                    break;
                case 4:
                    $list[$key]['s_type'] = "直属代理商";
                    break;
            }
        }
        $this->assign("page", $page->show('Admin'));
        $this->assign('list', $list);
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
            $_POST['agent_id'] = $_POST['agent_id'] != null ? $_POST['agent_id'] : 0;
            $_POST['img_url'] = $_POST['img1'];
            $_POST['add_time'] = date('Y-m-d H:i:s');
            $data = M('Travels')->where(array('id' => $_POST['id']))->find();
            if($data != null){
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
        if ($data == null) {
            $data['add_date'] = date('Y-m-d', time());
            $data['commentnum'] = 0;
            $data['status'] = 1;
        }
        $this->assign('info', $data);
        $this->display();
    }

    public function sortSt()
    {
        $ids = I('post.id', array());
        $sort = I('post.sort', array());
        try {
            foreach ($ids as $k => $id) {
                D('Travels')->where(array('id' => $id))->save(array('sort' => $sort[$k]));
            }
            $this->success('排序成功', U('Travels/index'));
        } catch (\Exception $e) {
            $this->error('排序失败');
        }
    }

    public function recommend()
    {
        M('Travels')->where(array('id' => I('id')))->save(['is_home' => I('is_home')]);
        $this->success("操作成功！", U("Travels/index"));
    }

    public function close()
    {
        M('Travels')->where(array('id' => I('id')))->save(['status' => I('status')]);
        $this->success("操作成功！", U("Travels/index"));
    }
}