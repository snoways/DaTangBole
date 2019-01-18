<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/8/29
 * Time: 10:56
 */

namespace Client\Controller;

require('./application/Common/common/JPushSF.php');
class CommonController extends MapiBaseController
{
//客服电话，法律条款 ，关于我们
    public function config()
    {
        $config = M('Config')->find();
        if($config){
            $this->ApiReturn(1,'成功',$config);
        }else{
            $this->ApiReturn(-1,'失败');
        }
    }

    /**
     * 上传文件
     * sunfan
     */
    public function uploadImg()
    {
        if (!empty($_FILES['img'])) {
            //上传图片
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize = 11048576;// 设置附件上传大小
            $upload->exts = array('jpg', 'png', 'jpeg', 'gif');// 设置附件上传类型
            $upload->rootPath = './data/upload/headimg/'; // 设置附件上传根目录
            $upload->savePath = ''; // 设置附件上传（子）目录
            // 上传文件
            $info = $upload->upload();
            // var_dump($info);
            if (!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            } else {// 上传成功 获取上传文件信息
                $headimg = "/data/upload/headimg/" . $info['img']['savepath'] . $info['img']['savename'];
            }
            $this->ApiReturn(1, '上传成功',['img'=>$headimg]);
        }
    }

    /**
     * 多图上传
     */
    public function uploadMultiImg()
    {
        if (!empty($_FILES)) {
            //上传图片
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize = 11048576;// 设置附件上传大小
            $upload->exts = array('jpg', 'png', 'jpeg', 'gif');// 设置附件上传类型
            $upload->rootPath = './data/upload/circle/'; // 设置附件上传根目录
            $upload->savePath = ''; // 设置附件上传（子）目录
            // 上传文件
            $info = $upload->upload();

            if(!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            }else{// 上传成功 获取上传文件信息
                foreach($info as $file){
                    $headimg[] = "/data/upload/circle/" . $file['savepath'].$file['savename'];
                }
            }
            $this->ApiReturn(1, '上传成功',['imgs'=>$headimg]);
        }
    }


    public function getImgCode()
    {
        try{
            $data = $this->data;
            $phone = $data['phone']??$this->ApiReturn(20001, "手机号不能为空");

            $str = "23456789abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVW";
            $code = '';
            $codel = '';
            $coder = '';
            for ($i = 0; $i < 4; $i++) {
                $code .= $str[mt_rand(0, strlen($str)-1)];
            }
            for ($i = 0; $i < 2; $i++) {
                $codel .= $str[mt_rand(0, strlen($str)-1)];
            }
            for ($i = 0; $i < 10; $i++) {
                $coder .= $str[mt_rand(0, strlen($str)-1)];
            }

            //验证码存到数据库
            $db_code = M('phone_code');
            $data_code['phone'] = $phone;
            $data_code['pic_code'] = $code;
            $data_code['createtime'] = time();

            $code_phone_one = $db_code->where(array('phone'=>$data['phone']))->find();
            if ($code_phone_one){
                $db_code->where('phone='.$data['phone'])->save(array('pic_code'=>$code));
            }else{
                $db_code->add($data_code);
            }

            $msg = $codel.$code.$coder;

            $this->ApiReturn(1,"成功",['code'=>$msg]);
        } catch (\Exception $e) {
            $this->ApiReturn(10002,'系统错误');
        }
    }

    public function sendMsg()
    {
        $data = $this->data;
        $phone = $data['phone']??$this->ApiReturn(-1, '手机号不能为空');
        $img_code = $data['img_code']??$this->ApiReturn(-1, '非法操作');
        checkPhoneImgCode($phone, $img_code);

        $str = "1234567890";
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $str[mt_rand(0, strlen($str)-1)];
        }

        $msg = AliDayu($phone, $code);

        M('PhoneCode')->where('phone='.$data['phone'])->save(array('code'=>$code, 'createtime'=>time()));

