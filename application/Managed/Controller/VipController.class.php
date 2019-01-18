<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 上午 10:04
 */

namespace Managed\Controller;



class VipController extends ManagedBaseController
{

    public function _initialize(){
        parent::_initialize();
        //引入WxPayPubHelper
        vendor('WxPayPubHelper_s.WxPayPubHelper');

    }

    /**
     * 会员权益
     */

    public function index()
    {
        $find = M('Config')->find();
        $this->assign("find",$find);
        $this->display();
    }

    public function update()
    {
        $db = M('Level');
        $list = $db->where(['id'=>['neq', 1]])->order('money')->select();
        $this->assign('list',$list);
        $this->display();
    }

    public function pay()
    {
        $data = $_REQUEST;
        $db = M('Level');
        $level = $db->where(['id'=>$data['type']])->find();
        $sn = 'V'.sp_get_order_sn();
        $id = M('VipOrder')->add([
            'sn'    =>  $sn,
            'uid'   =>  $_SESSION['small_id'],
            'u_type'=>  3,
            'vip_level_id'=>$data['type'],
            'o_time'=>  date('Y-m-d H:i:s'),
            'money' =>  $level['money'],
            'time_length' =>  $level['time_length']
        ]);
        if ($id){
            $order_info = M('VipOrder')->where(array('id'=>$id))->find();
            $money = round($order_info['money'], 2);

            //商品描述
            $title = "订单编号:";
            $title .= $sn;

            //支付宝
            $this->aliPay($title, $sn, $money);
        }
    }

    public function aliPay($title, $out_trade_no, $money)
    {
        $money=0.01;//------------TODO------临时修改了价格--------
        require_once('./public/ali/aop/AopClient.php');
        require_once('./public/ali/aop/request/AlipayTradePagePayRequest.php');

        //构造参数
        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = "2018041102536222";
        $aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAwZXRdKdWHOEr12lG7QWWjWpKImFjecsJcRhz4cHr5S5dpkzCOLEF3BtIKRNYntJYaPIEyJWgJ9N0Oe5Vzv/cbxAGL+IBDd73ZJw5Ke1tizTMJIbN4aUVFQynXfwA5/Oea1p9N684MyBugslVdrpp9mxRJvMrAD62A29JJqQqo30BDIRamaLK/YpROZUy5vkMPNFKQysPqbyJDv1Pk47D6pqGMgIk09yG314Lllpy26qOQ1asAbhLRCaFx3oRk8JgW2BATt/SqN+yOp1J9Ntbeyh6rx3f3G1daGZnkxjD0GC2JSnO6N1w0PvCbV3KMsTG4w51L4dynHoFaI2lKafwsQIDAQABAoIBAE8kWdpQIyNxZwQM9UMnerJb1u0RVaLQQA7tiUqthixO9VhsnyagMJ4YxTqNvzhHFH/rGcuLaEt/5k9cwdX7wnnhWjYvNnEeS5PlTnV2rMcxkZgJSJhMbj8Jyk7hHNm3PnfzKagfSWGVi7iKaRc+BN4K0G6VEWprOOxUjdpvWUM2vBXXKtae8OMuAKukr/vEyQdp0SLwESt2SRNdJVIHpwwkt0fwgpXCO37/U3JuArIwtMHuogjKkeXUKyq+XxZpTXf1qRrVCml0S0aFj1grkARIU0cm/v0aKdiasOIQX1oliwvLcd6vFokyPM7k75zg9qvKId1Lax99mPDj1n9VSgECgYEA8dr6OsuM5WrwbQnKZ/X/FAGewYl14fDPgVoltH1t/fKaQwDLn4kAm1vUhwGv7MYIwChOUrTFr1VMoxvubMk9h+6FOpCSFuhkzf2JN4Ctyn9SCRS0gryICk/iJpUyXHE+nmUyE8rsB7ntc30ABtneaOK2gfay9eQfv5G538fsVRECgYEAzOglsBy5HY19izxJTQ2nBHMpNBKkKRynY9sKOGJk6EqTRvDkeoziT0XJYKNd0TE9y9NSEs+t6d3+9TUF/mNOEbtk7OCTOQAKe5F3px5W0mgPldSFVrPvdnDiGvnRVOW438tLgB/zLRCDWCEbfwN5nCXPPodAqXJbePsjKFkbYaECgYBRNNpyJWhwm6CQrAnnMETufcDFcRdAvu+dmhww5zCoZO4A82Jrdb/balEI57sfQDst8hqiUIpT3cs2tSkwI73iR2c6i9JRmMRIGgoZtb4k0O1FmUsm3pC7Dal8lPns6iVBX+8ZkDgCPB6LeXwp0LuJ8h2fs6rRP0CdvRtxFRq4UQKBgQCczFB6saAeMzWMpHdbFUVnLFCtXk5sf1bAHM93UiPxdY+5y4CrHr/W9Yoh/yE9gTbOkEjPyEhHG++L6CVMAuWsv/99HGTMS3G6GRi8s4SwwZybhOL78/kcY0lCZ0R+eMO9zS1bQBevtmErwTnvOdOHX491Q76Ba9b/fv3qVDWVwQKBgQCtTYZ//mI/KOZB+MftlUXnmihqDMjMwdmnCIZUp0nRC4SJRMiaFndDmbyfFLjaez+cz+VtnL7EgrqvsusvM2lsKpAhQ7WE9G0N8XtxD73RIrVvChk+R7A4hz9lxMYMd5I3Q916zyI3jT+yF3LCVg/dtOwya8jq+yQg3Zyu/vkjAw==' ;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'utf-8';
        $aop->format = 'json';
        $request = new \AlipayTradePagePayRequest ();
        $url = $this->url . "/Managed/Index/index";
        $notify_url = C('WEBHOST') . '/Managed/Vip/ali_notify_url';
        //支付后返回的页面
        $request->setReturnUrl($url);
        //回调地址
        $request->setNotifyUrl($notify_url);

        $bizcontent = "{\"body\":\"$title\","
            . "\"subject\": \"$title\","
            . "\"out_trade_no\": \"$out_trade_no\","
            . "\"total_amount\": \"$money\","
            . "\"product_code\":\"FAST_INSTANT_TRADE_PAY\""
            . "}";
        $request->setBizContent($bizcontent);

        //请求
        $result = $aop->pageExecute($request);

        //输出
        echo $result;
    }


