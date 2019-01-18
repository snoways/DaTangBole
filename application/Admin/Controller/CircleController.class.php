<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/11 0011
 * Time: 下午 01:27
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;
require('./application/Common/common/JPushSF.php');

class CircleController extends AdminbaseController
{

    /**
     * 亲子时光
     * sunfan
     * 2018.7.11
     */
    public function index()
    {
        $this->setList(1);
        $this->display();
    }


    public function add()
    {
        if(IS_POST){
            $id = $this->insert(1);
            if($id){
                $this->success('添加成功');
                exit();
            }
            $this->error("添加失败");
        }
        $this->display();
    }

    function edit(){
        if(IS_POST){
            $save = $this->update();
            $id = I('post.id',0);
            if($save){
                $this->success('保存成功',U('Circle/edit',array('id'=>$id)));
                exit();
            }
            $this->error('系统错误');
        }
        $id = I('get.id',0);
        $data = D('Circle')->where(array('id'=>$id))->find();
        $this->assign('data',$data);
        $list = explode(',',$data['img_list']);
        $img_list = array();
        foreach ($list as $k => $img){
            $img_list[$k]['url'] = $img;
            $img_list[$k]['alt'] = $img;
        }
        $this->assign('img_list',$img_list);
        $this->display();
    }

    /*
     *  校园日志
     */
    public function school(){
        $this->setList(2);
        $this->display();
    }


    public function addLog()
    {
        if(IS_POST){
            $id = $this->insert(2);
            if($id){
                $this->success('添加成功');
                exit();
            }
            $this->error("添加失败");
        }
        $this->display();
    }

    public function editLog(){
        if(IS_POST){
            $save = $this->update();
            if($save){
                $id = I('post.id',0);
                $this->success('保存成功',U('Circle/edit',array('id'=>$id)));
                exit();
            }
            $this->error('系统错误');
        }
        $id = I('get.id',0);
        $data = D('Circle')->where(array('id'=>$id))->find();
        $this->assign('data',$data);
        $list = explode(',',$data['img_list']);
        $img_list = array();
        foreach ($list as $k => $img){
            $img_list[$k]['url'] = $img;
            $img_list[$k]['alt'] = $img;
        }
        $this->assign('img_list',$img_list);
        $this->display();
    }


    /** ----------------- 修改删除 ---------------------------  */

    /*
     *  删除
     */
    public function delete(){
        $id = I('id',0);
        $msg = I('msg');
        $data = D('Circle')->where(array('id'=>$id))->find();
        if($data){
            $imgs = explode(',',$data['img_list']);
            foreach ($imgs as $img){
                unlink('.'.$img);
            }
            unlink('.'.$data['video_cover']);
            unlink('.'.$data['video_url']);
            if(D('Circle')->where(array('id'=>$id))->delete()){
                $act = 'index';
                if($data['option'] == 2){
                    $act = 'school';
                }
                $option = $data['option']==1?'亲子圈':'教学圈';
                if ($data['u_type'] == 1) {
                    //推送
                    $registration_id = M('parents')->where(['id'=>$data['uid']])->getField('registration_id');
                    if ($registration_id) {
                        $receive = array('registration_id'=>[$registration_id]);//别名
                        $push = new \JPushSF(C('JPush.PAPPID'),C('JPush.PAPPSECRET'));
                        $push->push($receive, 0, '',"您的一条{$option}因{$msg}已被管理员删除。");
                    }
                }
                if ($data['u_type'] == 2) {
                    //推送
                    $registration_id = M('teacher')->where(['id'=>$data['uid']])->getField('registration_id');
                    if ($registration_id){
                        $receive = array('registration_id'=>[$registration_id]);//别名
                        $push = new \JPushSF(C('JPush.TAPPID'),C('JPush.TAPPSECRET'));
                        $push->push($receive, 0, '',"您的一条{$option}因{$msg}已被管理员删除。");
                    }
                }
                if ($data['u_type'] == 3) {
                    //推送
                    $registration_id = M('small_table')->where(['id'=>$data['uid']])->getField('registration_id');
                    if ($registration_id){
                        $receive = array('registration_id'=>[$registration_id]);
                        $push = new \JPushSF(C('JPush.SAPPID'),C('JPush.SAPPSECRET'));
                        $r = $push->push($receive, 0, '',"您的一条{$option}因{$msg}已被管理员删除。");
//                        print_r($r);
                    }
                }
                $this->success('删除成功',U('Circle/'.$act));exit();
            }
        }
        $this->error('删除失败');
    }

    private function insert($opt = 1){
        $data = array(
            'c_time'=>date('Y-m-d H:i:s'),
            'u_type'=>4,
            'uid'=>0,
            'c_type'=>I('post.c_type',1),
            'content'=>I('post.content'),
            'option'=>$opt
        );
        if(I('post.c_type')==3){
            $data['video_cover']=I('post.video_cover');
            $data['video_url']=I('post.video');
        }elseif (I('post.c_type')==2){
            $data['img_list'] = implode(',',I('post.photos_url'));
        }
        return D('Circle')->add($data);
    }

