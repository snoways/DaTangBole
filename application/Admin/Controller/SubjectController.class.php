<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/10/23
 * Time: 13:32
 */

namespace Admin\Controller;

//学科分类管理
use Common\Controller\AdminbaseController;

class SubjectController extends AdminbaseController
{
    /**
     * 学科列表
     * sunfan
     * 2017.10.23
     */
    public function index()
    {
        $subject = M('subject');
        $count   = $subject->where(['parentid'=>0])->count();
        $page    = $this->page($count, 5);
        $rs      = $subject
            ->where(['parentid'=>0])
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('sort asc')
            ->select();
        foreach ($rs as &$v){
            $v['son'] = $subject->where(['parentid'=>$v['id']])->order('sort asc')->select();
            foreach ($v['son'] as &$item) {
                $item['son'] = $subject->where(['parentid'=>$item['id']])->order('sort asc')->select();
            }
        }

        $this->assign("page", $page->show('Admin'));
        $this->assign('rs', $rs);
        $this->display();
    }

    /**
     * 删除学科
     * sunfan
     * 2017.10.23
     */
    public function del()
    {
        $id = I('get.id');
        M('Subject')->where(['id'=>$id])->delete();
        $this->success("删除成功！", U("Subject/index"));
    }

    /**
     * 添加一级学科
     * sunfan
     * 2017.10.23
     */
    public function add1()
    {
        if (IS_POST){
            $map['s_name'] = $_POST['s_name'];
            if (M('Subject')->where(['s_name'=>$map['s_name'], 'parentid'=>0])->find()){
                $this->error("已经有相同名称的一级学科了！");
            }else{
                M('Subject')->add($map);
                $this->success("添加成功！", U("Subject/index"));
            }
        }else{
            $this->display();
        }
    }

    /**
     * 添加二级学科
     * sunfan
     * 2017.10.23
     */
    public function add2()
    {
        if (IS_POST){
            $map['s_name'] = $_POST['s_name'];
            $map['parentid'] = $_POST['parentid'];
            M('Subject')->add($map);
            $this->success("添加成功！", U("Subject/index"));
        }else{
            $rs = M('Subject')->where(['parentid'=>0])->order('sort asc')->select();
            $this->assign('rs', $rs);
            $this->display();
        }
    }
    /**
     * 添加二级学科
     * jizhongbao
     * 2018.11.24
     */
    public function add3()
    {
        if (IS_POST){
            $map['s_name'] = $_POST['s_name'];
            $map['parentid'] = $_POST['parentid'];
            M('Subject')->add($map);
            $this->success("添加成功！", U("Subject/index"));
        }else{
            $rs = M('Subject')
                ->alias('s')
                ->join('LEFT JOIN fzm_subject s1 ON s.id = s1.parentid')
                ->where(['s.parentid'=>0])
                ->field('s1.id,s1.s_name,s.s_name as p_s_name')
                ->order('s.sort asc,s.id asc')
                ->select();
//            print_r($rs);die;
            $this->assign('rs', $rs);
            $this->display();
        }
    }

    /**
     * 设置排序
     */
    public function sort(){
        $sort = $_POST['sort'];
        $id = $_POST['id'];
        foreach($id as $k=>$v){
            $info = M('Subject')->where(array('id'=>$v))->save(array('sort'=>$sort[$k]));
            if($info == false && $info > 0){
                $this->error('网络错误');
            }
        }
        $this->success('保存成功',U('Subject/index'));
    }

    /**
     * 设置推荐
     */
    public function setHot(){
        $id = I('get.id',0,'intval');
        $is_hot = I("get.is_hot",0,'intval');
        $info = M("Subject")->where(array('id'=>$id))->save(array('is_hot'=>$is_hot));
        if($info !== false){
            $this->success('设置成功',U('subject/index'));
        }else{
            $this->error('网络错误');
        }
    }
}