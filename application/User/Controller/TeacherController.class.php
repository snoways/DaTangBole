<?php
namespace User\Controller;

use Common\Controller\AdminbaseController;

class TeacherController extends AdminbaseController {

    public function _initialize()
    {
        parent::_initialize();
        $this->AppKey = C('NetEaseConf.APPID');
        $this->AppSecret = C('NetEaseConf.APPSECRET');
    }
	
	// 老师列表
	public function index(){
        $where=array();

        if(I('t_type')){
           $where['t_type'] = I('t_type');
        }
        if($this->role == 'agent'){
            $areas = D('Users')->where(array('id'=>$this->uid))->getField('areacode');
            $where['areaId'] = array('in',$areas);
        }
        $request=I('request.');

        if(!empty($request['uid'])){
            $where['id']=intval($request['uid']);
        }

        if(!empty($request['state'])){
            $where['state']=intval($request['state']);
        }

        if(!empty($request['keyword'])){
            $keyword=$request['keyword'];
            /*$keyword_complex=array();
            $keyword_complex['phone']  = array('like', "%$keyword%");
            $keyword_complex['u.name']  = array('like',"%$keyword%");
            $keyword_complex['_logic'] = 'or';
            $where['_complex'] = $keyword_complex;*/

            $where['phone|u.name'] = array('like',"%$keyword%");
        }

		$oauth_user_model=M('Teacher');
		$count=$oauth_user_model
            ->alias('u')
            ->join('LEFT JOIN fzm_level l ON l.id=u.level_id')
            ->where($where)
            ->count();
		$page = $this->page($count, 20);
		$lists = $oauth_user_model
            ->alias('u')
            ->join('LEFT JOIN fzm_level l ON l.id=u.level_id')
            ->where($where)
		    ->order("sort ASC")
		    ->limit($page->firstRow . ',' . $page->listRows)
            ->field('u.*, l.name as level_name')
		    ->select();

        $where['state'] = 1;
        $stacount=$oauth_user_model
            ->alias('u')
            ->join('LEFT JOIN fzm_level l ON l.id=u.level_id')
            ->where($where)
            ->count();
        $this->assign('count', $stacount);
		$this->assign("page", $page->show('Admin'));
		$this->assign('lists', $lists);
		$this->display();
	}

    /**
     * 老师用户 禁用
     * sunfan
     * 2017.10.20
     */
    public function ban(){
        $id= I('get.id',0,'intval');
        if ($id) {
            $result = M("Teacher")->where(array("id"=>$id))->setField('status',2);
            if ($result) {
                $teacher = M("Teacher")->where(array("id"=>$id))->find();
                S($teacher['token'], 0, 1);
                $this->success("会员禁用成功！", U("teacher/index"));
            } else {
                $this->error('会员禁用失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 老师用户 启用
     * sunfan
     * 2017.10.20
     */
    public function cancelban(){
        $id= I('get.id',0,'intval');
        if ($id) {
            $result = M("Teacher")->where(array("id"=>$id))->setField('status',1);
            if ($result) {
                $this->success("会员启用成功！", U("teacher/index"));
            } else {
                $this->error('会员启用失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 老师用户 详情
     * sunfan
     * 2017.10.20
     */
    public function detail()
    {
        if (IS_POST) {
            $id = I('post.id',0,'intval');
            if ($_POST['status']){
                $status = $_POST['status'];
                M("Teacher")->where(array("id"=>$id))->setField('status',$status);
            }elseif ($_POST['state']){
                $info = M("Teacher")->where(array("id"=>$id))->find();
                $state = $_POST['state'];
                if ($state==3){
                    //审核不通过
                    M("Teacher")->where(array("id"=>$id))->save(array('state'=>3,'fail_reason'=>$_POST['fail_reason']));
                    send_message($info['phone'],'SMS_126356286');
                }elseif ($state==2){
                    M("Teacher")->where(array("id"=>$id))->setField('state',$state);
                    //审核通过
                    send_message($info['phone'],'SMS_134316155');
                }
//                M("Teacher")->where(array("id"=>$id))->setField('state',$state);
            }
            $this->success("操作成功！", U("teacher/index"), 1);
        }else {
            $id = I('get.id', 0, 'intval');
            $rs = M('Teacher')->where(['id'=>$id])->find();

            $examine = D("examine");
            $examine_list = $examine->where(['uid'=>$id])->select();
            $this->assign('examine',$examine_list);
            //不通过个数
            $count = $examine->where(['uid'=>$id,'status'=>2])->count();
            $this->assign('count',$count);

            //通过个数
            $yer_count = $examine->where(['uid'=>$id,'status'=>1])->count();
            $this->assign('yes_count',$yer_count);

            //未审核个数
            $undetermined = $examine->where(['uid'=>$id,'status'=>0])->count();
            $this->assign('undetermined',$undetermined);



            $honor = M('TeacherHonor')->where(['teacher_id'=>$id])->select();
            $this->assign('detail', $rs);
            $this->assign('honor', $honor);
            $this->display();
        }
    }

    /**
     * 老师用户 审核
     * sunfan
     * 2017.10.20
     */
    public function check(){
        $id= I('get.id',0,'intval');
        if ($id) {
            $ok = I('get.ok',0);
            $info = M("Teacher")->where(array("id"=>$id))->find();
            if ($ok==3){
                //审核不通过
                M("Teacher")->where(array("id"=>$id))->save(array('state'=>3));
                send_message($info['phone'],'SMS_126356286');
            }else{
                M("Teacher")->where(array("id"=>$id))->setField('state',$ok);
                if ($ok==2)
                {
                    //审核通过
                    send_message($info['phone'],'SMS_134316155');
                    //注册云信账号
//                    $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
//                    $result = $p->createUserId($id, $info['phone']);
                }
            }
            $this->success("审核成功！", U("teacher/index"));
        } else {
            $this->error('数据传入失败！');
        }
    }


    /*
     *  排序
     */

    public function sort(){
        $ids = I('post.id',array());
        $sort = I('post.sort',array());
        try{
            foreach ($ids as $k => $id){
                D('Teacher')->where(array('id'=>$id))->save(array('sort'=>$sort[$k]));
                D('TeacherSort')->where(array('tid'=>$id))->save(array('sort'=>$sort[$k]));
            }
            $this->success('排序成功',U('Teacher/index'));
        }catch (\Exception $e){
            $this->error('排序失败');
        }
    }

    /**
     * 设置推荐
     */
    public function setHot(){
        $id = I('get.id',0,'intval');
        $is_hot = I("get.is_hot",0,'intval');
        $info = M("Teacher")->where(array('id'=>$id))->save(array('is_hot'=>$is_hot));
        if($info !== false){
            $this->success('设置成功',U('Teacher/index'));
        }else{
            $this->error('网络错误');
        }
    }
    //审核各项信息
    public function examine(){
        $examine = D("examine");
        $where['uid'] = I("post.id",0);
        $where['option_key'] = I("post.str");
        $save['status'] = I("post.status",1);
        $info = $examine->where($where)->save($save);
        if($info){
            echo 1;
        }else{
            echo -1;
        }
        exit();
    }
}