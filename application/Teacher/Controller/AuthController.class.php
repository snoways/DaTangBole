<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/10/31
 * Time: 13:33
 */

namespace Teacher\Controller;

use JPush\Client;
use Client\Controller\MapiBaseController;
require('./application/Common/common/ServerAPI.php');
require('./application/Common/common/JPushSF.php');

class AuthController extends MapiBaseController
{

    private $AppKey='';
    private $AppSecret='';
    public function _initialize()
    {
        parent::_initialize();
        $this->AppKey = C('NetEaseConf.APPID');
        $this->AppSecret = C('NetEaseConf.APPSECRET');
    }


    /**
     *
     */
    public function login()
    {
        $data = $this->data;
        if($data['phone'] == ""){
            $this->ApiReturn(-1, '手机号不能为空');
        }
        if($data['password'] == ""){
            $this->ApiReturn(-1, '密码不能为空');
        }
        $rs = M('Teacher')->where(['phone'=>$data['phone']])->find();
        if (empty($rs)){
            $this->ApiReturn(-1, '用户不存在');
        }
        if($rs['status'] == 2){
            $this->ApiReturn(-1, '用户已冻结');
        }
        if ($rs['password']!=md5($data['password'])){
            $this->ApiReturn(-1, '密码不正确');
        }

        //更新极光推送用户id
        if ($data['registration_id']) {
            $appKey = C('JPush.TAPPID');
            $masterSecret = C('JPush.TAPPSECRET');
            $client = new Client($appKey, $masterSecret);
            $alias = 'teacher'.$rs['id'];
            if ($rs['registration_id'] != '') {
                if ($rs['registration_id'] != $data['registration_id']) {
                    $client->device()->updateAlias($data['registration_id'], $alias);
                    M('Teacher')->where(['id'=>$rs['id']])->setField('registration_id',$data['registration_id']);
                }
            } else {
                $client->device()->updateAlias($data['registration_id'], $alias);
                M('Teacher')->where(['id'=>$rs['id']])->setField('registration_id',$data['registration_id']);
            }
        }

        //更新云信用户信息
        $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
        $accid="t".$rs['id'];
        $name = $rs['name'];
        $img = C('WEBHOST').$rs['t_img'];
        $p->updateUinfo($accid, $name , $img);
//        dump($p->updateUinfo($accid, $name , $img));

        $arr = $this->userInfo($rs['id']);
        $token = $this->get_token($data['phone'],$data['password'],$rs['id'], 2);
        $arr['token'] = $token;
        $rs = M('Teacher')->where(['phone'=>$data['phone']])->save(array('token'=>$token));
        $this->ApiReturn(1,"成功",$arr);
    }

