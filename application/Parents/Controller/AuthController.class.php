<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/10/31
 * Time: 13:33
 */

namespace Parents\Controller;

use Client\Controller\MapiBaseController;
use JPush\Client;
require('./application/Common/common/JPushSF.php');
require('./application/Common/common/ServerAPI.php');

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

    //极光的
    public function updateJpushID()
    {
        /*$appKey = C('JPush.PAPPID');
        $masterSecret = C('JPush.PAPPSECRET');
        $client = new Client($appKey, $masterSecret);

        $list = M('Teacher')->select();
        foreach ($list as $item){
            $alias = 'parent'.$item['id'];
            $client->device()->updateAlias("13165ffa4e25cfc9547", $alias);
        }

        $appKey = C('JPush.TAPPID');
        $masterSecret = C('JPush.TAPPSECRET');
        $client = new Client($appKey, $masterSecret);

        $list = M('Parents')->select();
        foreach ($list as $item){
            $alias = 'teacher'.$item['id'];
            $client->device()->updateAlias("13165ffa4e25cfc9547", $alias);
        }*/
        $alias = 'parent30';
        //给老师推送
        $push = new \JPushSF(C('JPush.PAPPID'),C('JPush.PAPPSECRET'));
        $receive = array('alias'=>[$alias]);//别名
        $result = $push->push($receive,0,"哈哈");
        echo $result;
    }

    //网易云信im
    public function getUinfos()
    {
        $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
        $accid=I('id');
        dump($p->getUinfos([$accid]));
    }

    public function yxuser()
    {
        //注册网易云账号
//        $user = M('Parents')->select();
        $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
        /*foreach ($user as $item)
        {
            $accid="p".$item['id'];
            $name = $item['parent_name'];
            $img = C('WEBHOST').$item['p_img'];
//            $result = $p->createUserId($accid, $item['phone']);
            $p->updateUinfo($accid, $name , $img);
        }
        $user = M('Teacher')->select();
        foreach ($user as $item)
        {
            $accid="t".$item['id'];
            $name = $item['name'];
            $img = C('WEBHOST').$item['t_img'];
//            $result = $p->createUserId($accid, $item['phone']);
            $p->updateUinfo($accid, $name , $img);
        }*/
        $user = M('SmallTable')->page(I('page'), 100)->select();
        foreach ($user as $item)
        {
            //更新云信用户信息
            $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
            $accid="s".$item['id'];
            $name = $item['s_name'];
            $img = C('WEBHOST').$item['s_img'];
//            $result = $p->createUserId($accid, $name, 0 , $img);
            $p->updateUinfo($accid, $name , $img);
        }
    }

    /**
     * 家长登录
     * sunfan
     * 2017.10.31
     */
    public function login()
    {
        $data = $this->data;
        $phone = $data['phone']??$this->ApiReturn(-1, '手机号不能为空');
        $password = $data['password']??$this->ApiReturn(-1, '密码不能为空');
        $rs = M('Parents')->where(['phone'=>$phone])->find();
        if (empty($rs)){
            $this->ApiReturn(-1, '用户不存在');
        }

        if ($rs['status']==2){
            $this->ApiReturn(-1, '用户被冻结');
        }

        if ($rs['password']!=$password){
            $this->ApiReturn(-1, '密码不正确');
        }

        $rs['p_img'] = $rs['p_img']??"/public/images/headicon.png";
        $rs['token'] = $this->get_token($rs['phone'],$rs['password'],$rs['id'],1);
        M('Parents')->where(['phone'=>$phone])->save(['token'=>$rs['token']]);

        $rs['u_type'] = 0;//普通用户
        if ($rs['st_id'] != 0) {
            $rs['u_type'] = 1;//直属代理商
        }
        //更新极光推送用户id
        if ($data['registration_id']) {
            $appKey = C('JPush.PAPPID');
            $masterSecret = C('JPush.PAPPSECRET');
            $client = new Client($appKey, $masterSecret);
            $alias = 'parent'.$rs['id'];
            if ($rs['registration_id'] != '') {
                if ($rs['registration_id'] != $data['registration_id']) {
                    $client->device()->updateAlias($data['registration_id'], $alias);
                    M('Parents')->where(['id'=>$rs['id']])->setField('registration_id',$data['registration_id']);
                }
            } else {
                $client->device()->updateAlias($data['registration_id'], $alias);
                M('Parents')->where(['id'=>$rs['id']])->setField('registration_id',$data['registration_id']);
            }
        }
        $rs['level_name'] = M('Level')->where(['id'=>$rs['level_id']])->getField('name');
        $rs['level_icon'] = M('Level')->where(['id'=>$rs['level_id']])->getField('icon');

        //更新用户姓名及头像
        $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
        $accid="p".$rs['id'];
        $name = $rs['parent_name'];
        $img = C('WEBHOST').$rs['p_img'];
        $p->updateUinfo($accid, $name , $img);

        $message = "登录成功";
        if(empty($rs)){
            $code = 0;
        }else{
            $code = 1;
        }
        $rs['code']=0;
        $this->ApiReturn($code,$message,$rs);
    }

    /**
     * 注册
     * sunfan
     * 2017.11.1
     */
    public function reg()
    {
        $data = $this->data;
        $phone = $data['phone']??$this->ApiReturn(-1, '手机号不能为空');
        $code = $data['code']??$this->ApiReturn(-1, '短信验证码不能为空');
        $pw = $data['password']??$this->ApiReturn(-1, '密码不能为空');
        $model = M('Parents');
        if ($model->where(['phone'=>$phone])->find())
        {
            $this->ApiReturn(-1, '该手机号已注册');
        }

        checkPhoneCode($phone, $code);
        if (!$model->where(['phone'=>$data['parent_id']])->find()) {
            $data['parent_id'] = 0;
        }
        $id = $model->add([
            'phone'     =>  $phone,
            'password'  =>  $pw,
            'p_time'    =>  date('Y-m-d H:i:s'),
            'p_img'     =>  '/public/images/headicon.png',
            'child_name'=>  "学生".substr($phone, -4),
            'parent_name'=>  "家长".substr($phone, -4),
            'child_school'=> "暂无",
            'child_grade'=> "暂无",
            'relationship'=> "父亲",
            'parent_id' => $data['parent_id']
        ]);
        if (!$id)
        {
            $this->ApiReturn(-1, '服务器开小差了，请重新注册');
        }

        //注册云信账号
        $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
        $result = $p->createUserId($id, $phone);

        //送优惠券
        $this->giftCoupons($id);

        //给邀请人赠送优惠券
        if (!empty($data['invitation_code'])){
            $icid = M('Parents')->where(['code'=>$data['invitation_code']])->getField('id');
            $this->giftCoupons($icid);
        }

        //是否是员工推荐注册的
        if (!empty($data['empnum'])){
            $e_num = $data['empnum'];
            $einfo = M('Employee')->where(['e_num'=>$e_num])->find();
            if ($einfo['e_status']==1){
                M('Parents')->where(['phone'=>$phone])->save(['empnum'=>$e_num]);
                M('Employee')->where(['e_num'=>$e_num])->setInc('count1');
                M('Employee')->where(['e_num'=>$e_num])->setInc('count2');
            }
        }

        //生成邀请码
        $code = getInvitationCode($id);

        $rs = M('Parents')->where(['id'=>$id])->find();
        $rs['p_img'] = $rs['p_img']??"/public/images/headicon.png";
        $rs['token'] = $this->get_token($rs['phone'],$rs['password'],$rs['id'], 1);
        $rs['code']=0;
        M('Parents')->where(['phone'=>$phone])->save(['token'=>$rs['token'], 'code'=>$code]);
        $message = "注册成功";
        if(empty($rs)){
            $code = 0;
        }else{
            $code = 1;
        }

        $this->ApiReturn($code,$message,$rs);
    }

    /**
     * 微信登录
     * sunfan
     * 2017.11.1
     */
    /*public function wxlogin()
    {
        $data = $this->data;
        $code = $data['code'];    //从客户端传过来的
        $state = $data['state'];    //从客户端传过来的

        $appid = "wx5d9729b68dcd8f43";
        $secret = "af87c4d879a2e9745f4044f76666aec9";
        $token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code";

        $token_res = file_get_contents($token_url);
        $token_res = json_decode($token_res,true);

        if($token_res['errcode']){
            file_put_contents('/Public/wei_log.logs',json_encode($token_res,JSON_UNESCAPED_UNICODE),FILE_APPEND);
            $this->ApiReturn(-1,'获取token失败');
        }
        $access_token=$token_res['access_token'];
        $openid=$token_res['openid'];
        file_put_contents('/Public/wei.logs',json_encode($token_res,JSON_UNESCAPED_UNICODE));
        $user_url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}";

        $user_res = file_get_contents($user_url);
        $user_res = json_decode($user_res,true);

        if($user_res['errcode']){
            file_put_contents('/Public/wei_log.logs',json_encode($user_res,JSON_UNESCAPED_UNICODE),FILE_APPEND);
            $this->ApiReturn(-1, '获取用户信息失败');
        }

        $userinfo = M('Parents')->where(['openid'=>$openid])->find();
        if (!$userinfo)
        {
            $data1['openid'] = $openid;
            $data1['parent_name'] = $user_res['nickname'];
            $data1['p_img'] = $user_res['headimgurl'];
            $data1['p_time'] = date('Y-m-d H:i:s');
            M('Parents')->add($data1);
            $status=2;      //2需要绑定手机号
        }else{
            if (empty($userinfo['p_img'])){
                $data2['p_img'] = $user_res['headimgurl'];
                M('Parents')->where(['openid'=>$openid])->save($data2);
            }
            $status=1;      //1已经有账号
        }

        $wres = M('Parents')->where(['openid'=>$openid])->find();
        $wres['token'] = $this->get_token($openid,$wres['password'],$wres['id'], 1);
        $wres['type'] = $status;
        M('Parents')->where(['openid'=>$openid])->save(['token'=>$wres['token']]);
        $this->ApiReturn(1,"登录成功",$wres);
    }*/

    public function wxLogin()
    {
        $user_res = $this->data;
        $openid = $user_res['openid']??$this->ApiReturn(-1, 'openid不能为空');
        if (!empty($user_res['headimgurl']))
        {
            $headimg = downloadImage($user_res['headimgurl']);
        }else{
            $headimg = "/public/images/headicon.png";
        }

        $nickname = $this->emoji_filter($user_res['nickname']);
//        print_r($nickname);die;
        $users = M('Parents');
        $count = $users->where(array('openid'=>$openid))->find();
        if(!$count){
            $data1 = [
                'p_img'=> $headimg,
                'parent_name'=> $nickname,
                'child_name'=> "学生".substr($openid, -4),
                'openid'=> $openid,
                'p_time'=> date('Y-m-d H:i:s'),
                'child_school'=> "暂无",
                'child_grade'=> "暂无",
                'relationship'=> "父亲",
            ];
            $id = $users->add($data1);

            //送优惠券
            $this->giftCoupons($id);

            //生成邀请码
            $code = getInvitationCode($id);

            $arr = M('Parents')->where(['id'=>$id])->find();
            $arr['level_name'] = M('Level')->where(['id'=>$arr['level_id']])->getField('name');
            $arr['level_icon'] = M('Level')->where(['id'=>$arr['level_id']])->getField('icon');

            $token = $this->get_token($openid,$data1['p_time'],$id, 1);
            M('Parents')->where(['id'=>$id])->save(['token'=>$token, 'code'=>$code]);
            $arr['token']=$token;
            $arr['code']=2;
            $this->ApiReturn(1,"请绑定手机号",$arr);
        }else{
            /*$map=[];
            if (!empty($headimg)){
                $map['p_img'] = $headimg;
            }
            if (!empty($nickname)){
                $map['parent_name'] = $nickname;
            }
            M('Parents')->where(['id'=>$count['id']])->save($map);*/

            $arr = M('Parents')->where(['id'=>$count['id']])->find();

            $arr['level_name'] = M('Level')->where(['id'=>$arr['level_id']])->getField('name');
            $arr['level_icon'] = M('Level')->where(['id'=>$arr['level_id']])->getField('icon');

            if ($arr['status']==2){
                $this->ApiReturn(-1, '用户被冻结');
            }

            if (empty($count['phone'])){
                $token = $this->get_token($count['phone'],$openid,$count['id'], 1);
                M('Parents')->where(['id'=>$count['id']])->save(['token'=>$token]);
                $arr['token']=$token;
                $arr['code']=2;
                $this->ApiReturn(1,"需要绑定手机号",$arr);
            }else{

                //更新极光推送用户id
                if ($user_res['registration_id']) {
                    $appKey = C('JPush.PAPPID');
                    $masterSecret = C('JPush.PAPPSECRET');
                    $client = new Client($appKey, $masterSecret);
                    $alias = 'parent'.$count['id'];
                    if ($count['registration_id'] != '') {
                        if ($count['registration_id'] != $user_res['registration_id']) {
                            $client->device()->updateAlias($user_res['registration_id'], $alias);
                            M('Parents')->where(['id'=>$count['id']])->setField('registration_id',$user_res['registration_id']);
                        }
                    } else {
                        $client->device()->updateAlias($user_res['registration_id'], $alias);
                        M('Parents')->where(['id'=>$count['id']])->setField('registration_id',$user_res['registration_id']);
                    }
                }

                //更新用户姓名及头像
                $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
                $accid="p".$count['id'];
                $name = $count['parent_name'];
                $img = C('WEBHOST').$count['p_img'];
                $p->updateUinfo($accid, $name , $img);

                $token = $this->get_token($count['phone'],$count['password'],$count['id'], 1);
                M('Parents')->where(['id'=>$count['id']])->save(['token'=>$token]);
                $arr['token']=$token;
                $arr['code']=1;
                $this->ApiReturn(1,"登陆成功",$arr);
            }
        }
    }
    protected function emoji_filter($str) {
        if($str){
            $name = $str;
            $name = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '', $name);
            $name = preg_replace('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S','?', $name);
            $return = json_decode(preg_replace("#(\\\ud[0-9a-f]{3})#ie","",json_encode($name)));

        }else{
            $return = '';
        }
        return $return;

    }
    /**
     * 绑定手机号
     * sunfan
     * 2017.12.4
     */
    public function bindPhone()
    {
        $data = $this->data;
        $token = $data['token']??$this->ApiReturn(-1, 'token无效或已过期');
        $id = S($token);
        $phone = $data['phone']??$this->ApiReturn(-1, '手机号不能为空');
        $password = $data['password']??$this->ApiReturn(-1, '密码不能为空');
        $code = $data['code']??$this->ApiReturn(-1, '验证码不能为空');
        checkPhoneCode($phone,$code);

        $user = M("Parents");
        $row = $user->where(['phone'=>$phone])->find();//手机号登陆注册过来的账号
        if($row){
            if (!empty($row['openid'])){
                $rr['code'] = 302;
                $this->ApiReturn(1, '此手机号已绑定其他微信号', $rr);
            }else{
                $res = $user->where("id='{$id}'")->find();//微信登陆注册过来的账号 要把这里的账号信息放到手机号注册的账号上 合并
                $map['openid'] = $res['openid'];
                if (empty($row['p_img'])){
                    $map['p_img'] = $res['p_img'];
                }
                if (empty($row['parent_name'])){
                    $map['parent_name'] = $res['parent_name'];
                }
                $map['password'] = $password;

                $data1 = $user -> where(['phone'=>$phone]) -> save($map);
                $arr = $user -> where(['phone'=>$phone]) -> find();

                $token = $this->get_token($data['phone'],$data['password'],$arr['id'], 1);
                M('Parents')->where(['phone'=>$phone])->save(['token'=>$token]);
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
                $nmap['phone'] = $phone;
                $nmap['password'] = $password;
                $data1 = $user -> where("id ={$id}") -> save($nmap);

                $arr = $user -> where(['phone'=>$phone]) -> find();
                $token = $this->get_token($data['phone'],$data['password'],$id, 1);
                $arr['token']=$token;
                $arr['code']=301;

                $this->ApiReturn(1, '绑定成功', $arr);
            }else{
                $this->ApiReturn(-1, '绑定失败');
            }
        }
    }


    /**
     * 找回密码
     * sunfan
     * 2017.11.17
     */

    public function findPwd()
    {
        try{
            $data = $this->data;
            $phone = $data['phone']??$this->ApiReturn(-1, '手机号不能为空');
            $code = $data['code']??$this->ApiReturn(-1, '验证码不能为空');
            $pw = $data['password']??$this->ApiReturn(-1, '新密码不能为空');
            $userinfo = M('Parents')->where(['phone'=>$phone])->find();
            if (!$userinfo){
                $this->ApiReturn(-1, '用户不存在');
            }
            checkPhoneCode($phone, $code);
            try{
                M('Parents')->where(['phone'=>$phone])->save(['password'=>$pw]);
            }catch (\Exception $e){
                $this->ApiReturn(-1,'系统错误');
            }
            $this->ApiReturn(1, 'success');
        }catch (\Exception $e){
            $this->ApiReturn(-1,'系统错误');
        }
    }

    public function img()
    {
        // 加载水印以及要加水印的图像
        $stamp = imagecreatefrompng('./public/images/stamp.png');
        $im = imagecreatefromjpeg('./data/upload/admin/20171229/5a45be375d3dc.png');

// 设置水印图像的外边距，并且获取水印图像的尺寸
        $marge_right = 30;
        $marge_bottom = 30;
        $sx = imagesx($stamp);
        $sy = imagesy($stamp);


// 利用图像的宽度和水印的外边距计算位置，并且将水印复制到图像上

        imagecopy($im, $stamp, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));

        // 输出图像并释放内存
        header('Content-type: image/png');
        imagepng($im);
        imagedestroy($im);
    }

    public function send_pub()
    {
        $push = new \JPushSF(C('JPush.PAPPID'),C('JPush.PAPPSECRET'));
        $receive = array('alias'=>['parent1']);//别名
        $result = $push->push($receive,1);
    }

}