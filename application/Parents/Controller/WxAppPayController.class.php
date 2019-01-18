<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/11/29
 * Time: 13:51
 */

namespace Parents\Controller;

use Client\Controller\CommonController;
use Client\Controller\MapiBaseController;
use Think\Controller;
require('./application/Common/common/JPushSF.php');

class WxAppPayController extends MapiBaseController
{
    private $appid='';
    private $mchid='';
    private $key='';
    public function _initialize(){
        $this->appid='wx5d9729b68dcd8f43';
        $this->mchid='1493252882';
        $this->key='da256tan43g2bo3le91anYuanyUanabc';
    }

    public function PayOrder()
    {
        $data = $_REQUEST;
        $out_trade_no = $data['order_sn']??$this->ApiReturn(-1, '订单id不能为空');

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

        $money = round($order_info[$key], 2)*100;
//        $money=1;//--------------------TODO::改价格----------------

        //商品描述
        $title = "订单编号:";
        $title .= $out_trade_no;

        $this->Wxorder($out_trade_no, $money, $title);
    }

    //调起微信支付
    public function Wxorder($out_trade_no, $money, $title){
        $arr['appid'] = $this->appid;
        $arr['body']=$title;
        $arr['mch_id']=$this->mchid;
        $arr['nonce_str']=md5(mt_rand(1,99999).'zm');
        $arr['notify_url']=C('WEBHOST')."/Parents/WxAppPay/notifyurl";
        $arr['notify_url']= "http://".$_SERVER['HTTP_HOST']."/Parents/WxAppPay/notifyurl";
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
    //回调
    public function notifyurl()
    {
        $xml = file_get_contents('php://input');
        $arr = xml2array($xml)['xml'];
        $sign = $arr['sign'];
        unset($arr['sign']);

        $trade_no = $arr['transaction_id'];
        $out_trade_no=$arr['out_trade_no'];

        if ($sign == $this->weixin_get_sign($arr)) {
            if ($arr['result_code'] == 'SUCCESS') {
                $pay_type=2;
                //判断一下是什么订单类型
                $first = substr($out_trade_no , 0 , 1);
                $first = strtoupper($first);
                if ($first=="C"){
                    //教育机构订单
                    $status = 5;
                    $db = M('EducationalOrder');
                    $key = 'total_money';
                    $title = "购买课程";
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
                        if ($order['u_type'] == 2) {
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
                        if ($activity['shop_id'] > 0) {
                            //给商户钱
                            $this->addMoney($order['st_id'],$first,$order['id'],  $order['money']);
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
                        $this->addMoney($order['st_id'],$first,$order['id'], $order['money']);

                        //推送
                        $alias = 'smalltable'.$order['st_id'];
                        $receive = array('alias'=>[$alias]);//别名
                        $push = new \JPushSF(C('JPush.SAPPID'),C('JPush.SAPPSECRET'));
                        $push->push($receive, 7, $order['id']);
                    }
                }
            } else {
                $this->wxreturn('0', '签名错误');
                die;
            }
        }
    }

    //返回错误
    private function wxreturn($code,$msg){
        if($code==1){
            $arr['return_code']="SUCCESS";
        }else{
            $arr['return_code']="FAIL";
        }
        $arr['return_msg']=$msg;
        echo $this->ToXml($arr);
        die;
    }

    //数组转XML
    public function ToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    //用户实际ip
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