    public function register()
    {
        $data = $this->data;
        if(!in_array($data['t_type'],[1,2,3])){
            $this->ApiReturn(-1,'参数错误！');
        }
        if($data['phone'] == ""){
            $this->ApiReturn(-1, '手机号不能为空');
        }
        if($data['code'] == ""){
            $this->ApiReturn(-1, '验证码不能为空');
        }
        //验证手机号，验证码 补上
        checkPhoneCode($data['phone'], $data['code']);
        if($data['password_one'] == ""){
            $this->ApiReturn(-1, '密码不能为空');
        }
        if($data['password_two'] == ""){
            $this->ApiReturn(-1, '确认密码不能为空');
        }
        if($data['password_two'] != $data['password_one']){
            $this->ApiReturn(-1, '密码不一致');
        }
        if($data['province'] == ""){
            $this->ApiReturn(-1, '省不能为空');
        }
        if($data['city'] == ""){
            $this->ApiReturn(-1, '市不能为空');
        }
        if($data['area'] == ""){
            $this->ApiReturn(-1, '区不能为空');
        }
        if($data['address'] == ''){
            $this->ApiReturn(-1,'详细地址不能为空！');
        }
//        if($data['t_grade'] == ""){
//            $this->ApiReturn(-1, '年级不能为空');
//        }
        /*if($data['t_sub2'] == ""){
            $this->ApiReturn(-1, '科目不能为空');
        }*/

        if($data['position'] == ''){
            $this->ApiReturn(-1,'请上传经纬度');
        }
        if($data['type'] == ""){
            $this->ApiReturn(-1, '没有同意平台服务协议');
        }
        $find = M('Teacher')->where(array('phone'=>$data['phone'],'status'=>1))->find();
        if($find){
            $this->ApiReturn(-1, '账号已存在');
        }

        $where['phone'] = $data['phone'];
        $where['password'] = md5($data['password_one']);
        $where['province'] = $data['province'];
        $where['city'] = $data['city'];
        $where['area'] = $data['area'];
        $where['t_type'] = $data['t_type'];
        $where['address'] = $data['address'];
        $where['t_time'] = date('Y-m-d H:i:s');
        $where['level_id'] = 1;
        $where['position'] = $data['position'];
        $newstr = substr($data['phone'],-4);
        $where['name'] ='老师'.$newstr;
        $add = M('Teacher')->add($where);
        if(!$add){
            $this->ApiReturn(-1,"失败");
        }
        $arr = $this->userInfo($add);
        $token = $this->get_token($data['phone'],$data['password'],$add, 2);
        $arr['token'] = $token;
        $rs = M('Teacher')->where(['id'=>$add])->save(array('token'=>$token));

        //是否是员工推荐注册的
        if (!empty($data['empnum'])){
            $e_num = $data['empnum'];
            $einfo = M('Employee')->where(['e_num'=>$e_num])->find();
            if ($einfo['e_status']==1){
                M('Teacher')->where(['id'=>$add])->save(['empnum'=>$e_num]);
                M('Employee')->where(['e_num'=>$e_num])->setInc('count1');
                M('Employee')->where(['e_num'=>$e_num])->setInc('count3');
            }

        }

        $this->ApiReturn(1,"成功", $arr);
    }
    //微信授权
    public function WeLogin()
    {
        $Teacher_res = $this->data;
        $openid = $Teacher_res['openid']??$this->ApiReturn(-1, 'openid不能为空');
        $headimg = $Teacher_res['headimgurl']??$this->ApiReturn(-1, '头像不能为空');
        $nickname = $Teacher_res['nickname']??$this->ApiReturn(-1, '昵称不能为空');
        $sex = $Teacher_res['sex']??$this->ApiReturn(-1, '性别不能为空');

        if(!empty($Teacher_res['headimgurl'])){
            $headimg = downloadImage($Teacher_res['headimgurl']);
        }else{
            $headimg = "/public/images/headicon.png";
        }
        $where['openid'] = $openid;
        $users = M('Teacher');
        $count = $users->where($where)->find();
        if (!$count) {
            $data1['t_img'] = $headimg;
            $data1['name'] = $nickname;
            $data1['sex'] = $sex;
            $data1['openid'] = $openid;
            $data1['t_time'] = date('Y-m-d H:i:s');  //注册时间
            $id = M('Teacher')->add($data1);

            $token = $this->get_token($openid, $data1['t_time'], $id, 2);
            M('Teacher')->where(['id'=>$id])->save(['token'=>$token]);
            $arr['token'] = $token;
            $arr['code']=2;
            $this->ApiReturn(1, "需要绑定手机号", $arr);
        } else {
            /* $map=[];
            *if (!empty($headimg)){
                 $map['t_img'] = $headimg;
             }
             if (!empty($nickname)){
                 $map['name'] = $nickname;
             }
             M('Teacher')->where(['id'=>$count['id']])->save($map);*/

            //更新极光推送用户id
            if ($Teacher_res['registration_id']) {
                $appKey = C('JPush.TAPPID');
                $masterSecret = C('JPush.TAPPSECRET');
                $client = new Client($appKey, $masterSecret);
                $alias = 'teacher'.$count['id'];
                if ($count['registration_id'] != '') {
                    if ($count['registration_id'] != $Teacher_res['registration_id']) {
                        $client->device()->updateAlias($Teacher_res['registration_id'], $alias);
                        M('Teacher')->where(['id'=>$count['id']])->setField('registration_id',$Teacher_res['registration_id']);
                    }
                } else {
                    $client->device()->updateAlias($Teacher_res['registration_id'], $alias);
                    M('Teacher')->where(['id'=>$count['id']])->setField('registration_id',$Teacher_res['registration_id']);
                }
            }

            //更新云信用户信息
            $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
            $accid="t".$count['id'];
            $name = $count['name'];
            $img = C('WEBHOST').$count['t_img'];
            $p->updateUinfo($accid, $name , $img);


            if ($count['phone'] == "") {
                $arr = M('Teacher')->where(array('id' => $count['id']))->find();
                $token = $this->get_token($count['phone'], $count['openid'], $count['id'], 2);
                M('Teacher')->where(['id'=>$count['id']])->save(['token'=>$token]);
                $arr['token'] = $token;
                $arr['code'] = 2;
                $this->ApiReturn(1, "需要绑定手机号", $arr);
            } else {
                if($count['status'] == 2){
                    $this->ApiReturn(-1, '账号已被冻结');
                }
                $token = $this->get_token($count['phone'], $count['password'], $count['id'], 2);
                M('Teacher')->where(['id'=>$count['id']])->save(['token'=>$token]);
                $arr = $this->userInfo($count['id']);
                $arr['token'] = $token;
                $arr['code']=1;
                $this->ApiReturn(1, "登陆成功", $arr);
            }
        }
    }
    //绑定手机号
    public function bindPhone()
    {
        $data = $this->data;
        $id = S($data['token'])??$this->ApiReturn(-1, 'token无效或已过期');
        $phone = $data['phone']??$this->ApiReturn(-1, '手机号不能为空');
        $password = $data['password']??$this->ApiReturn(-1, '密码不能为空');
        $checkpassword = $data['checkpassword']??$this->ApiReturn(-1, '确认密码不能为空');
        $code = $data['code']??$this->ApiReturn(-1, '验证码不能为空');
        //checkPhoneCode($phone,$code);
        checkPhoneCode($phone, $code);
        $user = M("Teacher");
        $row = $user->where(['phone'=>$phone])->find();
        //手机注册后  微信登录 过来的情况
        if($row){
            if (!empty($row['openid'])){
                $arr['code'] = 302;
                $this->ApiReturn(-1, '此手机号已绑定其他微信号',$arr);
            }else{
                $res = $user->where(array('id'=>$id))->find();//微信登陆 注册过来的账号 要把这里的账号信息放到手机号注册的账号上 合并
                $map['openid'] = $res['openid'];
                if (empty($row['t_img'])){
                    $map['t_img'] = $res['t_img'];
                }
                if (empty($row['name'])){
                    $map['name'] = $res['name'];
                }
                $data1 = $user -> where(['phone'=>$phone]) -> save($map);
                $token = $this->get_token($data['phone'],$data['password'],$id, 2);
                M('Teacher')->where(['phone'=>$phone])->save(['token'=>$token]);
                $arr = $this->userInfo($row['id']);
                $arr['token']=$token;
                $arr['code']=301;
                $user->where("id='{$id}'")->delete();
                if($data1!==false){
                    $this->ApiReturn(1, '绑定成功,数据已同步', $arr);
                }else{
                    $this->ApiReturn(-1, '绑定失败');
                }

            }
        }else{
//            $where['t_type'] = $data['t_type'];
//            if(!in_array($data['t_type'],[1,2,3]))$this->ApiReturn(-1,'老师身份未传！');
            $where['id'] = $id;
            $res = $user->where($where)->find();
            if($res){
                $nmap['phone'] = $phone;
                $nmap['password'] = md5($password);
                $data1 = $user -> where("id ={$id}") -> save($nmap);
                $token = $this->get_token($data['phone'],$data['password'],$id, 2);
                $user -> where("id ={$id}") ->save(array('token'=>$token));
                $arr = $this->userInfo($id);
                $arr['token']=$token;
                $arr['code']=303;
                $this->ApiReturn(1, '绑定成功', $arr);
            }else{
                $this->ApiReturn(-1, '绑定失败');
            }
        }
    }

