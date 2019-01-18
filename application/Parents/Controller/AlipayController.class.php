<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/7/31
 * Time: 17:05
 */

namespace Parents\Controller;


use Client\Controller\CommonController;
use Client\Controller\MapiBaseController;
use Think\Controller;
require('./application/Common/common/JPushSF.php');

class AlipayController extends MapiBaseController
{
    private $url='';
    public function _initialize(){
//        $this->url=C('WEBHOST').'/index.php';
    }

    public function PayOrder()
    {
        $data = $_REQUEST;
        $out_trade_no = $data['order_sn']??$this->ApiReturn(-1, '订单号不能为空');

        //1.老师订单 2.教育机构订单 3.托管订单 4.线下活动订单 5.线上课堂订单 6.会员购买订单
        $type = $data['type']??$this->ApiReturn(-1, '订单类型不能为空');

        if ($type==1){
            $db = M('Order');
            $key = 'total_money';
        }elseif($type==2){
            $db = M('EducationalOrder');
            $key = 'total_money';
        }elseif($type==3){
            $db = M('HostingOrder');
            $key = 'total_money';
        }elseif($type==4){
            $db = M('ActivityOrder');
            $key = 'pay_money';
        }elseif($type==5){
            $db = M('OnlineClassOrder');
            $key = 'total_money';
        }elseif($type==6){
            $db = M('VipOrder');
            $key = 'money';
        }else{
            $this->ApiReturn(-1, '订单异常');
        }
        $order_info = $db->where(array('sn'=>$out_trade_no))->find();

        if (!$order_info){
            $this->ApiReturn(-1, '订单异常');
        }

//        if($type==4){
//            $activity = M('Activity')->where(['id'=>$order_info['activity_id']])->find();
//            if ($activity['now_num']>=$activity['people_num']){
//                $db->where(array('sn'=>$out_trade_no))->delete();
//                $this->ApiReturn(-1, '该活动人数已满，不能报名');
//            }
//        }


        $money = round($order_info[$key], 2);
//        $money = 0.01;//-----------------TODO::改价格--------------

        //商品描述
        $title = "订单编号:";
        $title .= $out_trade_no;

        //支付宝
        $this->aliPay($out_trade_no, $money, $title);
    }


