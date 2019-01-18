<?php
namespace User\Controller;

use Common\Controller\AdminbaseController;

class IndexadminController extends AdminbaseController {

    /**
     * 家长用户列表
     * sunfan
     * 2017.10.20
     */
    public function index(){
        $where=array();
        $request=I('request.');
        
        if(!empty($request['uid'])){
            $where['id']=intval($request['uid']);
        }

        if(!empty($request['sex'])){
            $where['child_sex']=intval($request['sex']);
        }
        
        if(!empty($request['keyword'])){
            $keyword=$request['keyword'];
            $keyword_complex=array();
            $keyword_complex['child_name']  = array('like', "%$keyword%");
            $keyword_complex['phone']  = array('like',"%$keyword%");
//            $keyword_complex['province']  = array('like',"%$keyword%");
//            $keyword_complex['city']  = array('like',"%$keyword%");
//            $keyword_complex['area']  = array('like',"%$keyword%");
//            $keyword_complex['address']  = array('like',"%$keyword%");
            $keyword_complex['child_grade']  = array('like',"%$keyword%");
            $keyword_complex['_logic'] = 'or';
            $where['_complex'] = $keyword_complex;
        }
        
    	$users_model=M("Parents");
    	
    	$count=$users_model->where($where)->count();
    	$page = $this->page($count, 20);
    	
    	$list = $users_model
            ->alias('u')
            ->join('LEFT JOIN fzm_level l ON l.id=u.level_id')
            ->where($where)
            ->order("p_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('u.*, l.name as level_name')
            ->select();
    	
    	$this->assign('list', $list);
    	$this->assign("page", $page->show('Admin'));
    	
    	$this->display(":index");
    }

    /**
     * 家长用户 禁用
     * sunfan
     * 2017.10.20
     */
    public function ban(){
    	$id= I('get.id',0,'intval');
    	if ($id) {
    		$result = M("Parents")->where(array("id"=>$id))->setField('status',2);
    		if ($result) {
                $parent = M("Parents")->where(array("id"=>$id))->find();
                S($parent['token'], 0, 1);
    			$this->success("会员禁用成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员禁用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }

    /**
     * 家长用户 启用
     * sunfan
     * 2017.10.20
     */
    public function cancelban(){
    	$id= I('get.id',0,'intval');
    	if ($id) {
    		$result = M("Parents")->where(array("id"=>$id))->setField('status',1);
    		if ($result) {
    			$this->success("会员启用成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员启用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }
}
