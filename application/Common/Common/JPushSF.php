<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2018/3/2
 * Time: 9:24
 */

require './simplewind/Core/Library/Vendor/jpush/autoload.php';


/*** 极光推送  通知推送*****/
class JPushSF{
    private $app_key;        //待发送的应用程序(appKey)，只能填一个。
    private $master_secret ;    //主密码
    private $url = "https://api.jpush.cn/v3/push";      //推送的地址



    //若实例化的时候传入相应的值则按新的相应值进行
    public function __construct($app_key=null, $master_secret=null,$url=null) {
        if ($app_key) $this->app_key = $app_key;
        if ($master_secret) $this->master_secret = $master_secret;
        if ($url) $this->url = $url;
    }
    //极光推送的类
    //文档见：http://docs.jpush.cn/display/dev/Push-API-v3


    /**
     * @param string $receiver 接收者
     * @param string $m_type 推送附加字段的类型 1.老师-有新订单 2.家长-老师已接单 3.老师-到账提醒
     *                          4.老师/商户-购买视频课程 5.商户-购买线下活动 6.商户-购买课程 7.商户-购买托管 8.三端-圈子评论 9.家长端-关注人发圈子消息
     *                          10.商户端-账号未通过审核 11.商户端-账号通过审核 12.系统消息
     * @param int $id 推送附加字段的id值
     * $m_time 保存离线时间的秒数默认为一天(可不传)单位为秒
     * $param string $content 推送的内容。
     */
    public function push($receiver='',$m_type='', $id=0, $content=""){

        $m_time='86400';
        switch ($m_type) {
            case 1:
                $content = "您有新订单，快去接单吧";
                break;
            case 2:
                $content = "老师已接单，快去支付吧";
                break;
            case 3:
                $content = "您有一笔资金已到账，快去查查吧";   //无id
                break;
            case 4:
                $content = "有人购买您的视频课程了，快去看看吧~";
                break;
            case 5:
                $content = "有人报名您的线下活动了，快去看看吧~";
                break;
            case 6:
                $content = "有人购买您的课程了，快去看看吧~";
                break;
            case 7:
                $content = "有人购买您的托管班了，快去看看吧~";
                break;
            case 8:
                $content = '您发布的动态又有新评论了，快去看看吧~';
                break;
            case 9:
                $content = '您关注的人又有新的动态了，快去看看吧~';
                break;
            case 10:
                $content = "您的账号未通过审核，请前往APP查看";    //无id
                break;
            case 11:
                $content = "您的账号已通过审核，请前往APP查看";    //无id
                break;
            case 12:
                $content = "您的游记评论有人回复了，快去看看吧~";
                break;
            case 13:
                $content = "您的活动评论有人回复了，快去看看吧~";
                break;
        }

        $base64=base64_encode("$this->app_key:$this->master_secret");
        $header=array("Authorization:Basic $base64","Content-Type:application/json");
        $data = array();
        $data['platform'] = 'all';          //目标用户终端手机的平台类型android,ios,winphone

        $data['audience'] = $receiver;      //目标用户


        $data['notification'] = array(
            //统一的模式--标准模式
            "alert"=>$content,
            //安卓自定义
            "android"=>array(
                "alert"=>$content,
                "title"=>"",
                "builder_id"=>1,
                "extras"=>array("type"=>$m_type, 'id'=>$id)
            ),
            //ios的自定义
            "ios"=>array(
                "alert"=>$content,
                "badge"=>"1",
                "sound"=>"default",
                "extras"=>array("type"=>$m_type, 'id'=>$id)
            )
        );
        $data['options'] = ["apns_production"=>false];

        $param = json_encode($data);
        $result = $this->push_curl($param,$header);
        return $result;
//        dump($result);
    }

//推送的Curl方法
    public function push_curl($param="",$header="") {
        if (empty($param)) { return false; }
        $postUrl = $this->url;
        $curlPost = $param;
        $ch = curl_init();                                      //初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);                 //抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);                    //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);            //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);                      //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);           // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch);                                 //运行curl
        curl_close($ch);
        return $data;
    }

}