        $this->ApiReturn(1, '发送成功');
    }

    /**
     * 退出登录
     * sunfan
     * 2017.11.14
     */
    public function signOut()
    {
        try {
            $data=$this->data;
            $id = $data['token'];
            if(!S($id)){
                $this->ApiReturn(20003,'token无效或已过期');
            }
            S($id,null);
            $this->ApiReturn(1, '退出成功');
        } catch (\Exception $e) {
            $this->ApiReturn(-1,'系统错误');
        }
    }

    function request_post($url = '', $param = '') {
        if (empty($url) || empty($param)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $param;
        $curl = curl_init();//初始化curl
        curl_setopt($curl, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($curl, CURLOPT_HEADER, 0);//设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($curl);//运行curl
        curl_close($curl);

        return $data;
    }


    public function sendwxmsg()
    {
        $appid = "wxa81d95ef804d35be";
        $secret = "0253bc55000c71d80f3afc3b57c9e6c0";
        $token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";

        $token_res = file_get_contents($token_url);
        $token_res = json_decode($token_res,true);

        if($token_res['errcode']){
            file_put_contents('/Public/wei_log.logs',json_encode($token_res,JSON_UNESCAPED_UNICODE),FILE_APPEND);
            $this->ApiReturn(-1,'获取token失败');
        }
        $access_token=$token_res['access_token'];

//        dump($access_token);
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
        $post_data['touser']       = 'OPENID';
        $post_data['template_id']      = 'CkOJDHvWk6Bzv9afOWcfyeSwyLqQeYj-WWlPpNKqGCE';
        $post_data['topcolor'] = '#FF0000';
        $post_data['data']=[
            'name'      =>  ["value"=>"sun"],
            'content'   =>  ['value'=>"成功!"]
        ];
        dump($post_data);
        $post_data = json_encode($post_data);

        dump($url);
        dump($post_data);
        $res = $this->request_post($url, $post_data);

        dump($res);
    }

    /**
     * 安卓检查版本更新
     */
    public function getVersion()
    {
        $config = M('Config')->field('versionp, versiont,versions, pversion_note, tversion_note, sversion_note')->find();
        $this->ApiReturn(1, 'success', $config);
    }

    public function uploadVideo()
    {
        if (!empty($_FILES['video'])) {
            //上传图片
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize = 20971520;// 设置附件上传大小
            $upload->exts = array('mp4', '3gp', '3gpp','rm', 'rmvb','avi','wmv','mov');// 设置附件上传类型
            $upload->rootPath = './data/upload/Video/'; // 设置附件上传根目录
            $upload->savePath = ''; // 设置附件上传（子）目录
            // 上传文件
            $info = $upload->upload();
            if (!$info) {// 上传错误提示错误信息
                $this->ApiReturn(-1, $upload->getError());
            } else {// 上传成功 获取上传文件信息
                $headimg = "/data/upload/video/" . $info['video']['savepath'] . $info['video']['savename'];
            }
            $this->ApiReturn(1, '上传成功',['video'=>$headimg]);
        }
        $this->ApiReturn(-1, '没有文件被上传');
    }

    /**
     * 会员权限介绍
     */
    public function vipIntroduce()
    {
        $find = M('Config')->find();
        $this->ApiReturn(1, 'success', $find['vip']);
    }

    /**
     * 会员开关
     */
    public function getVipState()
    {
        $find = M('Config')->field('pvip_option, tvip_option, svip_option')->find();
        $this->ApiReturn(1, 'success', $find);
    }


    /**
     * 支付成功后的回调
     * @param $pay_type 1.支付宝 2.微信
     * @param $out_trade_no 商户订单号
     * @param $trade_no 三方支付单号
     */
//    public function paySucess($pay_type, $out_trade_no, $trade_no)
//    {
//        //判断一下是什么订单类型
//        $first = substr($out_trade_no , 0 , 1);
//        $first = strtoupper($first);
//        if ($first=="C"){
//            //教育机构订单
//            $status = 5;
//            $db = M('EducationalOrder');
//            $key = 'total_money';
//            $title = "购买机构课程";
//        }elseif ($first=="H"){
//            //托管订单
//            $status = 6;
//            $db = M('HostingOrder');
//            $key = 'total_money';
//            $title = "购买托管班";
//        }elseif ($first=="A"){
//            //线下活动订单
//            $status = 8;
//            $db = M('ActivityOrder');
//            $key = 'pay_money';
//            $title = "报名线下活动";
//        }elseif ($first=="O"){
//            //线上课堂
//            $status = 7;
//            $db = M('OnlineClassOrder');
//            $key = 'total_money';
//            $title = "购买视频课程";
//        }elseif ($first=="V"){
//            //会员
//            $status = 4;
//            $db = M('VipOrder');
//            $key = 'money';
//            $title = "充值会员";
//        }elseif ($first=="D"){
//            //预约老师订单
//            $status = 1;
//            $db = M('Order');
//            $key = 'total_money';
//            $title = "预约老师";
//        }
//
//        $where['sn']=$out_trade_no;
//        if (!M('MoneyLog')->where(array('order_sn'=>$out_trade_no, 'status'=>$status))->find()){
//            $order = $db->where($where)->find();
//            $db->where(['id'=>$order['id']])->save(array('status'=>2, 'paytype'=>1, 'trade_no'=>$trade_no));
//
//            //订单在完成的时候才要给老师钱，这里不给老师钱
//            M('MoneyLog')->add([
//                'uid'   =>  $order['parent_id'],
//                'u_type'    =>  1,
//                'money'     =>  $order[$key],
//                'status'    =>  $status,  //1家长预约老师支付  2老师提现  3家长退款 4.充值会员 5.教育机构订单支付 6.托管课程支付 7.线上课堂支付 8.线下活动支付
//                'paytype'   =>  $pay_type,  //1支付宝 2微信
//                'state'     =>  1,  //1 收入  2支出
//                'm_time'    => date('Y-m-d H:i:s'),
//                'msg'       =>  $title.$order['sn'],
//                'order_sn'  =>  $order['sn'],
//            ]);
//
//            if ($first=="D"){
//                //支付完成后才把老师的空闲时间去掉
//                M('TeacherTime')->where(array('id'=>$order['teacher_time_id']))->save(array('status'=>2));
//                M('Teacher')->where(['id'=>$order['teacher_id']])->setInc('sale_num');
//            }elseif ($first=="V"){
//                //支付完，给会员升等级
//                M('Parents')->where(['id'=>$order['uid']])->save(['level_id'=>$order['vip_level_id']]);
//            }elseif ($first=="O"){
//                //支付完，增加视频的销量
//                M('OnlineClass')->where(['id'=>$order['oc_id']])->setInc('sale_num');
//                if ($order['u_type']==1){
//                    //给老师钱
//                    $this->addMoney($order['tid'],  $order['money']);
//                    M('Teacher')->where(['id'=>$order['tid']])->setInc('sale_num');
//
//                    //推送
//                    $alias = 'teacher'.$order['tid'];
//                    $receive = array('alias'=>[$alias]);//别名
//                    $push = new \JPushSF(C('JPush.TAPPID'),C('JPush.TAPPSECRET'));
//                    $push->push($receive, 0, 4, $order['id']);
//                }elseif ($order['u_type']==2){
//                    //给商户钱
//                    $this->addMoney($order['tid'], 2, $order['money']);
//
//                    //推送
//                    $alias = 'smalltable'.$order['tid'];
//                    $receive = array('alias'=>[$alias]);//别名
//                    $push = new \JPushSF(C('JPush.SAPPID'),C('JPush.SAPPSECRET'));
//                    $push->push($receive, 0, 4, $order['id']);
//                }
//
//            }elseif ($first=="A"){
//                //线下活动订单
//                M('Activity')->where(['id'=>$order['activity_id']])->setInc('now_num', $order['num']);
//                $activity = M('Activity')
//                    ->where(['id'=>$order['activity_id']])
//                    ->find();
//                if ($activity['shop_id']!=-1){
//                    //活动结束前不入商家账目，活动结束后自动进入余额
//                    $add_preview = D("ActivityPreview")->add([
//                        'activity_id'   =>   $order['activity_id'],
//                        'ao_id'         =>   $order['id'],
//                        'shop_id'       =>   $activity['shop_id'],
//                        'money'         =>   $order['money']
//                    ]);
//
//                    //推送
//                    $alias = 'smalltable'.$activity['shop_id'];
//                    $receive = array('alias'=>[$alias]);//别名
//                    $push = new \JPushSF(C('JPush.SAPPID'),C('JPush.SAPPSECRET'));
//                    $push->push($receive, 0, 5, $order['id']);
//                }
//            }elseif ($first=="C"){
//                //教育机构订单
//                //增加购买人数
//                M('EducationalCourse')->where(['id'=>$order['ec_id']])->setInc('buy_num');
//                //给机构钱
//                $this->addMoney($order['st_id'], 2, $order['money']);
//
//                //推送
//                $alias = 'smalltable'.$order['st_id'];
//                $receive = array('alias'=>[$alias]);//别名
//                $push = new \JPushSF(C('JPush.SAPPID'),C('JPush.SAPPSECRET'));
//                $push->push($receive, 0, 6, $order['id']);
//            }elseif ($first=="H"){
//                //托管订单
//                //增加购买人数
//                M('HostingCourse')->where(['id'=>$order['hc_id']])->setInc('buy_num');
//                //给机构钱
//                $this->addMoney($order['st_id'], 2, $order['money']);
//
//                //推送
//                $alias = 'smalltable'.$order['st_id'];
//                $receive = array('alias'=>[$alias]);//别名
//                $push = new \JPushSF(C('JPush.SAPPID'),C('JPush.SAPPSECRET'));
//                $push->push($receive, 0, 7, $order['id']);
//            }
//        }
//    }

    /**
     * 获取分享的标题和图片
     * user：jizhongbao
     * time：2018/11/23
     */
    public function getShare()
    {
        $data      = $this->data;
        $object_id = $data['object_id']?$data['object_id']:$this->ApiReturn(-1, '缺少参数：object_id');
        $s_type    = in_array($data['s_type'],[1,2])?$data['s_type']:$this->ApiReturn(-1, '缺少参数：s_type');
        $where = [
            'object_id' => $object_id,
            's_type'    => $s_type
        ];
        $code = 1;
        $msg = 'success';
        $info = M('share')->where($where)->field(' title , image_path,description')->find();
        if (!$info) {
            if ($s_type == 1) {
                $mode = M('online_class');
                $info = $mode->where(['id'=>$object_id])->field(' title , img_url as image_path')->find();
            } else {
                $mode = M('activity');
                $info = $mode->where(['id'=>$object_id])->field(' title , img as image_path')->find();
            }
            if (!$info) {
                $code = -1;
                $msg = 'error';
            }
        }
//        $info['image_path'] = 'http://'.$_SERVER['HTTP_HOST'].'/'.$info['image_path'];
        $this->ApiReturn($code, $msg, $info);
    }

    /**
     * 用户和商家协议
     */
    public function protocol()
    {
        $data = $this->data;
        if (in_array($data['protocol'],[1,2])) {
            $Fields = [1=>'registered',2=>'business_agreement'];
            $protocol = M('Config')->getField($Fields[$data['protocol']]);
            $this->ApiReturn(1, 'success', ['protocol'=>'http://'.$_SERVER['HTTP_HOST'].'/Client/Common/protocol_html?protocol='.$Fields[$data['protocol']]]);
        }
    }
    /**
     * 用户和商家协议的html
     */
    public function protocol_html()
    {
        $data = $this->data;
        $Fields = ['registered','business_agreement'];
        if (in_array($data['protocol'],$Fields)) {
            $protocol = M('Config')->getField($data['protocol']);
           echo $protocol; die;
        }
    }


    //上架时 isSixSixSix返回0
    public function m24_()
    {
        $this->ApiReturn(1, '', ['isSixSixSix'=>0,'iskls'=>"wgnakfad_whnew",'sfqw_'=>'dguhnrignr_gh_ajaqw']);
//        $this->ApiReturn(1, '', ['isSixSixSix'=>1,'iskls'=>0,'sfqw_'=>1]);
    }
}