    //微信支付
    public function wxpay()
    {
        $data = $_REQUEST;
        $db = M('Level');
        $level = $db->where(['id'=>$data['type']])->find();
        $sn = 'v'.sp_get_order_sn();
        $id = M('VipOrder')->add([
            'sn'    =>  $sn,
            'uid'   =>  $_SESSION['small_id'],
            'u_type'=>  3,
            'vip_level_id'=>$data['type'],
            'o_time'=>  date('Y-m-d H:i:s'),
            'money' =>  $level['money'],
            'time_length' =>  $level['time_length']
        ]);
        if ($id){
            $order_info = M('VipOrder')->where(array('id'=>$id))->find();
            $money = round($order_info['money'], 2);

            $out_trade_no = $sn;
            //商品描述
            $title = "订单编号:";
            $title .= $sn;
//使用统一支付接口
            $unifiedOrder = new \UnifiedOrder_pub();

            //设置统一支付接口参数
            //设置必填参数
            //appid已填,商户无需重复填写
            //mch_id已填,商户无需重复填写
            //noncestr已填,商户无需重复填写
            //spbill_create_ip已填,商户无需重复填写

            //sign已填,商户无需重复填写
            $unifiedOrder->setParameter("body","$title");//商品描述
            $money = (int)($money*100);
            $unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号
            $unifiedOrder->setParameter("total_fee","$money");//总金额
            $unifiedOrder->setParameter("notify_url", C('WEBHOST') . '/Managed/Vip/wxNotify');//通知地址
            $unifiedOrder->setParameter("trade_type","NATIVE");//交易类型
            //非必填参数，商户可根据实际情况选填

            //获取统一支付接口结果
            $unifiedOrderResult = $unifiedOrder->getResult();

            //商户根据实际情况设置相应的处理流程
            if ($unifiedOrderResult["return_code"] == "FAIL")
            {
                //商户自行增加处理流程
                echo "通信出错：".$unifiedOrderResult['return_msg']."<br>";
            }
            elseif($unifiedOrderResult["result_code"] == "FAIL")
            {
                //商户自行增加处理流程
                echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
                echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
            }
            elseif($unifiedOrderResult["code_url"] != NULL)
            {
                //从统一支付接口获取到code_url
                $code_url = $unifiedOrderResult["code_url"];
                //商户自行增加处理流程
                //......
            }
            $this->assign('out_trade_no',$out_trade_no);
            $this->assign('code_url',$code_url);
            $this->assign('unifiedOrderResult',$unifiedOrderResult);

            $this->display('qrcode');
        }

    }


}