    /*
     * 微信登录-绑定手机号后，发现手机号也没有注册，于是走一下类似注册的流程
     * sunfan
     * 2018.7.20
     */
    public function addOther()
    {
        $data = $this->data;
        $id = S($data['token'])??$this->ApiReturn(20003, "需要传token");
        if(!in_array($data['t_type'],[1,2,3])){
            $this->ApiReturn(-1,'参数错误！');
        }
        if($data['province'] == ""){
            $this->ApiReturn(-1, '省不能为空');
        }
        if($data['city'] == ""){
            $this->ApiReturn(-1, '市不能为空');
        }
        if($data['area'] == ""){
            $this->ApiReturn(-1, '区不能为空');
        }
        if($data['address'] == ''){
            $this->ApiReturn(-1,'详细地址不能为空！');
        }
        if($data['position'] == ''){
            $this->ApiReturn(-1,'请上传经纬度');
        }
        if($data['type'] == ""){
            $this->ApiReturn(-1, '没有同意平台服务协议');
        }

        $where['province'] = $data['province'];
        $where['city'] = $data['city'];
        $where['area'] = $data['area'];
        $where['t_type'] = $data['t_type'];
        $where['address'] = $data['address'];
        $where['level_id'] = 1;
        $where['position'] = $data['position'];
        $save = M('Teacher')->where(['id'=>$id])->save($where);
        if(!$save){
            $this->ApiReturn(-1,"失败");
        }
        $arr = $this->userInfo($id);
        $token = $this->get_token($data['phone'],$data['password'],$id, 2);
        $arr['token'] = $token;
        $rs = M('Teacher')->where(['id'=>$id])->save(array('token'=>$token));

        $this->ApiReturn(1,"成功", $arr);
    }

