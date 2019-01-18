<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/11/15
 * Time: 9:41
 */

namespace ManagedClient\Controller;

use Client\Controller\MapiBaseController;
use JPush\Client;
require('./application/Common/common/ServerAPI.php');
require('./application/Common/common/JPushSF.php');
//require './simplewind/Core/Library/Vendor/jpush/src/JPush/client.php';

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


    public function send()
    {
        $push = new \JPushSF(C('JPush.PAPPID'),C('JPush.PAPPSECRET'));
        $push->push("parent30", 0, '哈哈');
    }

    public function login(){
        $data = $this->data;
        $s_phone = $data['phone']??$this->ApiReturn(-1, '手机号不能为空');
        $s_passowrd = $data['password']??$this->ApiReturn(-1, '密码不能为空');
        $registration_id = !empty($data['registration_id'])?$data['registration_id']:$this->ApiReturn(-1, '设备id不能为空');

        $small = M("SmallTable");
        $rs = $small->where(['s_phone'=>$s_phone])->find();
        if (empty($rs)){
            $this->ApiReturn(-1, '用户不存在');
        }

        if($rs['status'] == 2){
            $this->ApiReturn(-1, '用户已冻结');
        }

        if ($rs['s_password']!=$s_passowrd){
            $this->ApiReturn(-1, '密码不正确');
        }

        //更新极光推送用户id
        if ($registration_id) {
            $appKey = C('JPush.SAPPID');
            $masterSecret = C('JPush.SAPPSECRET');
            $client = new Client($appKey, $masterSecret);
            $alias = 'smalltable'.$rs['id'];
            if (!empty($rs['registration_id'])) {
                if ($rs['registration_id'] != $registration_id) {
                    $client->device()->updateAlias($registration_id, $alias);
                    $small->where(['id'=>$rs['id']])->setField('registration_id',$registration_id);
                }
            } else {
                $client->device()->updateAlias($registration_id, $alias);
                $small->where(['id'=>$rs['id']])->setField('registration_id',$registration_id);
            }
        }

        //更新云信用户信息
        $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
        $accid="s".$rs['id'];
        $name = $rs['s_name'];
        $img = C('WEBHOST').$rs['s_img'];
        $sre = $p->updateUinfo($accid, $name , $img);
        if ($sre['code'] != 200) {
            $sre1 = $p->createUserId($accid, $name ,'', $img);
//            print_r($sre1);die;
        }
        $arr = $this->userInfo($rs['id']);
        $token = $this->get_token($data['phone'],$data['password'],$rs['id'], 3);
        $arr['token'] = $token;
        $rs = $small->where(['s_phone'=>$data['phone']])->save(array('token'=>$token));
        $this->ApiReturn(1,"成功",$arr);
    }

    public function register()
    {
        $data = $this->data;
        $small = M("SmallTable");
        $phone = !empty($data['phone'])?$data['phone']:$this->ApiReturn(-1, '手机号不能为空');
        $code = $data['code']??$this->ApiReturn(-1, '验证码不能为空');
        //验证手机号，验证码 补上
        checkPhoneCode($phone, $code);
        $find = $small->where(array('s_phone'=>$data['phone']))->find();
        if($find){
            $this->ApiReturn(-1, '账号已存在');
        }

        $password = !empty($data['password'])?$data['password']:$this->ApiReturn(-1, '密码不能为空');
        $s_type = !empty($data['s_type'])?$data['s_type']:$this->ApiReturn(-1, '用户身份类型不能为空');

        $add = $small->add([
            's_phone'   =>  $phone,
            's_password'=>  $password,
            's_time'    =>  date('Y-m-d H:i:s'),
            's_type'    =>  $s_type,    //1.托管 2.教育机构
        ]);
        if(!$add){
            $this->ApiReturn(-1,"失败");
        }

        //是否是员工推荐注册的
        if (!empty($data['empnum'])){
            $e_num = $data['empnum'];
            $einfo = M('Employee')->where(['e_num'=>$e_num])->find();
            if ($einfo['e_status']==1){
                $small->where(['s_phone'=>$data['phone']])->save(['empnum'=>$e_num]);
                M('Employee')->where(['e_num'=>$e_num])->setInc('count1');
                M('Employee')->where(['e_num'=>$e_num])->setInc('count4');
            }
        }


        $arr = $this->userInfo($add);
        $token = $this->get_token($data['phone'],$data['password'],$add, 3);
        $arr['token'] = $token;
        $rs = $small->where(['s_phone'=>$data['phone']])->save(array('token'=>$token));
        $this->ApiReturn(1,"成功",$arr);
    }
    //微信授权
    public function WeLogin()
    {
        $data = $this->data;
        $openid = !empty($data['openid'])?$data['openid']:$this->ApiReturn(-1, 'openid不能为空');
        $headimg = $data['headimgurl'];
        $nickname = !empty($data['nickname'])?$data['nickname']:$this->ApiReturn(-1, '昵称不能为空');
        $registration_id = !empty($data['registration_id'])?$data['registration_id']:$this->ApiReturn(-1, '设备id不能为空');

        if(!empty($headimg)){
            $headimg = downloadImage($headimg);
        }else{
            $headimg = "/public/images/headicon.png";
        }
        $where['openid'] = $openid;
        $users = M('SmallTable');
        $count = $rs = $users->where($where)->find();
        if (!$count) {
            //注册新用户
            $id = $users->add([
                's_img'     =>  $headimg,
                's_time'    =>  date('Y-m-d H:i:s'),
                'c_name'    =>  $nickname,
                'openid'      =>  $openid,
            ]);

            $token = $this->get_token($openid, time(), $id, 3);
            $users->where(['id'=>$id])->save(['token'=>$token]);
            $arr['token'] = $token;
            $arr['code']=2;
            $this->ApiReturn(1, "需要绑定手机号", $arr);
        } else {
            //更新极光推送用户id
            if ($registration_id) {
                $appKey = C('JPush.SAPPID');
                $masterSecret = C('JPush.SAPPSECRET');
                $client = new Client($appKey, $masterSecret);
                $alias = 'smalltable'.$rs['id'];
                if ($rs['registration_id'] != '') {
                    if ($rs['registration_id'] != $registration_id) {
                        $client->device()->updateAlias($registration_id, $alias);
                        $users->where(['id'=>$rs['id']])->setField('registration_id',$registration_id);
                    }
                } else {
                    $client->device()->updateAlias($registration_id, $alias);
                    $users->where(['id'=>$rs['id']])->setField('registration_id',$registration_id);
                }
            }

            //更新云信用户信息
            $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
            $accid="s".$rs['id'];
            $name = $rs['s_name'];
            $img = C('WEBHOST').$rs['t_img'];
            $p->updateUinfo($accid, $name , $img);


            if ($count['s_phone'] == "") {
                $arr = $users->where(array('id' => $count['id']))->find();
                $token = $this->get_token($count['s_phone'], $count['openid'], $count['id'], 3);
                $users->where(['id'=>$count['id']])->save(['token'=>$token]);
                $arr['token'] = $token;
                $arr['code'] = 2;
                $this->ApiReturn(1, "需要绑定手机号", $arr);
            } else {
                if($count['status'] == 2){
                    $this->ApiReturn(-1, '账号已被冻结');
                }
                $token = $this->get_token($count['s_phone'], $count['s_password'], $count['id'], 3);
                $users->where(['id'=>$count['id']])->save(['token'=>$token]);
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
        $token = $data['token']??$this->ApiReturn(20003, 'token无效或已过期');
        $id = S($token);
        $s_type = !empty($data['s_type'])?$data['s_type']:$this->ApiReturn(-1, '用户身份类型不能为空');
        $phone = !empty($data['phone'])?$data['phone']:$this->ApiReturn(-1, '手机号不能为空');
        $password = !empty($data['password'])?$data['password']:$this->ApiReturn(-1, '密码不能为空');
        $code = !empty($data['code'])?$data['code']:$this->ApiReturn(-1, '验证码不能为空');
        checkPhoneCode($phone, $code);
        $user = M("SmallTable");
        $row = $user->where(['s_phone'=>$phone])->find();
        //手机注册后  微信登录 过来的情况
        if($row){
            if (!empty($row['openid'])){
                $arr['code'] = 302;
                $this->ApiReturn(-1, '此手机号已绑定其他微信号',$arr);
            }else{
                $res = $user->where(array('id'=>$id))->find();//微信登陆 注册过来的账号 要把这里的账号信息放到手机号注册的账号上 合并
                $map['openid'] = $res['openid'];
                $map['s_type'] = $s_type;//1.托管 2.教育机构
                if (empty($row['s_img'])){
                    $map['s_img'] = $res['s_img'];
                }
                if (empty($row['name'])){
                    $map['name'] = $res['name'];
                }
                $data1 = $user -> where(['s_phone'=>$phone]) -> save($map);
                $token = $this->get_token($data['s_phone'],$data['s_password'],$id, 3);
                $user->where(['s_phone'=>$phone])->save(['token'=>$token]);
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
            $res = $user->where(['id'=>$id])->find();
            if($res){
                $nmap['s_phone'] = $phone;
                $nmap['s_password'] = $password;
                $nmap['s_type'] = $s_type;//1.托管 2.教育机构
                $data1 = $user -> where("id ={$id}") -> save($nmap);
                $token = $this->get_token($data['s_phone'],$data['s_password'],$id, 3);
                $user -> where("id ={$id}") ->save(array('token'=>$token));
                $arr = $this->userInfo($id);
                $arr['token']=$token;
                $arr['code']=301;
                $this->ApiReturn(1, '绑定成功', $arr);
            }else{
                $this->ApiReturn(-1, '绑定失败');
            }
        }
    }

    //填写审核资料
    public function addOther()
    {
        $data = $this->data;
        $small = M("SmallTable");
        $token = !empty($data['xtoken'])?$data['xtoken']:$this->ApiReturn(20003, 'token无效或已过期');
        $id = S($token);
        $find = $small->where(array('id'=>$id))->find();
        if(!$find){
            $this->ApiReturn(-1, '账号不存在');
        }

        $logo = !empty($data['logo'])?$data['logo']:$this->ApiReturn(-1, 'logo不能为空');
        $s_name = !empty($data['s_name'])?$data['s_name']:$this->ApiReturn(-1, '公司名称不能为空');
        $c_name = !empty($data['c_name'])?$data['c_name']:$this->ApiReturn(-1, '联系人姓名不能为空');
        $address = !empty($data['address'])?$data['address']:$this->ApiReturn(-1, '公司地址不能为空');
        $xy = !empty($data['xy'])?$data['xy']:$this->ApiReturn(-1, '公司坐标不能为空');
        $area_code = !empty($data['area_code'])?$data['area_code']:$this->ApiReturn(-1, '区code不能为空');
        $protocol = !empty($data['protocol'])?$data['protocol']:$this->ApiReturn(-1, '没有同意平台服务协议');

        //根据区code，计算属于哪个代理商
        $agent_id = M('Users')->where(['areacode'=>['like', "%".$area_code."%"]])->getField('id');

        $add = $small->where(['id'=>$id])->save([
            's_img'     =>  $logo,
            'address'   =>  $address,
            's_name'    =>  $s_name,
            's_content' =>  $data['content'],
            'introduction'=>$data['introduction'],
            'c_name'    =>  $c_name,
            's_xy'      =>  $xy,
            'area_code' =>  $area_code,
            'agent_id'  =>  $agent_id,
            'business_license'=>$data['business_license'],
            'holding_card'=>$data['holding_card'],
            'card1'     =>  $data['card1'],
            'card2'     =>  $data['card2'],
            'store1'    =>  $data['store1'],
            'store2'    =>  $data['store2'],
            's_status'  =>  1
        ]);
        $map = [
            's_img'=>$logo,
            'address'=>$address,
            's_name'=>$s_name,
            's_content'=>$data['content'],
            'c_name'=>$c_name,
            'business_license'=>$data['business_license'],
            'holding_card'=>$data['holding_card'],
            'card1'=>$data['card1'],
            'card2'=>$data['card2'],
            'store1'=>$data['store1'],
            'store2'=>$data['store2'],
            's_phone'=>$find['s_phone']
        ];
        if(!$data['store2']){
            unset($map['store2']);
        }
        $examine = D("examine_table");
        foreach($map as $k=>$v){
            $examine->where(['option_key'=>$k,'uid'=>$id])->delete();
            $add = [
                'option_key'=> $k,
                'option_value'=>$v,
                'uid'       => $id
            ];
            $examine->add($add);
        }
        if(!$add){
            $this->ApiReturn(-1,"失败");
        }
        $this->ApiReturn(1,"成功");
    }

    //用户信息
    protected function userInfo($user_id)
    {
        $id=$user_id;
        $db_user= M("SmallTable");
        $info = $db_user
            ->join('LEFT JOIN fzm_level ON fzm_level.id=fzm_small_table.level_id')
            ->where('fzm_small_table.id='.$id)
            ->field('fzm_small_table.*, fzm_level.name as level_name')
            ->find();
        $arr=array();
        $arr['phone'] = $info['s_phone'];
        $arr['s_name'] = $info['s_name'];
        $arr['logo'] = $info['s_img'];
        $arr['level'] = $info['level_name'];
        $arr['level_icon'] = $info['icon'];
        $arr['s_type'] = $info['s_type'];
        $arr['status'] = $info['s_status']; //1.未审核 2.审核通过 3.审核不通过

        //星级
        $star = 0;
        $star += $info['star1'] + $info['star2'] + $info['star3'] + $info['star4'] + $info['star5'];
        $star /= 5;
        $arr['star'] = sprintf('%.1f', $star);

        //人气 浏览量
        $arr['view'] = $info['view_num'];

        //关注 收藏量
        $collection = M('Collection')->where(['type'=>2, 'item_id'=>$user_id])->count();
        $arr['collection'] = $collection;

        return $arr;
    }

    //用户信息
    public function information()
    {
        $data = $this->data;
        $id=S($data['token']);
        if (!$id) $this->ApiReturn(20003, '请登录');
        $db_user= M("SmallTable");
        $info = $db_user
            ->join('LEFT JOIN fzm_level ON fzm_level.id=fzm_small_table.level_id')
            ->where('fzm_small_table.id='.$id)
            ->field('fzm_small_table.*, fzm_level.name as level_name, fzm_level.icon')
            ->find();
        if ($info) {
            $arr=array();
            $arr['phone'] = $info['s_phone'];
            $arr['s_name'] = $info['s_name'];
            $arr['logo'] = $info['s_img'];
            $arr['level'] = $info['level_name'];
            $arr['level_id'] = $info['level_id'];
            $arr['level_icon'] = $info['icon'];
            $arr['status'] = $info['s_status']; //1.未审核 2.审核通过 3.审核不通过
            $arr['s_type'] = $info['s_type'];   //1.托管 2.教育机构

            //星级
            $star = 0;
            $star += $info['star1'] + $info['star2'] + $info['star3'] + $info['star4'] + $info['star5'];
            $star /= 5;
            $arr['star'] = sprintf('%.1f', $star);

            //人气 浏览量
            $arr['view'] = $info['view_num'];

            //关注 收藏量
            $collection = M('Collection')->where(['type'=>2, 'item_id'=>$id])->count();
            $arr['collection'] = $collection;

            //会员是否开启的功能
            $arr['svip_option'] = M('Config')->getField('svip_option');
            $this->ApiReturn(1, 'success', $arr);
        } else {
            $this->ApiReturn(20003, '请登录');
            S($data['token'],null);
        }

    }

    //找回密码
    public function resetPwd()
    {
        $data = $this->data;
        if($data['phone'] ==""){
            $this->ApiReturn(-1, '手机号不能为空');
        }
        if($data['code'] ==""){
            $this->ApiReturn(-1, '验证码不能为空');
        }
        if($data['password'] ==""){
            $this->ApiReturn(-1, '密码不能为空');
        }
        $user = M('SmallTable')->where(array('s_phone'=>$data['phone']))->find();
        if (!$user){
            $this->ApiReturn(-1, '用户不存在');
        }
        if ($user['s_password']==$data['password']){
            $this->ApiReturn(-1, '不能与原密码相同');
        }
        //验证手机号，验证码 补上
        checkPhoneCode($data['phone'], $data['code']);
        $save = M('SmallTable')->where(array('s_phone'=>$data['phone']))->save(array('s_password'=>$data['password']));
        $this->ApiReturn(1,'修改成功');
    }

}