    private function setList($opt = 1){
        $db = M('Circle');
        $where['option'] = $opt;      //默认亲子时光--1.亲子时光 2.青春日记

        if (!empty(I('keyword'))){
            $where['content'] = ['like', "%".I('keyword')."%"];
        }

        if (!empty(I('name'))){
            $ids = [];
            $parents = M('Parents')->where(['parent_name'=>['like', "%".I('name')."%"]])->field('id')->select();
            if (!empty($parents))foreach ($parents as $item){
                array_push($ids, $item['id']);
            }
            $teacher = M('Teacher')->where(['name'=>['like', "%".I('name')."%"]])->field('id')->select();
            if (!empty($teacher))foreach ($teacher as $item){
                array_push($ids, $item['id']);
            }
            $small = M('SmallTable')->where(['s_name'=>['like', "%".I('name')."%"]])->field('id')->select();
            if (!empty($small))foreach ($small as $item){
                array_push($ids, $item['id']);
            }
            if (!empty($ids)){
                $where['uid'] = ['in', $ids];
            }
        }

        if (I('start_time') && I('end_time')){
            $where['c_time'] = ['between', [I('start_time'), I('end_time')]];
        }

        $count = $db
            ->alias('c')
            ->where($where)
            ->count();
        $page = $this->page($count,15);

        $list = $db
            ->alias('c')
            ->where($where)
            ->limit($page->firstRow,$page->listRows)
            ->order('c_time DESC')
            ->field('c.id as circle_id, c_time, content, img_list, video_url, zan, c.view_num, c_type, uid, u_type, report_num')
            ->select();
        $report_log = M('report_log');
        foreach ($list as $k=>$item) {
            //图文
            $img_list = $item['img_list'];
            $list[$k]['img_list']=[];
            if($item['c_type']==2){
                $list[$k]['img_list'] = explode(',', $img_list);
            }

            if ($item['u_type']==1){
                //家长用户
                $user = M('Parents')->where(['id'=>$item['uid']])->find();
                $list[$k]['img'] = $user['p_img'];
                $list[$k]['uname'] = $user['parent_name'];
            }elseif ($item['u_type']==2){
                //老师用户
                $user = M('Teacher')->where(['id'=>$item['uid']])->find();
                $list[$k]['img'] = $user['t_img'];
                $list[$k]['uname'] = $user['name'];
            }elseif ($item['u_type']==3){
                //商户用户
                $user = M('SmallTable')->where(['id'=>$item['uid']])->find();
                $list[$k]['img'] = $user['s_img'];
                $list[$k]['uname'] = $user['s_name'];
            }else{
                //后台用户
                $list[$k]['img'] = "/public/images/img_new.png";
                $list[$k]['uname'] = "平台";
            }
            if ($item['report_num'] > 0) {
                $list[$k]['report_num_list'] = $report_log->where(['cid'=>$item['circle_id']])->field('reason,count(*) num')->group('reason')->select();
            }
        }
        $this->assign('page',$page->show('Admin'));
        $this->assign('list',$list);
    }

    private function update(){
        $id = I('post.id',0);
        $data = array(
            'c_time'=>date('Y-m-d H:i:s'),
            'c_type'=>I('post.c_type',1),
            'content'=>I('post.content'),
        );
        if(I('post.c_type')==3){
            $data['video_cover']=I('post.video_cover');
            $data['video_url']=I('post.video');
        }elseif (I('post.c_type')==2){
            $data['img_list'] = implode(',',I('post.photos_url'));
        }
        try{
            D('Circle')->where(array('id'=>$id))->save($data);
            return true;
        }catch(\Exception $e){
            return false;
        }
    }

    /*
     *  评论
     */
    public function comment(){
        $id = I('get.id',0,'');
        $where = array(
            'cid'=>$id
        );

        $count = D('Comment')
            ->where($where)
            ->count();

        $page = $this->page($count,16);

        $list = D('Comment')
            ->limit($page->firstRow.','.$page->listRows)
            ->where($where)
            ->select();

        foreach ($list as &$item) {
            if($item['u_type'] == 1){
              $par =  D('Parents')->where(array('id'=>$item['uid']))->find();
              $item['u_name'] = $par['parent_name'];
              $item['avatar'] = $par['p_img'];
            }elseif ($item['u_type'] == 2){
              $tech = D('Teacher')->where(array('id'=>$item['uid']))->find();
              $item['u_name'] = $tech['s_name'];
              $item['avatar'] = $tech['t_img'];
            }elseif ($item['u_type'] == 3){
              $host = D('SmallTable')->where(array('id'=>$item['uid']))->find();
              $item['u_name'] = $host['s_name'];
              $item['avatar'] = $host['s_img'];
            }
        }

        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->display();
    }


    /*
     *  删除评论
     */
    public function deleteComment(){
        $id = I('get.id',0);
        $com = D('Comment')
            ->alias('cm')
            ->join('__CIRCLE__ c on cm.cid = c.id')
            ->where(array('cm.id'=>$id))
            ->field('cm.*,c.option')
            ->find();
        if( D('Comment')->where(array('id'=>$id))->delete()){
            $url = 'Circle/comment';
            if($com['option'] == 2){
                $url = 'Circle/school';
            }
            $this->success('删除成功',U($url,array('id'=>$com['cid'])));
            exit();
        }
        $this->error('删除失败');
    }

    //亲子圈举报原因选项列表
    public function report_reason()
    {
        $data = M('report_reason')->select();
        $this->assign('list',$data);
        $this->display();
    }
    //修改亲子圈举报原因选项
    public function edit_report_reason()
    {
        $id = I('id',0,'intval');
        if ($id) {
            if (IS_POST) {
                $title = trim(I('title'));
                if (M('report_reason')->save(['id'=>$id,'title'=>$title])) {
                    $this->success('修改成功');
                } else {
                    $this->error('修改失败');
                }
            } else {
                $data = M('report_reason')->where(['id'=>$id])->find();
                $this->assign('data',$data);
                $this->display();
            }
        }
    }
}