    public function aliPay($out_trade_no, $money, $title)
    {
        require_once('./public/ali/aop/AopClient.php');
        require_once('./public/ali/aop/request/AlipayTradeAppPayRequest.php');
        $aop = new \AopClient;
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = "2017111309905854";
        $aop->rsaPrivateKey = 'MIIEowIBAAKCAQEAt1zaP2u+l7+1hY/PNj2FVIz9UDaDmbuv1PqHWhsH0JuAsZp2ArI2Q2kgNtRg/T+onvafd9sIaIbOv5ZIZ4c9eJtMqd/d2l3FAuRwfsxgJ7WXElYT2RNwcW7an/ljYuh5RmY1YL/R47UVbXPjS6z1rNAJ3apDnOUeE83yqtwfHiNsy79/+AItUg248IrIMmAcgcHgqoV0rKUnqPa9k7U9HeBHjymxhbMcrRIMupD+Iz7FxqWZUzyZwCdZ+O4nRXS1ExFU671pjtdqpPAUxWhhX/ZUw2eaWxA5x9FXRdK0x209Zqy8BmfEAQFg3c9014nsPJvAcBjjkhm3+wEgGzovcwIDAQABAoIBAF+0CP2dIOdXWkkR3Fc1GQgeZoCdU2bD/WwuLsMq5JNO9oa8VefmWEgq8HNtugm0VjBSDL2kBul3oyWALN5MShtPA16Ox96XnqJ51PV5ep5/OxCI+OcOLFwoPdfNdMBFEjdaVXXf6I7vDvYHzJTM+5VtKBkYKx4Sv/YaQhRHu74gYLdSMSUuYzCaiR8Pn3kdG/aiGrmZDHasHmLYutqwyH2Bh8IWPoJIPm+2cQoQeERUXxDf85lv5B3eeDZr1zvyaOK0Txfglq3e6w+ZJ2HMJY9b33c8+ZTIfqDhEYXwawNpcSz56xjTFD+gbU34+NqhlYkw3xqb+InQEdtcBYweNxECgYEA5YzArGIpLZMm2fC4sedAMRJO4FT/bnWBOA+Y75D6P7FNohLQhV6mQtf4eU0YqedwIECKMP8PNzQY0jbesxLNuOXB1NZgok6+uVS/hbp+ajWwvgao8cPmBLlFhhusvYavyT/qMU9MZj8Lee06XHi7lXzhP579+QEXZLdnGynloRUCgYEAzH2s17D5MVPljlL2i6Nbg8SaOP2eDDRpbNC10dEg9YD5aH5S+KDXHCQ+rR/GLQLZQ3VR9nxbBrB/8Xin/yniNS6p6MyTI2+IkozDnQQIOIy03ufCh3fC21AE4FW0vV9U3fFrl2cV/+b07BX4geI5E0v2QPd13nlLhZsuXJhU4GcCgYAPlw6i5ovLZ5oU5S92DbGjY43t2Hf8pYhgKVcGtj74wm72WfbFiBccpRRgEKdjKq/H0PpRt5Dt++DPriBT4ywLqbcPYHvxqg10Ath7GZ5qUjktvsAMo3Rkz7x0Dj8eJB6eOXQLY0paC2AZKM5051I+JdwaeQ7gsX1IPtiG1MKevQKBgQCgOWByaDH4WnolOBABfW+5IRSNzvpFKdPo9OdhjPC8K+A/5arxMGUbobKSR7Epl2/QkV41OV7BMQ4uj5FiNtkNPpDW3WP0gfGKkky6/GvMk0Ms3H7pUgcVe+82LzE8qDOA6yaYLKdqKPaC/PwIGM8LtZsvcDWkqXlpKBv9ZUYrxQKBgC5bV+rltzHsS1UXQAZAF3rwtbcoaUBHESKJlr+XHhUxYvbiui/w3Zj0KTSV8AhDAdb4S+H1OkxCMiYc4IcjUI8bWPqrCROhh2qc8e3xcrF7yAevZNcvCyr5xOkAO/zC5kfYdW7+w5I4Tb3V9UHU+5OPmBAQIhs5ifsDlp0RvpUS' ;
        $aop->format = "json";
//        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhhMOH57Y6vu0jM04mU7+Fk+v5VtHbv1t8KuMWOWGwoCZPsm0IeEUoIjTX/Iwoszb2818GmPjwhacW/rwzq1ME+5sC85FJYzOCO52J97uEIt1VEoVkmnbwHuZJ4yCwyYJPJ9tulFmL15jjYin6p8pxf3eAIO8DmZ6Wib4oQzU3DfcdKqSJE8tIqepxH8EvyVPxi25N8D80tV75SgR2iOH33ZT7GKyv8IS2GSgqTD/+DkiLsq+QY7XB7O5GExquZRMsNN95mlsOanFXjc74T60uu2sPZ0NiY1jMWCGGU1Eo3rCaeC3iS2FA73MhQ9f4TUJJRllThuEGD8pOJ3cNTdPNwIDAQAB';
//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new \AlipayTradeAppPayRequest();

        $subject = $title;
        $order_sn = $out_trade_no;
        $total_amount = $money;

        $bizcontent = "{\"body\":\"$subject\","
            . "\"subject\": \"$subject\","
            . "\"out_trade_no\": \"$order_sn\","
            . "\"timeout_express\": \"30m\","
            . "\"total_amount\": \"$total_amount\","
            . "\"product_code\":\"QUICK_MSECURITY_PAY\""
            . "}";

//        $request->setNotifyUrl(C('WEBHOST') ."/Parents/Alipay/notifyurl");
        $request->setNotifyUrl("http://".$_SERVER['HTTP_HOST']."/Parents/Alipay/notifyurl");
        $request->setBizContent($bizcontent);
//这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
        $return = htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
        $return = str_replace('amp;', '', $return);
        $this->ApiReturn(1, '成功', $return);
    }

