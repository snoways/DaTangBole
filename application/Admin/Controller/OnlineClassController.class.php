<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/14 0014
 * Time: 下午 05:09
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class OnlineClassController extends AdminbaseController
{

    /**
     * 线上课堂模块
     * sunfan
     * 2018.5.14
     */

    public function index()
    {
        $where['m.status'] = ['neq', 3];
        if (!empty(I('keyword'))){
            $where['title|m.t_sub1|m.t_sub2|m.t_sub3'] = ['like', "%".I('keyword')."%"];
        }
        $model = M("OnlineClass");
        $count = $model->alias('m')
            ->join("LEFT JOIN fzm_teacher t on t.id = m.tid")
            ->where($where)
            ->count();
        $page = $this->page($count,15);
        $info = $model->alias('m')
            ->order(['sort'=>'asc', 'oc_time'=>'desc'])
            ->where($where)
            ->limit($page->firstRow,$page->listRows)
            ->select();

        foreach ($info as &$item){
            if ($item['u_type']==1){    //用户类型 1.老师 2.商户 3.后台
                //老师
                $item['name'] = M('Teacher')->where(['id'=>$item['tid']])->getField('name');
                $item['t_img'] = M('Teacher')->where(['id'=>$item['tid']])->getField('t_img');
            }elseif ($item['u_type']==2){    //用户类型 1.老师 2.商户 3.后台
                //商户
                $item['name'] = M('SmallTable')->where(['id'=>$item['tid']])->getField('s_name');
                $item['t_img'] = M('SmallTable')->where(['id'=>$item['tid']])->getField('s_img');
            }else{
                //平台
                $item['name'] = "平台";
                $item['t_img'] = "/public/images/img_new.png";
            }
        }

        $this->assign('page',$page->show('Admin'));
        $this->assign('info',$info);
        $this->display();
    }
    //添加
    public function add_index(){
        if(IS_POST){
            $data = I('post.');
            if(!$data['sub1']){
                $this->error('请选择一级学科');
            }
            if(!$data['sub2']){
                $this->error('请选择二级学科');
            }
            if(!$data['sub3']){
                $this->error('请选择三级学科');
            }
            if(empty($data['title'])){
                $this->error('请填写标题');
            }
            if ($data['price_type'] == 1) {
                if($data['price'] <= 0 || !preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $data['price'])){
                    $this->error('请输入大于0的并且最多2为小数的价格');
                }
                if($data['access_password']==true && !preg_match('/^(\w){4,8}$/',$data['access_password'])){
                    $this->error('只能输入4-8个字母、数字、下划线');
                }
            }
            if(!$data['video_cover'] || !$data['video']){
                $this->error('您好，请上传视频和封面图！');
            }
            $online_class = D("OnlineClass");
            $config = M('Config')->find();
            $info = $online_class->add([
                'title'           =>      $data['title'],
                'tid'             =>      -1,
                'u_type'          =>      3,
                'img_url'         =>      $data['video_cover'],
                'video_url'       =>      $data['video'],
                'oc_time'         =>      date('Y-m-d H:i:s'),
//                'vip_price'       =>      $data['price']*$config['vip_radio'],
                'price'           =>      $data['price'],
                'content'         =>      $_POST['content'],
                't_sub1'          =>      $data['sub1'],
                't_sub2'          =>      $data['sub2'],
                't_sub3'          =>      $data['sub3'],
                'price_type'      =>      $data['price_type'],
                'access_password' =>      $data['access_password'],
                'age_id'          =>      $_POST['age_id'],
                'hot'             =>      $data['hot']
            ]);
            if($info){
                $this->success('添加成功');
            }else{
                $this->error('网络错误');
            }
        }else{
            $subject = D("subject");
            $sub1 = $subject->where(['parentid'=>0])->order('sort asc')->select();
            $ages = M('ages')->field('id,min,max')->order('id')->select();
            foreach ($ages as &$item) {
                if ($item['max'] == 0) {
                    $item['title'] = $item['min'].'岁以上';
                } else {
                    $item['title'] = $item['min'].'-'.$item['max'].'岁';
                }
                unset($item['min']);
                unset($item['max']);
            }
            $this->assign('sub1',$sub1);
            $this->assign('ages',$ages);
            $this->display();
        }
    }
    //二级学科接口
    public function subject(){
        $id = I("post.id");
        $subject = D("subject");
        $sub1 = $subject->where(['parentid'=>$id])->order('sort asc')->select();
        echo json_encode($sub1);
        exit();
    }
    //编辑
    public function edit(){
        $online_class = D("OnlineClass");
        $config = M('Config')->find();
        if(IS_POST){
            $id = I("post.id",0);
            if(!$_POST['sub1']){
                $this->error('请选择一级学科');
            }
            if(!$_POST['sub2']){
                $this->error('请选择二级学科');
            }
            if(!$_POST['sub3']){
                $this->error('请选择三级学科');
            }
            if(empty($_POST['title'])){
                $this->error('请填写标题');
            }
            if ($_POST['price_type'] == 1) {
                if($_POST['price'] <= 0 || !preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $_POST['price'])){
                    $this->error('请输入大于0的并且最多2为小数的价格');
                }
                if($_POST['access_password'] == true && !preg_match('/^(\w){4,8}$/',$_POST['access_password'])){
                    $this->error('只能输入4-8个字母、数字、下划线');
                }
            }
            if(!$_POST['video_cover'] || !$_POST['video']){
                $this->error('您好，请上传视频和封面图！');
            }
            $online_class->where(['id'=>$id])->save([
                'title'           =>      $_POST['title'],
                'u_type'          =>      3,
                'img_url'         =>      $_POST['video_cover'],
                'video_url'       =>      $_POST['video'],
//                'vip_price'       =>      $_POST['price_type']?$_POST['price']*$config['vip_radio']:0,
                'price'           =>      $_POST['price_type']?$_POST['price']:0,
                'content'         =>      $_POST['content'],
                't_sub1'          =>      $_POST['sub1'],
                't_sub2'          =>      $_POST['sub2'],
                't_sub3'          =>      $_POST['sub3'],
                'price_type'      =>      $_POST['price_type'],
                'access_password' =>      $_POST['access_password'],
                'age_id'          =>      $_POST['age_id'],
                'hot'             =>      $_POST['hot']
            ]);
            $this->success('保存成功');
        }else{
            $id = I("get.id",0,'intval');
            $info = $online_class->where(['id'=>$id])->find();
            $subject = D("subject");

            $sub1 = $subject->where(['parentid'=>0])->order('sort asc')->select();
            $sub2_id = $subject->where(['s_name'=>$info['t_sub1']])->find();
            $sub3_id = $subject->where(['s_name'=>$info['t_sub2']])->find();
            $sub2 = $subject->where(['parentid'=>$sub2_id['id']])->order('sort asc')->select();
            $sub3 = $subject->where(['parentid'=>$sub3_id['id']])->order('sort asc')->select();
            $ages = M('ages')->field('id,min,max')->order('id')->select();
            foreach ($ages as &$item) {
                if ($item['max'] == 0) {
                    $item['title'] = $item['min'].'岁以上';
                } else {
                    $item['title'] = $item['min'].'-'.$item['max'].'岁';
                }
                unset($item['min']);
                unset($item['max']);
            }
            $this->assign('sub1',$sub1);
            $this->assign('sub2',$sub2);
            $this->assign('sub3',$sub3);
            $this->assign('ages',$ages);
            $this->assign('info',$info);
            $this->display();
        }
    }
    public function status(){
        $id = I("id");
        $hot = I("hot");
        $online_class = D("OnlineClass");
        $info = $online_class->where(['id'=>$id])->save(['hot'=>$hot]);
        if($info){
            $arr['code'] = 1;
        }else{
            $arr['msg'] = '网络错误,修改失败';
        }
        echo json_encode($arr);
        exit();
    }
    public function is_popular(){
        $id = I("id");
        $hot = I("hot");
        $online_class = D("OnlineClass");
        $info = $online_class->where(['id'=>$id])->save(['is_popular'=>$hot]);
        if($info){
            $arr['code'] = 1;
        }else{
            $arr['msg'] = '网络错误,修改失败';
        }
        echo json_encode($arr);
        exit();
    }
    //禁用
    public function ban(){
        $id = I('id');
        $status = I('status');
        if($id){
            if($status){
                $info = M('OnlineClass')->where(array('id'=>$id))->save(array('status'=>$status));
                if($info){
                    $this->success('保存成功！',U('OnlineClass/index'));
                }else{
                    $this->error('网路错误！');
                }
            }else{
                $this->error('数据失败！');
            }

        }else{
            $this->error('数据传入失败！');
        }
    }
    //删除
    public function delete(){
        $id = I('id',0,'intval');
        $info = M('OnlineClass')->where(array('id'=>$id))->save(array('status'=>3));
        if($info){
            $this->success('删除成功！',U('OnlineClass/index'));
        }else{
            $this->error('网络错误！');
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
                D('OnlineClass')->where(array('id'=>$id))->save(array('sort'=>$sort[$k]));
            }
            $this->success('排序成功',U('OnlineClass/index'));
        }catch (\Exception $e){
            $this->error('排序失败');
        }
    }
    //查看评价
    public function evaluate(){
        $where = [];
        $id = I("id",0,'intval');
        if($id){
            $where['o.id'] = ['eq',$id];
            $model = D("OnlineEvaluate");
            $count = $model->alias('o')
                ->join("LEFT JOIN fzm_parents p on o.uid = p.id")
                ->join("LEFT JOIN fzm_online_class c on o.c_id = c.id")
                ->where($where)
                ->count();
            $page = $this->page($count,15);
            $list = $model->alias('o')
                ->join("LEFT JOIN fzm_parents p on o.uid = p.id")
                ->join("LEFT JOIN fzm_online_class c on o.c_id = c.id")
                ->limit($page->firstRow,$page->listRows)
                ->where($where)
                ->order("o.create_time desc")
                ->field('o.*,p.parent_name,p.p_img,c.title')
                ->select();
            $this->assign('page',$page->show("Admin"));
            $this->assign('list',$list);
            $this->display();
        }
    }
    //删除评论
    public function del_evaluate(){
        $id = I("get.id",0,'intval');
        $model = D("OnlineEvaluate");
        $info = $model->where(['id'=>$id])->delete();
        if($info){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }
}