<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class UserController extends AdminbaseController{

	protected $users_model,$role_model;

	public function _initialize() {
		parent::_initialize();
		$this->users_model = D("Common/Users");
		$this->role_model = D("Common/Role");
	}

	// 管理员列表
	public function index(){
		$where = array("user_type"=>1);
		/**搜索条件**/
		$user_login = I('request.user_login');
		$user_email = trim(I('request.user_email'));
		if($user_login){
			$where['user_login'] = array('like',"%$user_login%");
		}
		
		if($user_email){
			$where['user_email'] = array('like',"%$user_email%");;
		}

		$count=$this->users_model->where($where)->count();
		$page = $this->page($count, 20);
        $users = $this->users_model
            ->where($where)
            ->order("create_time DESC")
            ->limit($page->firstRow, $page->listRows)
            ->select();
		$roles_src=$this->role_model->select();
		$roles=array();
		foreach ($roles_src as $r){
			$roleid=$r['id'];
			$roles["$roleid"]=$r;
		}
		$this->assign("page", $page->show('Admin'));
		$this->assign("roles",$roles);
		$this->assign("users",$users);



		$this->display();
	}

	// 管理员添加
	public function add(){

		$roles=$this->role_model->where(array('status' => 1))->order("id DESC")->select();
		$this->assign("roles",$roles);
		$this->display();
	}

	// 管理员添加提交
	public function add_post(){
		if(IS_POST){
			if(!empty($_POST['role_id']) && is_array($_POST['role_id'])){
				$role_ids=$_POST['role_id'];
				unset($_POST['role_id']);
				if ($this->users_model->create()!==false) {
					$result=$this->users_model->add();
					if ($result!==false) {
						$role_user_model=M("RoleUser");
						foreach ($role_ids as $role_id){
							if(sp_get_current_admin_id() != 1 && $role_id == 1){
								$this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
							}
							$role_user_model->add(array("role_id"=>$role_id,"user_id"=>$result));
						}
						$this->success("添加成功！", U("user/index"));
					} else {
						$this->error("添加失败！");
					}
				} else {
					$this->error($this->users_model->getError());
				}
			}else{
				$this->error("请为此用户指定角色！");
			}

		}
	}

	// 管理员编辑
	public function edit(){

	    $id = I('get.id',0,'intval');
		$roles=$this->role_model->where(array('status' => 1))->order("id DESC")->select();
		$this->assign("roles",$roles);
		$role_user_model=M("RoleUser");
		$role_ids=$role_user_model->where(array("user_id"=>$id))->getField("role_id",true);
		$this->assign("role_ids",$role_ids);

		$user=$this->users_model->where(array("id"=>$id))->find();
		$this->assign($user);
		$this->display();
	}

	// 管理员编辑提交
	public function edit_post(){
		if (IS_POST) {
			if(!empty($_POST['role_id']) && is_array($_POST['role_id'])){
				if(empty($_POST['user_pass'])){
					unset($_POST['user_pass']);
				}
				$role_ids = I('post.role_id/a');
				unset($_POST['role_id']);
				if ($this->users_model->create()!==false) {
					$result=$this->users_model->save();
					if ($result!==false) {
						$uid = I('post.id',0,'intval');
						$role_user_model=M("RoleUser");
						$role_user_model->where(array("user_id"=>$uid))->delete();
						foreach ($role_ids as $role_id){
							if(sp_get_current_admin_id() != 1 && $role_id == 1){
								$this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
							}
							$role_user_model->add(array("role_id"=>$role_id,"user_id"=>$uid));
						}
						$this->success("保存成功！");
					} else {
						$this->error("保存失败！");
					}
				} else {
					$this->error($this->users_model->getError());
				}
			}else{
				$this->error("请为此用户指定角色！");
			}

		}
	}

	// 管理员删除
	public function delete(){
	    $id = I('get.id',0,'intval');
		if($id==1){
			$this->error("最高管理员不能删除！");
		}

		if ($this->users_model->delete($id)!==false) {
			M("RoleUser")->where(array("user_id"=>$id))->delete();
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}

	// 管理员个人信息修改
	public function userinfo(){
		$id=sp_get_current_admin_id();
		$user=$this->users_model->where(array("id"=>$id))->find();
		$this->assign($user);
		$this->display();
	}

	// 管理员个人信息修改提交
	public function userinfo_post(){
		if (IS_POST) {
			$_POST['id']=sp_get_current_admin_id();
			$create_result=$this->users_model
			->field("id,user_nicename,sex,birthday,user_url,signature")
			->create();
			if ($create_result!==false) {
				if ($this->users_model->save()!==false) {
					$this->success("保存成功！");
				} else {
					$this->error("保存失败！");
				}
			} else {
				$this->error($this->users_model->getError());
			}
		}
	}

	// 停用管理员
    public function ban(){
        $id = I('get.id',0,'intval');
    	if (!empty($id)) {
    		$result = $this->users_model->where(array("id"=>$id,"user_type"=>1))->setField('user_status','0');
    		if ($result!==false) {
    			$this->success("管理员停用成功！", U("user/index"));
    		} else {
    			$this->error('管理员停用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }

    // 启用管理员
    public function cancelban(){
    	$id = I('get.id',0,'intval');
    	if (!empty($id)) {
    		$result = $this->users_model->where(array("id"=>$id,"user_type"=>1))->setField('user_status','1');
    		if ($result!==false) {
    			$this->success("管理员启用成功！", U("user/index"));
    		} else {
    			$this->error('管理员启用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }

    //获取所有的省
    public function getSub()
    {
        $code = I('code');
        $rs = M('Areas')->where(['parentId'=>$code])->select();
        echo json_encode($rs);
    }

    public function address(){

        $province = M('Areas')->where(['parentId'=>0])->select();
        $this->assign('province', $province);
        $this->display();
    }

    /*
     *  异步获取地址
     */
    function getAddress(){
        $id = I('get.id',0,'intval');
        try{
            $data = D('Users')->where(array('id'=>$id))->getField('areacode');
            $list = explode(',',$data);
            $areas = array();
            foreach ($list as $k => $v){
               $areas[$k]['address'] =  $address = $this->area($v);
               $areas[$k]['id']=$v;
            }
            $this->ajaxReturn(array('code'=>1,'msg'=>'成功','list'=>$areas));
        }catch (\Exception $e){
            $this->ajaxReturn(array('code'=>0,'msg'=>'系统错误'));
        }
    }

    /*
     * 删除地址
     */
    function deleteAddress(){
        $code = I('get.code','');
        $id = I('get.id',0,'intval');
        $data = D("Users")->where(array('id'=>$id))->getField('areacode');
        $list = explode(',',$data);
        $key = array_keys($list,$code);
        unset($list[$key[0]]);
        $str = implode(',',$list);
        if(D("Users")->where(array('id'=>$id))->save(array('areacode'=>$str))){
            $this->ajaxReturn(array('code'=>1));
        }
        $this->ajaxReturn(array('code'=>0));
    }

    /*
     *  添加地区
     */
    function area($code=''){

        $data = D('Areas')
            ->alias('a')
            ->join('__AREAS__ c on a.parentId =  c.areaId')
            ->join('__AREAS__ p on c.parentId =  p.areaId')
            ->where(array('a.areaId'=>$code,'a.areaType'=>2,'c.areaType'=>1,'p.areaType'=>0))
            ->field('a.areaName as area,c.areaName as city,p.areaName as province')
            ->find();
        return $data;
    }

    /*
     * 添加地址
     */
    public function addAddress(){

        $data = I('post.');
        try{
            $code = D("Users")->where(array('id'=>$data['id']))->getField('areacode');
            if ($code){$arr = explode(',',$code);}else{$arr = array();}
            if(!in_array($data['code'],$arr)){
                $arr[] = $data['code'];
            }else{
                $this->ajaxReturn(array('code'=>0,'msg'=>'地址重复了'));
            }
            $str = implode(',',$arr);
            $add = D('Users')->where(array('id'=>$data['id']))->save(array('areacode'=>$str));
            if($add){
                $this->ajaxReturn(array('code'=>1,'msg'=>'保存成功'));
            }
            $this->ajaxReturn(array('code'=>0,'msg'=>'添加失败'));
        }catch (\Exception $e){
            $this->ajaxReturn(array('code'=>0,'msg'=>'系统错误'));
        }

    }

}