<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/2/4
 * Time: 9:55
 */

namespace Client\Controller;
use Think\Controller;


class MapiBaseController extends Controller {
    //返回
    protected $data;

    protected function _initialize(){
        header("Access-Control-Allow-Origin: *");
        $data = $_REQUEST;
        $this->writeLog(json_encode($data));
        if(is_null($data)){
            $this->ApiReturn(10001,'数据格式错误');
        }
        $this->data=$data;
    }
    //api返回
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

        $this->writeLog(json_encode($arr));
        die();
    }

    protected function JsReturn($code,$msg,$data=null,$num=-1){
        $arr['code']=$code;
        $arr['msg']=$msg;
//        $arr['num']=$num;
        if(empty($data)&&!is_array($data)){
            $data=array();
        }
        //$data = str_replace(null,"",$data);
        $arr['data']=$data;
        $arr['time']=time();
        $a = $this->data;

        $callback = isset($_GET['callback']) ? trim($_GET['callback']) : ''; //jsonp回调参数，必需
        echo $callback.'('.str_replace('\/',"/",json_encode($data,JSON_UNESCAPED_UNICODE)).')';

        $this->writeLog(json_encode($arr));
        die();
    }

    /**
     * 获得TOKEN
     * @param $phone
     * @param $password
     * @param $id
     * @param $type 1.家长 2.老师 3.商户
     * @return string
     */
    protected function get_token($phone,$password,$id, $type){
        if ($type==1){
            $first="p";
        }elseif ($type==2){
            $first="t";
        }else{
            $first="s";
        }
        $token=$first.md5($phone.md5($password).time().$this->createNoncestr());
        //S($token,$id,86400);
        // S('name',null);
        S($token,$id,2592000);
        return $token;
    }
    function inputFilter($content)
    {
        if(is_string($content) ) {
            return $this->clean($content);
        }
        elseif(is_array($content)){
            foreach ( $content as $key => $val ) {
                $content[$key] = inputFilter($val);
            }
            return $content;
        }
        elseif(is_object($content)) {
            $vars = get_object_vars($content);
            foreach($vars as $key=>$val) {
                $content->$key = inputFilter($val);
            }
            return $content;
        }
        else{
            return $content;
        }
    }
    function clean($v) {
        //判断magic_quotes_gpc是否为打开
        if (!get_magic_quotes_gpc()) {
            //进行magic_quotes_gpc没有打开的情况对提交数据的过滤
            $v = addslashes($v);
        }
        $v=str_replace("update", "", $v);
        //把'_'过滤掉
        $v = str_replace("_", "\_", $v);
        //把'%'过滤掉
        $v = str_replace("%", "\%", $v);
        //把'*'过滤掉
        $v = str_replace("*", "\*", $v);
        //回车转换
        $v = nl2br($v);
        //html标记转换
        $v = htmlspecialchars($v);
        return $v;
    }

    function writeLog($mes)
    {
        $myfile = file_put_contents("log.txt", date('Y-m-d H:i:s')." ".$mes."\n", FILE_APPEND);
        //fwrite($myfile, date('Y-m-d H:i:s')." ".$mes);
        fclose($myfile);
    }
    function createNoncestr( $length = 32 )
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
    /**
     * 检查手机号码格式
     * @param $mobile 手机号码
     */
    function check_mobile($mobile){
        if(preg_match('/1[34578]\d{9}$/',$mobile))
            return true;
        return false;
    }

    /**
     * 检查邮箱地址格式
     * @param $email 邮箱地址
     */
    function check_email($email){
        if(filter_var($email,FILTER_VALIDATE_EMAIL))
            return true;
        return false;
    }

    protected function get_department_name($department_id){
        $department_name = M('Department')->where(array('id'=>$department_id))->field('d_name')->find();
        return $department_name['d_name'];
    }

    /**
     * 送优惠券
     * 2018/5/14 0014 下午 03:06
     * sunfan
     */
    public function giftCoupons($id)
    {
        if (!empty($id)){
            //查找所有未过期的优惠券
            $now = date('Y-m-d H:i:s');
            $model = M("coupon");
            $info = $model
                ->where(['expire_date'=>['gt', $now], 'c_status'=>1])
//                ->field('m.*,pp.name')
                ->select();

            foreach ($info as $item){
                M('CouponRecords')->add([
                    'coupon_id'     =>  $item['id'],
                    'uid'           =>  $id,
                    'create_time'   =>  date('Y-m-d H:i:s'),
                    'start_date'    =>  $item['start_date'],
                    'expire_date'   =>  $item['expire_date'],
                    'money'         =>  $item['money'],
                    'min_consumption'=> $item['min_consumption'],
                ]);
            }
        }
    }

    /**
     * @param $id int 商家id
     * @param $otype string 订单类型
     * @param $oid int 订单id
     * @param $money float 金额
     */
    public function addMoney($id,$otype,$oid, $money)
    {
        $db = M('SmallTable');
        $user = $db->where(array('id'=>$id))->find();
        $total = floatval($money) + floatval($user['balance']);
        $db->where(array('id'=>$id))->save(array('balance'=>$total));
        M('small_table_money_log')->add([
            'o_type' => $otype,
            'o_id' => $oid,
            't_id' => $id,
            'money' => $money,
        ]);
    }

    /**
     * 查找是否有优惠券可以使用
     * sunfan
     * 2018.5.21
     * @uid 家长id
     * @type 1.老师下单 2.托管班下单 3.教育机构下单 4.线下活动订单 5.线上课堂订单
     * @money 订单金额
     * @coupon_id 优惠券id
     * @return []
     */
    public function useCoupon($uid, $type, $money, $coupon_id)
    {
        $coupon = M('CouponRecords')
            ->alias('cr')
            ->join('LEFT JOIN fzm_coupon c ON c.id=cr.coupon_id')
            ->where([
                'uid'=>$uid,
                'cr.id'=>$coupon_id
            ])
            ->field('cr.*, c.type, c.min_consumption')
            ->find();
        if (!$coupon){
            return 0;
        }

        //找一下 该类目或者通用类目可使用的优惠券
        if ($coupon['type']!=1 && $coupon['type']!=($type+1)){
            return 0;
        }

        //是否满足满减金额
        if ($coupon['min_consumption']>$money){
            return 0;
        }

        //修改状态为已使用，并返回优惠券金额
        M('CouponRecords')->where(['id'=>$coupon['id']])->save(['cr_status'=>2]);
        return $coupon['money'];
    }

}