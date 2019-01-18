<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/3/9
 * Time: 11:28
 */

namespace Parents\Controller;


use Think\Controller;

class TestController extends Controller
{
    private $url='';
    private $appid='';
    private $mchid='';
    private $key='';
    public function _initialize(){
        $this->url='fzm.tjtomato.com';
        $this->appid='wx5d9729b68dcd8f43';
        $this->mchid='1493252882';
        $this->key='da256tan43g2bo3le91anYuanyUanabc';
    }

    /**
     * 初始化
     */
    /*public function _initialize()
    {
        //引入WxPayPubHelper
        vendor('WxPayPubHelper.WxPayPubHelper');
    }*/

    public function PayOrder()
    {
        $data = $_REQUEST;
        $out_trade_no = $data['order_sn']??$this->ApiReturn(-1, '订单id不能为空');

        $order_info = M('Order')->where(array('order_sn'=>$out_trade_no))->find();
        if (!$order_info){
            $this->ApiReturn(-1, '订单不存在');
        }
        $money = round($order_info['money'], 2);

        //商品描述
        $title = "订单编号:";
        $title .= $order_info['order_sn'];

        //支付宝
        $this->appCall($order_info['order_sn'], $money, $title);
    }

    public function appCall($out_trade_no, $money, $title)
    {
        $money = (int)($money*100);

        $arr['appid']=$this->appid;
        $arr['body']=$title;
//        $arr['device_info']='app';
        $arr['mch_id']=$this->mchid;
        $arr['nonce_str']=md5(mt_rand(1,99999).'yj');
        $arr['notify_url']="http://".$this->url."/Parents/WxAppPay/weNotify";
        $arr['out_trade_no']=$out_trade_no;
        $arr['spbill_create_ip']=$this->get_client_ip();
//        $arr['total_fee']=1;
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

        /*$returnsign=$returnarr['sign'];
        unset($returnarr['sign']);
        if($this->weixin_get_sign($returnarr)!=$returnsign){
            $this->ApiReturn('100032','微信签名错误');
        }*/
        $return['prepayid']=$returnarr['prepay_id'];
        $return['appid']=$this->appid;
        $return['partnerid']=$this->mchid;
        $return['package']='Sign=WXPay';
        $return['noncestr']=md5(rand(1,99999).'fq');
        $return['timestamp']=(string)time();
        $return['sign']=$this->weixin_get_sign($return);
        $this->ApiReturn(1,'success',$return);
    }

    public function weNotify()
    {
        $xml = file_get_contents('php://input');
        $arr=$this->xml2array($xml)['xml'];
        $sign=$arr['sign'];
        unset($arr['sign']);
        if($sign==$this->weixin_get_sign($arr)){
            if($arr['result_code']=='SUCCESS'){

                $out_trade_no=$arr['out_trade_no'];
                if (!M('MoneyLog')->where(array('order_sn'=>$out_trade_no))->find())
                {

                    $order = M('Order')->where(array('order_sn'=>$out_trade_no))->find();
                    if (M('Assess')->where(['oid'=>$order['id'], 'pid'=>$order['passenger_id']])->find())
                    {
                        M('Order')->where(array('order_sn'=>$out_trade_no))->save(array('o_status'=>6, 'paytype'=>2));
                    }else{
                        M('Order')->where(array('order_sn'=>$out_trade_no))->save(array('o_status'=>5, 'paytype'=>2));
                    }

                    $order = M('Order')->where(array('order_sn'=>$out_trade_no))->find();
                    $money = $order['total_money']-$order['platform_money'];
                    M('Driver')->where(['id'=>$order['driver_id']])->setInc('wallet', $money);
//                    M('Driver')->where(['id'=>$order['driver_id']])->setDec('wallet', $order['coupon_money']);

                    M('MoneyLog')->add([
                        'user_id'   =>  $order['driver_id'],
                        'u_type'    =>  2,
                        'money'     =>  $money,
                        'state'     =>  1,
                        'ml_type'   =>  2,
                        'create_time'=> date('Y-m-d H:i:s'),
                        'msg'       =>  '订单'.$order['order_sn'].'的里程费',
                        'order_sn'  =>  $order['order_sn']
                    ]);
                }
            }else{
                $this->wxreturn('0','返回失败');
                die;
            }
        }else{
            $this->wxreturn('0','签名错误');
            die;
        }
    }

    private function Log($msg)
    {
        $file = fopen("Public/notify_url.log","w");
        fwrite($file,date("Y-m-d H:i:s")."  ");
        fwrite($file,$msg."/n");
        fclose($file);
    }

    //数组转XML
    private function ToXml($arr)
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


    // 通知地址
    public function notify()
    {
        //使用通用通知接口
        $notify = new \Notify_pub();

        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);

        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if($notify->checkSign() == FALSE){
            $notify->setReturnParameter("return_code","FAIL");//返回状态码
            $notify->setReturnParameter("return_msg","签名失败");//返回信息
        }else{
            $notify->setReturnParameter("return_code","SUCCESS");//设置返回码
        }
        $returnXml = $notify->returnXml();
        echo $returnXml;

        //==商户根据实际情况设置相应的处理流程，此处仅作举例=======

        //以log文件形式记录回调信息
//         $log_ = new Log_();
        $log_name= __ROOT__."/Public/notify_url.log";//log文件路径

        log_result($log_name,"【接收到的notify通知】:\n".$xml."\n");

        if($notify->checkSign() == TRUE)
        {
            if ($notify->data["return_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                log_result($log_name,"【通信出错】:\n".$xml."\n");
            }
            elseif($notify->data["result_code"] == "FAIL"){
                //此处应该更新一下订单状态，商户自行增删操作
                log_result($log_name,"【业务出错】:\n".$xml."\n");
            }
            else{
                //此处应该更新一下订单状态，商户自行增删操作
                log_result($log_name,"【支付成功】:\n".$xml."\n");
            }

            //商户自行增加处理流程,
            //例如：更新订单状态
            //例如：数据库操作
            //例如：推送支付完成信息
        }
    }

    protected function ApiReturn($code,$msg,$data=null) {
        $arr['code']=$code;
        $arr['msg']=$msg;
        if(empty($data)&&!is_array($data)){
            $data=array();
        }
        //$data = str_replace(null,"",$data);
        $arr['data']=$data;
        $arr['time']=time();
        $a = $this->data;
        // echo json_encode($arr);
        // die();
        echo str_replace('\/',"/",json_encode($arr));

//        $this->writeLog("发出:".json_encode($arr));
        die();
    }



}