    //用户信息
    protected function userInfo($user_id)
    {
        try {
            $id=$user_id;
            $db_user=M("Teacher");
            $info = $db_user->where('id='.$id)->find();
            $arr=array();
            $arr['phone'] = $info['phone'];
            $arr['name'] = $info['name'];
            $arr['gender'] = $info['sex'];
            $arr['age'] = $info['age'];
            $arr['score'] = $info['score'];
            $arr['teach_age'] = $info['teach_age'];
            $arr['headImg'] = $info['t_img'];
            $arr['state'] = $info['state'];
            if($arr['state'] == 3){
                $arr['fail_reason'] = $info['fail_reason'];
            }
            $arr['t_type'] = $info['t_type'];
            $arr['level'] = $info['level_id'];
            $arr['level_name'] = M('Level')->where(['id'=>$info['level_id']])->getField('name');
            $arr['level_icon'] = M('Level')->where(['id'=>$info['level_id']])->getField('icon');


            $order = M('Order');
            $arr['student_num'] = $order->where(array('teacher_id'=>$id, 'status'=>array('in', array(1,2,3))))->count();       //订单数（学生数）
            $find = $order->where(array('teacher_id'=>$id, 'status'=>array('in', array(1,2,3))))->select();  //总课时
            $tomal = "";
            if($find){
                foreach($find as $k=>$v){
                    $tomal +=$v['class_num'] *30;
                }
                $arr['class_num']  = $tomal;
            }else{
                $arr['class_num']  = 0;
            }

            $star = $order->where(array('teacher_id'=>$id, 'status'=>array('in', array(-1,1,2))))->count();  //进行课时
           /* $star_class = "";
            foreach($star as $k1=>$v1){
                $star_class +=$v1['class_num'];
            }
            if($star_class){
                $arr['star_class']  = $star_class;
            }else{
                $arr['star_class']  = 0;
            }*/
            $arr['star_class'] = $star;

            $select = M('assess')->select();         //评价个数
            $count = 0;
            if($select){
                foreach($select as $k=>$v){
                    $find = M('Order')->where(array('id'=>$v['order_id'],'teacher_id'=>$id))->find();
                    if($find){
                        $count++;
                    }

                }
            }
            $arr['pingjia'] = $count;

            //认证
            if(!empty($info['idcard1']) &&!empty($info['idcard2'])&&!empty($info['hand_card'])){
              $arr['certification'] = 1;
            }else{
                $arr['certification'] = -1;
            }
            //资质
            if(!empty($info['t_card'])){
                $arr['qualification'] = 1;
            }else{
                $arr['qualification'] = -1;
            }
            //学历
            if(!empty($info['academic'])){
                $arr['education'] = 1;
            }else{
                $arr['education'] = -1;
            }
            //心里测评
            if(!empty($info['evaluation'])){
                $arr['evaluation'] = 1;
            }else{
                $arr['evaluation'] = -1;
            }
            return $arr;
        } catch (\Exception $e) {
            $this->ApiReturn(10002,'系统错误');
        }
    }

    
    //找回密码
    public function backpsw()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['phone'] ==""){
            $this->ApiReturn(-1, '电话不存在');
        }
        if($data['code'] ==""){
            $this->ApiReturn(-1, '验证码不存在');
        }
        if($data['password'] ==""){
            $this->ApiReturn(-1, '密码不存在');
        }
        if($data['checkpassword'] ==""){
            $this->ApiReturn(-1, '确认密码不存在');
        }
        if($data['password'] != $data['checkpassword']){
            $this->ApiReturn(-1, '密码不一致');
        }
        //验证手机号，验证码 补上
        checkPhoneCode($data['phone'], $data['code']);
        $save = M('Teacher')->where(array('phone'=>$data['phone']))->save(array('password'=>md5($data['password'])));
        //echo M('Teacher')->getLastSql();exit;
        $this->ApiReturn(1,'修改成功');
    }

    public function country()
    {
        $list = M('Country')->field('id as country_id, country_name, code')->select();
        $this->ApiReturn(1, 'success', $list);
    }

   

}