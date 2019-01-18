<?php

namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class AgentController extends AdminbaseController
{
    public function index()
    {
//        $where['s_type'] = 1;//1.托管 2.教育机构 3合作代理商 4直属代理商
//        if ($this->role == 'agent') {
//            $where['agent_id'] = $this->uid;
//        }
        if (I('keyword')) {
            $where['address|s_name|s_phone|c_name'] = ['like', '%' . I('keyword') . '%'];
        }
        if (I('s_status')) {
            $where['s_status'] = I('s_status');
        }
        if (I('is_home')) {
            $where['is_home'] = I('is_home');
        }
        $count = M('small_table')->where($where)->count();
        $page = $this->page($count, 10);
        $list = M('small_table')
            ->alias('st')
            ->join('LEFT JOIN fzm_level l ON l.id=st.level_id')
            ->where($where)
            ->order(['sort' => 'asc', 's_time' => 'desc'])
            ->limit($page->firstRow, $page->listRows)
            ->field('st.*, l.name as level_name')
            ->select();
        $this->assign("list", $list);
        $this->assign("page", $page->show('Admin'));
        $this->display('index');
    }

    /**
     * 代理商排序
     */
    public function sortSt(){
        $ids = I('post.id',array());
        $sort = I('post.sort',array());
        try{
            foreach ($ids as $k => $id){
                D('SmallTable')->where(array('id'=>$id))->save(array('sort'=>$sort[$k]));
                D('SmallTableSort')->where(array('sid'=>$id))->save(array('sort'=>$sort[$k]));
            }
            $this->success('排序成功',U('Agent/index'));
        }catch (\Exception $e){
            $this->error('排序失败');
        }
    }

    /**
     * 冻结  解冻
     */
    public function ban(){
        $id = I("get.id",0,'intval');
        if($id){
            $status = I("get.status",0,'intval');
            if($status){
                $info = M("small_table")->where(['id'=>$id])->save(['status'=>$status]);
                if($info){
                    $this->success('操作成功！',U('Agent/index'));
                }
            }
        }
        $this->error('操作出现错误');
    }

    /**
     * 推荐
     */
    public function recommend()
    {
        M('small_table')->where(array('id'=>I('id')))->save(['is_home'=>I('is_home')]);
        M('small_table_sort')->where(array('sid'=>I('id')))->save(['is_home'=>I('is_home')]);
        $this->success("操作成功！",U("Agent/index"));
    }

    /**
     * 删除代理商
     */
    public function delete()
    {
        $del = M('small_table')->where(array('id'=>$_GET['id']))->delete();
        $this->success("成功");
    }
}