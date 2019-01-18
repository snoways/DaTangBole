<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/17 0017
 * Time: 上午 11:56
 */

namespace ManagedClient\Controller;


use Client\Controller\MapiBaseController;
use Client\Controller\UserApiBaseController;

class VipController extends MapiBaseController
{
    private $appid='';
    private $mchid='';
    private $key='';

    public function _initialize(){
        parent::_initialize();
        $this->appid='wxa0f0c82f6d41a711';
        $this->mchid='1502452281';
        $this->key='Da256tan43g2bo3le91anYuanyUanabc';
    }


    //会员管理

    public function index()
    {
        $find = M('Config')->find();
        $this->ApiReturn(1, 'success', $find['vip']);
    }

    //会员列表
    public function MemberList()
    {
        $db = M('Level');
        $list = $db->where(['id'=>['neq', 1]])->order('money')->field('id as lid, money, time_length, name as title, icon')->select();
        if (!$list){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $list);
        }
    }

    public function pay()
    {
        $data = $this->data;
        $id = S($data['token']);
        if (!$id){
            $this->ApiReturn(20003, '请登录');
        }
        $db = M('Level');

        $pay_type = !empty($data['pay_type'])?$data['pay_type']:$this->ApiReturn(-1, '请选择支付方式');
        $lid = !empty($data['lid'])?$data['lid']:$this->ApiReturn(-1, '请选择会员等级');
        $level = $db->where(['id'=>$lid])->find();

        $user = M('SmallTable')->where(['id'=>$id])->find();
        $olevel = $db->where(['id'=>$user['level_id']])->find();
        if ($level['money']<=$olevel['money']){
            $this->ApiReturn(-1, '请选择更高等级的会员');
        }

        //如果有相同的会员等级，未支付的订单，用那个，否则生成一个新的
        $old_order = M('VipOrder')->where([
            'uid'   =>  $id,
            'u_type'=>  3,
            'vip_level_id'=>$lid,
            'status'=>  1,  //1 未支付 2已支付
        ])->find();
        if ($old_order){
//            $id = $old_order['id'];
            M('VipOrder')->delete($old_order['id']);
        }

        $sn = 'V'.sp_get_order_sn();
        $id = M('VipOrder')->add([
            'sn'    =>  $sn,
            'uid'   =>  $id,
            'u_type'=>  3,
            'vip_level_id'=>$lid,
            'o_time'=>  date('Y-m-d H:i:s'),
            'money' =>  $level['money'],
            'time_length' =>  $level['time_length']
        ]);

        if ($id){
            $order_info = M('VipOrder')->where(array('id'=>$id))->find();
            $money = round($order_info['money'], 2);

            $sn = $order_info['sn'];
            //商品描述
            $title = "订单编号:";
            $title .= $sn;

            if ($pay_type==1){
                //支付宝
                $this->aliPay($sn, $money, $title);
            }else{
                //微信
                $this->wxPay($sn, $money, $title);
            }
        }
    }

    public function aliPay($out_trade_no, $money, $title)
    {
        require_once('./public/ali/aop/AopClient.php');
        require_once('./public/ali/aop/request/AlipayTradeAppPayRequest.php');
        $aop = new \AopClient;
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = "2018041102536222";
        $aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAwZXRdKdWHOEr12lG7QWWjWpKImFjecsJcRhz4cHr5S5dpkzCOLEF3BtIKRNYntJYaPIEyJWgJ9N0Oe5Vzv/cbxAGL+IBDd73ZJw5Ke1tizTMJIbN4aUVFQynXfwA5/Oea1p9N684MyBugslVdrpp9mxRJvMrAD62A29JJqQqo30BDIRamaLK/YpROZUy5vkMPNFKQysPqbyJDv1Pk47D6pqGMgIk09yG314Lllpy26qOQ1asAbhLRCaFx3oRk8JgW2BATt/SqN+yOp1J9Ntbeyh6rx3f3G1daGZnkxjD0GC2JSnO6N1w0PvCbV3KMsTG4w51L4dynHoFaI2lKafwsQIDAQABAoIBAE8kWdpQIyNxZwQM9UMnerJb1u0RVaLQQA7tiUqthixO9VhsnyagMJ4YxTqNvzhHFH/rGcuLaEt/5k9cwdX7wnnhWjYvNnEeS5PlTnV2rMcxkZgJSJhMbj8Jyk7hHNm3PnfzKagfSWGVi7iKaRc+BN4K0G6VEWprOOxUjdpvWUM2vBXXKtae8OMuAKukr/vEyQdp0SLwESt2SRNdJVIHpwwkt0fwgpXCO37/U3JuArIwtMHuogjKkeXUKyq+XxZpTXf1qRrVCml0S0aFj1grkARIU0cm/v0aKdiasOIQX1oliwvLcd6vFokyPM7k75zg9qvKId1Lax99mPDj1n9VSgECgYEA8dr6OsuM5WrwbQnKZ/X/FAGewYl14fDPgVoltH1t/fKaQwDLn4kAm1vUhwGv7MYIwChOUrTFr1VMoxvubMk9h+6FOpCSFuhkzf2JN4Ctyn9SCRS0gryICk/iJpUyXHE+nmUyE8rsB7ntc30ABtneaOK2gfay9eQfv5G538fsVRECgYEAzOglsBy5HY19izxJTQ2nBHMpNBKkKRynY9sKOGJk6EqTRvDkeoziT0XJYKNd0TE9y9NSEs+t6d3+9TUF/mNOEbtk7OCTOQAKe5F3px5W0mgPldSFVrPvdnDiGvnRVOW438tLgB/zLRCDWCEbfwN5nCXPPodAqXJbePsjKFkbYaECgYBRNNpyJWhwm6CQrAnnMETufcDFcRdAvu+dmhww5zCoZO4A82Jrdb/balEI57sfQDst8hqiUIpT3cs2tSkwI73iR2c6i9JRmMRIGgoZtb4k0O1FmUsm3pC7Dal8lPns6iVBX+8ZkDgCPB6LeXwp0LuJ8h2fs6rRP0CdvRtxFRq4UQKBgQCczFB6saAeMzWMpHdbFUVnLFCtXk5sf1bAHM93UiPxdY+5y4CrHr/W9Yoh/yE9gTbOkEjPyEhHG++L6CVMAuWsv/99HGTMS3G6GRi8s4SwwZybhOL78/kcY0lCZ0R+eMO9zS1bQBevtmErwTnvOdOHX491Q76Ba9b/fv3qVDWVwQKBgQCtTYZ//mI/KOZB+MftlUXnmihqDMjMwdmnCIZUp0nRC4SJRMiaFndDmbyfFLjaez+cz+VtnL7EgrqvsusvM2lsKpAhQ7WE9G0N8XtxD73RIrVvChk+R7A4hz9lxMYMd5I3Q916zyI3jT+yF3LCVg/dtOwya8jq+yQg3Zyu/vkjAw==' ;
        $aop->format = "json";
//        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhhMOH57Y6vu0jM04mU7+Fk+v5VtHbv1t8KuMWOWGwoCZPsm0IeEUoIjTX/Iwoszb2818GmPjwhacW/rwzq1ME+5sC85FJYzOCO52J97uEIt1VEoVkmnbwHuZJ4yCwyYJPJ9tulFmL15jjYin6p8pxf3eAIO8DmZ6Wib4oQzU3DfcdKqSJE8tIqepxH8EvyVPxi25N8D80tV75SgR2iOH33ZT7GKyv8IS2GSgqTD/+DkiLsq+QY7XB7O5GExquZRMsNN95mlsOanFXjc74T60uu2sPZ0NiY1jMWCGGU1Eo3rCaeC3iS2FA73MhQ9f4TUJJRllThuEGD8pOJ3cNTdPNwIDAQAB';
//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new \AlipayTradeAppPayRequest();

        $subject = $title;
        $order_sn = $out_trade_no;
        $total_amount = $money;   //----------TODO------价格要改-----------
//        $total_amount = 0.01;

        $bizcontent = "{\"body\":\"$subject\","
            . "\"subject\": \"$subject\","
            . "\"out_trade_no\": \"$order_sn\","
            . "\"timeout_express\": \"30m\","
            . "\"total_amount\": \"$total_amount\","
            . "\"product_code\":\"QUICK_MSECURITY_PAY\""
            . "}";

        $request->setNotifyUrl(C('WEBHOST')."/ManagedClient/Vip/ali_notify_url");
        $request->setBizContent($bizcontent);
//这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
        $return = htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
        $return = str_replace('amp;', '', $return);
        $this->ApiReturn(1, '成功', $return);
    }

    //调起微信支付
    public function wxPay($out_trade_no, $money, $title){
        $money = $money*100;
//        $money = 1;     //--------------------TODO::改钱--------------
        $arr['appid'] = $this->appid;
        $arr['body']=$title;
        $arr['mch_id']=$this->mchid;
        $arr['nonce_str']=md5(mt_rand(1,99999).'fzm');
        $arr['notify_url']=C('WEBHOST') . '/ManagedClient/Vip/wxNotify';
        $arr['out_trade_no']=$out_trade_no;
        $arr['spbill_create_ip']=$this->get_client_ip();
        $arr['total_fee']=$money;
        $arr['trade_type']='APP';
        $arr['sign']=$this->weixin_get_sign($arr);
        $xml=$this->ToXml($arr);
        //发送URL
        $url='https://api.mch.weixin.qq.com/pay/unifiedorder';
        try{
            $rear = $this->xml2array($this->postXmlCurl($xml,$url));
            $returnarr= $rear['xml'];
        }catch (\Exception $e){
            $this->ApiReturn('10003','微信接口访问错误');
        }

        if ($returnarr['return_code']=="FAIL")
        {
            $this->ApiReturn(-1, $returnarr['return_msg'] );
        }
        $return['prepayid']=$returnarr['prepay_id'];
        $return['appid']=$this->appid;
        $return['partnerid']=$this->mchid;
        $return['package']='Sign=WXPay';
        $return['noncestr']=md5(rand(1,99999).'zm');
        $return['timestamp']=(string)time();
        $return['sign']=$this->weixin_get_sign($return);
        $this->ApiReturn(1,'success',$return);
    }


    //会员充值回调
    public function ali_notify_url()
    {
        require_once('./public/ali/aop/AopClient.php');
        $aop = new \AopClient;
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhhMOH57Y6vu0jM04mU7+Fk+v5VtHbv1t8KuMWOWGwoCZPsm0IeEUoIjTX/Iwoszb2818GmPjwhacW/rwzq1ME+5sC85FJYzOCO52J97uEIt1VEoVkmnbwHuZJ4yCwyYJPJ9tulFmL15jjYin6p8pxf3eAIO8DmZ6Wib4oQzU3DfcdKqSJE8tIqepxH8EvyVPxi25N8D80tV75SgR2iOH33ZT7GKyv8IS2GSgqTD/+DkiLsq+QY7XB7O5GExquZRMsNN95mlsOanFXjc74T60uu2sPZ0NiY1jMWCGGU1Eo3rCaeC3iS2FA73MhQ9f4TUJJRllThuEGD8pOJ3cNTdPNwIDAQAB';
        $flag = $aop->rsaCheckV1($_POST, null, "RSA2");

        if ($flag)
        {
            //订单状态
            $trade_status = $_POST['trade_status'];

            //商户订单号
            $out_trade_no = $_POST['out_trade_no'];

            $trade_no = $_POST['trade_no'];

            if ($trade_status=="TRADE_SUCCESS")             //支付成功
            {
                //会员
                $status = 4;
                $db = M('VipOrder');
                $key = 'money';
                $title = "充值会员";

                $where['sn']=$out_trade_no;
                if (!M('MoneyLog')->where(array('order_sn'=>$out_trade_no, 'status'=>$status))->find())
                {
                    $order = $db->where($where)->find();
                    $db->where($where)->save(array('status'=>2, 'paytype'=>1, 'trade_no'=>$trade_no));

                    //订单在完成的时候才要给老师钱，这里不给老师钱

                    M('MoneyLog')->add([
                        'uid'       =>  $order['uid'],
                        'u_type'    =>  3,
                        'money'     =>  $order[$key],
                        'status'    =>  $status,  //1家长预约老师支付  2老师提现  3家长退款 4.充值会员 5.教育机构订单支付 6.托管课程支付 7.线上课堂支付 8.线下活动支付
                        'paytype'   =>  1,  //1支付宝 2微信
                        'state'     =>  1,  //1 收入  2支出
                        'm_time'    => date('Y-m-d H:i:s'),
                        'msg'       =>  $title.$order['sn'],
                        'order_sn'  =>  $order['sn'],
                    ]);

                    //支付完，给会员升等级
                    M('SmallTable')->where(['id'=>$order['uid']])->save(['level_id'=>$order['vip_level_id']]);
                }
            }
        }
    }


    //回调
    public function wxNotify()
    {
        $xml = file_get_contents('php://input');
        $arr = xml2array($xml)['xml'];
        $sign = $arr['sign'];
        unset($arr['sign']);

        $trade_no = $arr['transaction_id'];
        $out_trade_no=$arr['out_trade_no'];

        if ($sign == $this->weixin_get_sign($arr)) {
            if ($arr['result_code'] == 'SUCCESS') {

                $mes = "商户购买会员订单：".$out_trade_no;
                $myfile = file_put_contents("log.txt", date('Y-m-d H:i:s')." ".$mes."\n", FILE_APPEND);
                fclose($myfile);

                //会员
                $status = 4;
                $db = M('VipOrder');
                $key = 'money';
                $title = "充值会员";

                $where['sn'] = $out_trade_no;
                if (!M('MoneyLog')->where(array('order_sn' => $out_trade_no, 'status'=>$status))->find()) {
                    $order = $db->where($where)->find();
                    $db->where($where)->save(array('status' => 2, 'paytype' => 2, 'trade_no' => $trade_no));

                    //订单在完成的时候才要给老师钱，这里不给老师钱

                    M('MoneyLog')->add([
                        'uid'   =>  $order['parent_id'],
                        'u_type'    =>  3,
                        'money'     =>  $order[$key],
                        'status'    =>  $status,  //1家长支付  2老师提现  3家长退款 4.充值会员
                        'paytype'   =>  2,  //1支付宝 2微信
                        'state'     =>  1,  //1 收入  2支出
                        'm_time'    => date('Y-m-d H:i:s'),
                        'msg'       =>  $title.$order['sn'],
                        'order_sn'  =>  $order['sn'],
                    ]);

                    //支付完，给会员升等级
                    M('SmallTable')->where(['id'=>$order['uid']])->save(['level_id'=>$order['vip_level_id']]);

                } else {
                    $this->wxreturn('0', '返回失败');
                    die;
                }
            } else {
                $this->wxreturn('0', '签名错误');
                die;
            }
        }
    }

    /**
     * 	作用：array转xml
     */
    private function ToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val))
            {
                $xml.="<".$key.">".$val."</".$key.">";

            }
            else
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
        $xml.="</xml>";
        return $xml;
    }