    public function notifyurl()
    {
        require_once('./public/ali/aop/AopClient.php');
        $aop = new \AopClient;
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhhMOH57Y6vu0jM04mU7+Fk+v5VtHbv1t8KuMWOWGwoCZPsm0IeEUoIjTX/Iwoszb2818GmPjwhacW/rwzq1ME+5sC85FJYzOCO52J97uEIt1VEoVkmnbwHuZJ4yCwyYJPJ9tulFmL15jjYin6p8pxf3eAIO8DmZ6Wib4oQzU3DfcdKqSJE8tIqepxH8EvyVPxi25N8D80tV75SgR2iOH33ZT7GKyv8IS2GSgqTD/+DkiLsq+QY7XB7O5GExquZRMsNN95mlsOanFXjc74T60uu2sPZ0NiY1jMWCGGU1Eo3rCaeC3iS2FA73MhQ9f4TUJJRllThuEGD8pOJ3cNTdPNwIDAQAB';
        $flag = $aop->rsaCheckV1($_POST, null, "RSA2");

        if ($flag)
        {
            echo "success";
            //订单状态
            $trade_status = $_POST['trade_status'];

            //商户订单号
            $out_trade_no = $_POST['out_trade_no'];

            $trade_no = $_POST['trade_no'];

            if ($trade_status=="TRADE_SUCCESS")             //支付成功
            {
                $pay_type=1;
                //判断一下是什么订单类型
                $first = substr($out_trade_no , 0 , 1);
                $first = strtoupper($first);
                if ($first=="C"){
                    //教育机构订单
                    $status = 5;
                    $db = M('EducationalOrder');
                    $key = 'total_money';
                    $title = "购买机构课程";
                }elseif ($first=="H"){
                    //托管订单
                    $status = 6;
                    $db = M('HostingOrder');
                    $key = 'total_money';
                    $title = "购买托管班";
                }elseif ($first=="A"){
                    //线下活动订单
                    $status = 8;
                    $db = M('ActivityOrder');
                    $key = 'pay_money';
                    $title = "报名线下活动";
                }elseif ($first=="O"){
                    //线上课堂
                    $status = 7;
                    $db = M('OnlineClassOrder');
                    $key = 'total_money';
                    $title = "购买视频课程";
                }

                $where['sn']=$out_trade_no;
                if (!M('MoneyLog')->where(array('order_sn'=>$out_trade_no, 'status'=>$status))->find()){
                    $order = $db->where($where)->find();
                    $db->where(['id'=>$order['id']])->save(array('status'=>2, 'paytype'=>1, 'trade_no'=>$trade_no));

                    //订单在完成，新增收入记录
                    M('MoneyLog')->add([
                        'uid'            =>  $order['parent_id'],
                        'u_type'         =>  1,
                        'money'          =>  $order[$key], //支付金额
                        'platform_money' =>  $order['platform_money'], //平台所得金额
                        'agent_money'    =>  $order['money'], //代理商所得金额
                        'status'         =>  $status,  // 5.课程订单支付 6.托管课程支付 7.线上课堂支付 8.线下活动支付
                        'paytype'        =>  $pay_type,  //1支付宝 2微信
                        'state'          =>  1,  //1 收入  2支出
                        'm_time'         => date('Y-m-d H:i:s'),
                        'msg'            =>  $title.$order['sn'],
                        'order_sn'       =>  $order['sn'],
                    ]);

                    if ($first=="O"){
                        //支付完，增加视频的销量
                        M('OnlineClass')->where(['id'=>$order['oc_id']])->setInc('sale_num');
                        if ($order['u_type']==2){
                            //给商户钱
                            $this->addMoney($order['tid'],$first,$order['id'], $order['money']);
                            //推送
                            $alias = 'smalltable'.$order['tid'];
                            $receive = array('alias'=>[$alias]);//别名
                            $push = new \JPushSF(C('JPush.SAPPID'),C('JPush.SAPPSECRET'));
                            $push->push($receive, 4, $order['oc_id']);
                        }
                        //如果是平台订单
                        if ($order['u_type'] == 3 && $order['money'] > 0) {
                            //订单在完成，新增收入记录
                            M('direct_agent_ratio')->add([
                                'parent_id'      =>  $order['parent_id'],
                                'order_id'       =>  $order['id'],
                                'money'          =>  $order['money'], //分佣金额
                                'order_type'     =>  1,  //1.线上课堂支付 2.线下活动支付
                                'create_time'    => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }elseif ($first=="A"){
                        //线下活动订单
                        M('Activity')->where(['id'=>$order['activity_id']])->setInc('now_num', $order['num']);
                        $activity = M('Activity')
                            ->where(['id'=>$order['activity_id']])
                            ->find();
                        if ($activity['shop_id'] > 0){
                            //给商户钱
                            $this->addMoney($order['st_id'],$first, $order['id'], $order['money']);
                            //推送
                            $alias = 'smalltable'.$activity['shop_id'];
                            $receive = array('alias'=>[$alias]);//别名
                            $push = new \JPushSF(C('JPush.SAPPID'),C('JPush.SAPPSECRET'));
                            $push->push($receive, 5, $order['id']);
                        }
                        //如果是平台订单
                        if ($activity['shop_id'] == 0 && $order['money'] > 0) {
                            //订单在完成，新增收入记录
                            M('direct_agent_ratio')->add([
                                'parent_id'      =>  $order['parent_id'],
                                'order_id'       =>  $order['id'],
                                'money'          =>  $order['money'], //分佣金额
                                'order_type'     =>  1,  //1.线上课堂支付 2.线下活动支付
                                'create_time'    => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }elseif ($first=="C"){
                        //课程订单
                        //增加购买人数
                        M('EducationalCourse')->where(['id'=>$order['ec_id']])->setInc('buy_num');
                        //给机构钱
                        $this->addMoney($order['st_id'],$first,$order['id'],  $order['money']);

                        //推送
                        $alias = 'smalltable'.$order['st_id'];
                        $receive = array('alias'=>[$alias]);//别名
                        $push = new \JPushSF(C('JPush.SAPPID'),C('JPush.SAPPSECRET'));
                        $push->push($receive, 6, $order['id']);
                    }elseif ($first=="H"){
                        //托管订单
                        //增加购买人数
                        M('HostingCourse')->where(['id'=>$order['hc_id']])->setInc('buy_num');
                        //给机构钱
                        $this->addMoney($order['st_id'],$first,$order['id'],  $order['money']);

                        //推送
                        $alias = 'smalltable'.$order['st_id'];
                        $receive = array('alias'=>[$alias]);//别名
                        $push = new \JPushSF(C('JPush.SAPPID'),C('JPush.SAPPSECRET'));
                        $push->push($receive, 7, $order['id']);
                    }
                }
            }
        }
    }
}