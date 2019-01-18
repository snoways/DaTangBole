<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/25
 * Time: 9:44
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class QuestionController extends AdminbaseController
{

    public function index()
    {
        if(!empty($request['status'])){
            $where['status']=intval($request['status']);
        }

        $oauth_user_model=M('Question');
        $count=$oauth_user_model->where($where)->count();
        $page = $this->page($count, 15);

        $rs = M('Question')
            ->where($where)
            ->order("q_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach ($rs as $k=>$v)
        {
            $rs[$k]['answer'] = M('Answer')->where(['q_id'=>$v['id']])->select();
        }
        $this->assign("page", $page->show('Admin'));
        $this->assign('rs', $rs);
        $this->display();
    }

    /**
     * 添加题目
     * sunfan
     * 2017.10.25
     */
    public function addq()
    {
        if(IS_POST)
        {
            $map['question'] = $_POST['question'];
            $map['q_time'] = date('Y-m-d H:i:s');
            M('Question')->add($map);
            $this->success('题目添加成功！');
        }
        $this->display();
    }

    /**
     * 添加答案
     * sunfan
     * 2017.10.25
     */
    public function answer()
    {
        $id = $_GET['id'];
        $this->assign('id', $id);
        if(IS_POST)
        {
            $map['q_id'] = $id;
            $map['answer'] = $_POST['answer'];
//            $map['desc'] = $_POST['desc'];
            $map['score'] = $_POST['score'];
            $map['a_time'] = date('Y-m-d H:i:s');
            M('Answer')->add($map);
            $this->success('添加成功');
        }
        $rs = M('Question')->where(['id'=>$id])->find();
        $answer = M('Answer')->where(['q_id'=>$rs['id']])->select();
        $this->assign('rs', $rs);
        $this->assign('answer', $answer);
        $this->display();
    }

    /**
     * 编辑题目
     * sunfan
     * 2017.10.25
     */
    public function editq()
    {
        $id = $_GET['id'];
        if(IS_POST)
        {
            $map['question'] = $_POST['question'];
            $map['q_time'] = date('Y-m-d H:i:s');
            M('Question')->where(['id'=>$id])->save($map);
            $this->success('题目修改成功！',U("Question/index"));
        }
        $rs = M('Question')->where(['id'=>$id])->find();
        $this->assign('rs', $rs);
        $this->display();
    }

    /**
     * 删除问题
     * sunfan
     * 2017.10.25
     */
    public function deleteq()
    {
        $id = $_GET['id'];
        M('Answer')->where(['q_id'=>$id])->delete();
        M('Question')->where(['id'=>$id])->delete();
        $this->success('题目删除成功！');
    }

    /**
     * 编辑答案
     * sunfan
     * 2017.10.25
     */
    public function edita()
    {
        $aid = $_GET['id'];
        if(IS_POST)
        {
            $map['answer'] = $_POST['answer'];
//            $map['desc'] = $_POST['desc'];
            $map['score'] = $_POST['score'];
            M('Answer')->where(['id'=>$aid])->save($map);
            $this->success('修改成功',U("Question/index"));
        }
        $answer = M('Answer')->where(['id'=>$aid])->find();
        $rs = M('Question')->where(['id'=>$answer['q_id']])->find();
        $this->assign('rs', $rs);
        $this->assign('answer', $answer);
        $this->display();
    }

    /**
     * 删除答案
     * sunfan
     * 2017.10.25
     */
    public function deletea()
    {
        $id = $_GET['id'];
        M('Answer')->where(['id'=>$id])->delete();
        $this->success('答案删除成功！');
    }

    /**
     * 测评题库分数管理
     * sunfan
     * 2017.11.1
     */
    public function score()
    {
        $oauth_user_model=M('ScoreDesc');
        $count=$oauth_user_model->count();
        $page = $this->page($count, 15);

        $rs = $oauth_user_model
            ->order("sleft ASC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign('rs', $rs);
        $this->display();
    }

    /**
     * 添加分数描述
     * sunfan
     * 2017.11.1
     */
    public function addscore()
    {
        if(IS_POST)
        {
            $map['sleft'] = $_POST['sleft'];
            $map['sright'] = $_POST['sright'];
            $map['desc'] = $_POST['desc'];
            M('ScoreDesc')->add($map);
            $this->success('添加成功！');
        }
        $this->display();
    }

    /**
     * 编辑分数描述
     * sunfan
     * 2017.11.1
     */
    public function editscore()
    {
        $aid = $_GET['id'];
        if(IS_POST)
        {
            $map['sleft'] = $_POST['sleft'];
            $map['desc'] = $_POST['desc'];
            $map['sright'] = $_POST['sright'];
            M('ScoreDesc')->where(['id'=>$aid])->save($map);
            $this->success('修改成功');
        }
        $rs = M('ScoreDesc')->where(['id'=>$aid])->find();
        $this->assign('rs', $rs);
        $this->display();
    }


    /**
     * 删除答案
     * sunfan
     * 2017.11.01
     */
    public function deletescore()
    {
        $id = $_GET['id'];
        M('ScoreDesc')->where(['id'=>$id])->delete();
        $this->success('删除成功！');
    }
}