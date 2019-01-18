<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/23
 * Time: 17:38
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
require('./application/Common/common/JPushSF.php');

class EducationalController extends AdminbaseController
{

    public function _initialize()
    {
        parent::_initialize();
        $this->AppKey = C('NetEaseConf.APPID');
        $this->AppSecret = C('NetEaseConf.APPSECRET');
    }

    public function index()
    {
        $where['s_type']=2;
        if($this->role=='agent'){
            $where['agent_id'] = $this->uid;
        }
        if (I('keyword')){
            $where['address|s_name|s_phone|s_content'] = ['like', '%'.I('keyword').'%'];
        }
        if (I('s_status')){
            $where['s_status'] = I('s_status');
        }
        if (I('is_home')){
            $where['is_home'] = I('is_home');
        }
        $count = M('small_table')->where($where)->count();
        $page=$this->page($count,10);
        $list = M('small_table')
            ->alias('st')
            ->join('LEFT JOIN fzm_level l ON l.id=st.level_id')
            ->where($where)
            ->order(['sort'=>'asc', 's_time'=>'desc'])
            ->limit($page->firstRow, $page->listRows)
            ->field('st.*, l.name as level_name')
            ->select();
        $this->assign("list",$list);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }
    public function add()
    {
        if(IS_POST){
            if(!empty($_FILES))
            {
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize = 11048576;// 设置附件上传大小
                $upload->exts = array('jpg', 'png', 'jpeg', 'gif');// 设置附件上传类型
                $upload->rootPath = './public/images/'; // 设置附件上传根目录
                $upload->savePath = ''; // 设置附件上传（子）目录
                // 上传文件
                $info = $upload->upload();
                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                } else {
                    $_POST['s_img'] = "/public/images/" . $info['img']['savepath'] . $info['img']['savename'];
                    $_POST['s_time'] = date('Y-m-d H:i:s');
                }
            }else{
                $this->error("没有选择图片文件！");
            }
            if (M('SmallTable')->where(['s_phone'=>$_POST['s_phone']])->find())
            {
                $this->error("手机号已存在！");
            }
            $_POST['s_password'] = md5(substr($_POST['s_phone'], -6));
            $_POST['s_type']=2;
            if($this->role='agent'){
                $_POST['agent_id'] = $this->uid;
            }
            $add = M('small_table')->add($_POST);
            if($add){
                $this->success("添加成功！",U("Educational/index"));
            }
        }else{
            if ($this->role == 'admin'){
                $agent = D('Users')
                    ->alias('u')
                    ->join('fzm_role_user as r on u.id = r.user_id')
                    ->where(array('r.role_id'=>17))
                    ->select();
                $this->assign('agent',$agent);
            }else{
                $this->assign('agent',false);
            }
            $this->display();
        }
    }
    public function edit()
    {
        if(IS_POST){
            if(!empty($_FILES))
            {
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize = 11048576;// 设置附件上传大小
                $upload->exts = array('jpg', 'png', 'jpeg', 'gif');// 设置附件上传类型
                $upload->rootPath = './public/images/'; // 设置附件上传根目录
                $upload->savePath = ''; // 设置附件上传（子）目录
                // 上传文件
                $info = $upload->upload();
                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                } else {
                    $_POST['s_img'] = "public/images/" . $info['img']['savepath'] . $info['img']['savename'];
                }
            }
            $add = M('small_table')->where(array('id'=>$_GET['id'], 's_type'=>2))->save($_POST);
            $this->success("添加成功！",U("Educational/index"));
        }else{
            $info = M('small_table')->where(array('id'=>$_GET['id'], 's_type'=>2))->find();
            $this->assign("info",$info);
            if ($this->role == 'admin'){
                $agent = D('Users')
                    ->alias('u')
                    ->join('fzm_role_user as r on u.id = r.user_id')
                    ->where(array('r.role_id'=>17))
                    ->select();
                $this->assign('agent',$agent);
            }else{
                $this->assign('agent',false);
            }
            $this->display();
        }
    }
    public function delete()
    {
        $del = M('small_table')->where(array('id'=>$_GET['id'], 's_type'=>2))->delete();
        $this->success("成功");
    }
    public function img_add(){
        $id = I('get.id',0,'intval');
        $list = M('small_images')->where("st_id='{$id}'")->select();
        if($list[0]['st_id']){
            $image = $list;
        }else{
            $image = '';
        }
        $this->assign('image',$image);
        $this->display();
    }
    public function insert(){
        $id = $_POST['id'];
        if(!$_FILES['url']['error'][0]){
            //实例化上传文件的类
            $upload = new \Think\Upload();
            //选择上传文件的大小
            $upload->maxSize = 3145728 ;
            //选择上传文件的类型
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            //将上传的图片存放在指定文件夹下
            $upload->rootPath = "./data/upload/admin/";
            $fileName=$_FILES['url']['name'];//这样就能够取得上传的文件名
            $fileExtensions=strrchr($fileName, '.');//通过对$fileName的处理，就能够取得上传的文件的后缀名
            $serverFileName=basename($fileName,$fileExtensions)."_".uniqid().$fileExtensions;
            $upload->saveRule=$serverFileName;
            //调用上传文件类中的upload方法
            $info = $upload -> upload();
            //判断调用是否成功
            $coun = count($_FILES['url']['name']);
            for ($i=0;$i<$coun;$i++){
                //得到要存入数据库中的地址
                $pic = $info[$i]["savepath"].$info[$i]['savename'];
                $data['url'] = "/data/upload/admin/".$pic;
                $data['st_id'] = $id;
                $data['url'] = image($data['url']);
                $rees = M('small_images')->add($data);
            }
            if($rees!==false){
                $this -> success("添加成功",U('Educational/index'));
            }else{
                $this -> error("添加失败");
            }
        }else{
            $this->error('没有图片被上传！');
        }
    }

    public function sort(){
        if ($_POST){
            foreach ($_POST['imgid'] as $k=>$item){
                M('small_images')->where(['id'=>$item])->save(['sort'=>$_POST['sort'][$k]]);
            }
            $this->success('保存成功！');
            exit();
        }
        $id = I('id',0,'intval');
        $list = M('small_images')->where("st_id='{$id}'")->order('sort asc')->select();
        if($list[0]['st_id']){
            $image = $list;
        }else{
            $image = '';
        }
        $this->assign('image',$image);
        $this->display();
    }


    public function modlunbo(){
        $id = $_POST['u_id'];
        $st_id = $_POST['id'];
        if(!$_FILES['url']['error']){
            //实例化上传文件的类
            $upload = new \Think\Upload();
            //选择上传文件的大小
            $upload->maxSize = 3145728 ;
            //选择上传文件的类型
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            //将上传的图片存放在指定文件夹下
            $upload->rootPath = "./data/upload/admin/";
            $info = $upload -> upload();
            //得到要存入数据库中的地址
            if(!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            }else {// 上传成功 获取上传文件信息
                $data['url'] = "/data/upload/admin/" . $info['url']['savepath'] . $info['url']['savename'];
            }
        }
        $rees = M('small_images')->where(array('id'=>$id))->save($data);
//        echo M('small_images')->getLastSql();die;
        if ($rees !== false) {
            $this->success("保存成功", U('Educational/img_add',array('id'=>$st_id)));
        } else {
            $this->success("保存成功");
        }
    }

    public function delurl(){
        $pro = M('small_images');
        $st_id = I("st_id",0,'intval');
        $id = $_GET['id'];
        $tu1 = $pro->field('url')->find($id);
        $rootpath1 = './Date/Upload/admin/';
        //删除文件夹中的图片
        unlink('.'.$tu1['url']);
        $del = $pro->delete($id);
        if($del!==false){
            $this->success('删除成功',U('Educational/img_add',array('id' =>$st_id)));
        }else {
            $this->error("添加失败");
        }
    }


    /**
     * 图集管理
     */
    public function imglist()
    {
        $id = I('get.id',0,'intval');
        $list = M('small_images')->where("st_id='{$id}'")->order('sort')->select();
        if($list[0]['st_id']){
            $image = $list;
        }else{
            $image = '';
        }
        $this->assign('image',$image);
        $this->display();
    }

    /**
     * 批量删除图集
     */
    public function del_batch_url(){
        $ids = I('ids');
        $pro = M('small_images');
        foreach ($ids as $id){
            $tu1 = $pro->field('url')->find($id);
            $rootpath1 = './Date/Upload/admin/';
            //删除文件夹中的图片
            unlink('.'.$tu1['url']);
            $del = $pro->delete($id);
        }
        $this->success('删除成功');
    }

    /**
     * 批量添加
     */
    public function imgadd()
    {
        if (IS_POST){
            $id = $_POST['id'];
            $data['st_id'] = $id;
            foreach ($_POST['photos_url'] as $img){
                $data['url'] = image('/data/upload/'.$img);
                $rees = M('small_images')->add($data);
            }
            $this->success('添加成功');
        }
        $rs = M('SmallTable')->where(['id'=>I('id')])->find();
        $this->assign('rs', $rs);
        $this->display();
    }

    /**
     * 审核
     * 二期
     * 2018.3.21
     */
    public function review()
    {
        $info = M('small_table')->where(array('id'=>$_GET['id'], 's_type'=>2))->find();

        $examine = D("examine_table");
        $examine_list = $examine->where(['uid'=>$info['id']])->select();
        $this->assign('examine',$examine_list);
        $info = M('small_table')->where(array('id'=>$_GET['id'], 's_type'=>2))->find();
        $this->assign("info",$info);
        $this->display();
    }

    /**
     * 审核通过、不通过
     * 二期
     * 2018.3.21
     */
    public function review_status()
    {
//        if(I('status') == 2){
//            $count = D("examine_table")->where(['uid'=>I("id"),'status'=>['in',[0,2]]])->count();
//            if($count > 0 ){
//                $this->error('当前有资料未审核通过，不可进行审核通过操作！');
//            }
//        }
        M('small_table')->where(array('id'=>I('id'), 's_type'=>2))->save(['s_status'=>I('status'), 'fail_reason'=>I('reason')]);
        $alias = 'smalltable'.I('id');
        //给商户推送
        $push = new \JPushSF(C('JPush.SAPPID'),C('JPush.SAPPSECRET'));
        $receive = array('alias'=>[$alias]);//别名
        $phone = M('small_table')->where(['id'=>I('id')])->getField('s_phone');
        if (I('status')==3){
            $push->push($receive,0,"您的账号未通过审核，请前往APP查看");
            //给商户发短信
            send_message($phone,'SMS_126356286');
            echo json_encode(['code'=>1, 'msg'=>"操作成功"]);
            exit();
        }else{
            $push->push($receive,0, "您的账号已通过审核，请前往APP查看");
            //给商户发短信
            send_message($phone,'SMS_139237300');
            $this->success('审核成功',U('Educational/index'));exit();
        }
        $this->error('操作失败');
    }

    /**
     * 评价
     * 二期
     * 2018.3.23
     */
    public function star()
    {
        if (IS_POST){
            $_POST['star'] = round(($_POST['star1']+$_POST['star2']+$_POST['star3']+$_POST['star4']+$_POST['star5'])/5);
            M('small_table')->where(array('id'=>$_POST['id'], 's_type'=>2))->save($_POST);
            M('small_table_sort')->where(array('sid'=>$_POST['id'], 's_type'=>2))->save(['star'=>$_POST['star']]);
            $this->success("评价成功！",U("Educational/index"));
            exit();
        }
        $info = M('small_table')->where(array('id'=>$_GET['id'], 's_type'=>2))->find();
        $this->assign("info",$info);
        $this->display();
    }

    /**
     * 推荐
     * sunfan
     * 2018.7.11
     */
    public function recommend()
    {
        M('small_table')->where(array('id'=>I('id')))->save(['is_home'=>I('is_home')]);
        M('small_table_sort')->where(array('sid'=>I('id')))->save(['is_home'=>I('is_home')]);
        $this->success("操作成功！",U("Educational/index"));
    }

    /*
     *  排序
     */

    public function sortSt(){
        $ids = I('post.id',array());
        $sort = I('post.sort',array());
        try{
            foreach ($ids as $k => $id){
                D('SmallTable')->where(array('id'=>$id))->save(array('sort'=>$sort[$k]));
                D('SmallTableSort')->where(array('sid'=>$id))->save(array('sort'=>$sort[$k]));
            }
            $this->success('排序成功',U('Educational/index'));
        }catch (\Exception $e){
            $this->error('排序失败');
        }
    }

    //审核各项信息
    public function examine(){
        $examine = D("examine_table");
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