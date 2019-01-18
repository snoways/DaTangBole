<?php
/**
* 	配置账号信息
*/

class WxPayConf_pub
{
	//=======【基本信息设置】=====================================
	//微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
    const APPID = 'wx5d9729b68dcd8f43';
	//受理商ID，身份标识
	const MCHID = '1493252882';
	//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	const KEY = 'da256tan43g2bo3le91anYuanyUanabc';
	//JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
	const APPSECRET = 'af87c4d879a2e9745f4044f76666aec9';
	
	//=======【JSAPI路径设置】===================================
	//获取access_token过程中的跳转uri，通过跳转将code传入jsapi支付页面
//	const JS_API_CALL_URL = 'http://fzm.tjtomato.com/Wexin/WxPay/jsApiCall';

	//=======【证书路径设置】=====================================
	//证书路径,注意应该填写绝对路径
//	const SSLCERT_PATH = 'D:\qampp\htdocs\DreamOfPhoenix\simplewind\Core\Library\Vendor\WxPayPubHelper\cacert\apiclient_cert.pem';
//	const SSLKEY_PATH = 'D:\qampp\htdocs\DreamOfPhoenix\simplewind\Core\Library\Vendor\WxPayPubHelper\cacert\apiclient_key.pem';
	const SSLCERT_PATH = 'E:\qampp\htdocs\fengzhimeng2\simplewind\Core\Library\Vendor\WxPayPubHelper\cacert\apiclient_cert.pem';
    const SSLKEY_PATH = 'E:\qampp\htdocs\fengzhimeng2\simplewind\Core\Library\Vendor\WxPayPubHelper\cacert\apiclient_key.pem';


    //=======【异步通知url设置】===================================
	//异步通知url，商户根据实际开发过程设定
    //	const NOTIFY_URL = 'http://www.ydcfdc.com/Wexin/WxPay/notify';

    //  const REDIRECT_URL = WEB_HOST."Wexin/WxPay/jsApiCall?params=";

	//=======【curl超时设置】===================================
	//本例程通过curl使用HTTP POST方法，此处可修改其超时时间，默认为30秒
	const CURL_TIMEOUT = 30;
}

?>