//微信签名算法
    private function weixin_get_sign($arr){
        ksort($arr);
        $string = $this->ToUrlParams($arr);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$this->key;//TODO::替换成客户提供的商户KEY
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    private function ToUrlParams($obj){
        $buff = "";
        foreach ($obj as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    private static function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if($useCert == true){
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);//严格校验
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
            curl_setopt($ch,CURLOPT_SSLCERT, getcwd().WxPayConfig::SSLCERT_PATH);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'pem');
            curl_setopt($ch,CURLOPT_SSLKEY, getcwd().WxPayConfig::SSLKEY_PATH);

        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl

        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new WxPayException("curl出错，错误码:$error");
        }
    }

    function get_client_ip() {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
            foreach ($matches[0] AS $xip) {
                if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                    $ip = $xip;
                    break;
                }
            }
        }
        return $ip;
    }

//xml转数组
    function xml2array($contents, $get_attributes=1, $priority = 'tag') {
        if(!$contents) return array();
        if(!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!";
            return array();
        }
        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);
        if(!$xml_values) return;//Hmm...
        //Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();
        $current = &$xml_array; //Refference
        //Go through the tags.
        $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
        foreach($xml_values as $data) {
            unset($attributes,$value);//Remove existing values, or there will be trouble
            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data);//We could use the array by itself, but this cooler.
            $result = array();
            $attributes_data = array();

            if(isset($value)) {
                if($priority == 'tag') $result = $value;
                else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
            }
            //Set the attributes too.
            if(isset($attributes) and $get_attributes) {
                foreach($attributes as $attr => $val) {
                    if($priority == 'tag') $attributes_data[$attr] = $val;
                    else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }
            //See tag status and do the needed.
            if($type == "open") {//The starting of the tag '<tag>'
                $parent[$level-1] = &$current;
                if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    $current = &$current[$tag];
                } else { //There was another element with the same tag name
                    if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                        $repeated_tag_index[$tag.'_'.$level]++;
                    } else {//This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag.'_'.$level] = 2;

                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
                    $current = &$current[$tag][$last_item_index];
                }
            } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if(!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;
                } else { //If taken, put all things inside a list(array)
                    if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...
                        // ...push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;

                        if($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag.'_'.$level]++;
                    } else { //If it is not an array...
                        $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag.'_'.$level] = 1;
                        if($priority == 'tag' and $get_attributes) {
                            if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well

                                $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                                unset($current[$tag.'_attr']);
                            }

                            if($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                    }
                }
            } elseif($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level-1];
            }
        }

        return($xml_array);
    }
}