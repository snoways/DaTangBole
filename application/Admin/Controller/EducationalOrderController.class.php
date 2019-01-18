<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/12 0012
 * Time: 下午 01:40
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class EducationalOrderController extends AdminbaseController
{
    public function index()
    {
        $where=[];
        if($this->role == 'agent'){
            $where['fzm_small_table.agent_id'] = $this->uid;
        }
        $request=I('request.');

        if(!empty($request['uid'])){
            $where['id']=intval($request['uid']);
        }

        if(!empty($request['sex'])){
            $where['child_sex']=$request['sex'];
        }

        if(!empty($request['status'])){
            $where['fzm_educational_order.status']=$request['status'];
        }

        if(!empty($request['keyword'])){
            $keyword=$request['keyword'];
            $where['sn|child_name|child_grade'] = ['like', "%$keyword%"];
        }

        $oauth_user_model=M('EducationalOrder');
        $count=$oauth_user_model
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_educational_order.parent_id')
            ->join('LEFT JOIN fzm_small_table ON fzm_small_table.id=fzm_educational_order.st_id')
            ->where($where)
            ->count();
        $total=$oauth_user_model
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_educational_order.parent_id')
            ->join('LEFT JOIN fzm_small_table ON fzm_small_table.id=fzm_educational_order.st_id')
            ->where($where)
            ->sum('total_money');
        $this->assign('total',$total?$total:'0.00');
        $page = $this->page($count, 20);
        $lists = $oauth_user_model
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_educational_order.parent_id')
            ->join('LEFT JOIN fzm_small_table ON fzm_small_table.id=fzm_educational_order.st_id')
            ->where($where)
            ->order("o_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('*, fzm_parents.phone as f_phone, fzm_educational_order.status as o_status, fzm_educational_order.id as oid, fzm_small_table.s_name')
            ->select();
        foreach ($lists as $k=>$order)
        {
            $lists[$k]['tui_status'] = -1;
            if ($order['o_status']==5)
            {
                $lists[$k]['tui_status'] = M('Application')->where(['oid'=>$order['oid']])->getField('status');
                $lists[$k]['st_status'] = M('Application')->where(['oid'=>$order['oid']])->getField('st_status');
            }
        }

        $status = [1=>'待支付',2=>'进行中',3=>'已完成',4=>'已取消',5=>'已退款'];
        $this->assign('status', $status);

        $province = M('Areas')->where(['parentId'=>0])->select();
        $this->assign('province' ,$province);
        $this->assign("page", $page->show('Admin'));
        $this->assign('lists', $lists);
        $this->display();
    }
    /**
     * 导出EXCEL
     * sunfan
     *  2017.10.24
     */
    public function oexcel() {

        $where=[];
        $request=I('request.');

        if(!empty($request['uid'])){
            $where['id']=intval($request['uid']);
        }

        if(!empty($request['sex'])){
            $where['child_sex']=$request['sex'];
        }

        if(!empty($request['status'])){
            $where['fzm_educational_order.status']=$request['status'];
        }

        if(!empty($request['keyword'])){
            $keyword=$request['keyword'];
            $where['sn|child_name|child_grade'] = ['like', "%$keyword%"];
        }

        $oauth_user_model=M('EducationalOrder');
        $lists = $oauth_user_model
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_educational_order.parent_id')
            ->join('LEFT JOIN fzm_small_table ON fzm_small_table.id=fzm_educational_order.st_id')
            ->where($where)
            ->order("o_time DESC")
            ->field('*, fzm_parents.phone as f_phone, fzm_educational_order.status as o_status, fzm_educational_order.id as oid, fzm_small_table.s_name')
            ->select();
        foreach ($lists as $k=>$order)
        {
            $lists[$k]['tui_status'] = -1;
            if ($order['o_status']==5)
            {
                $lists[$k]['tui_status'] = M('Application')->where(['oid'=>$order['oid']])->getField('status');
                $lists[$k]['st_status'] = M('Application')->where(['oid'=>$order['oid']])->getField('st_status');
            }
        }
        $str = '
		<table border="1">
		<tr style="background-color:#ccffcc;min-height:30px;">
			<th width="120" align="center">订单编号</th>
			<th align="center">学生姓名</th>
			<th align="center">学生性别</th>
			<th align="center">会员手机号</th>
			<th align="center">科目</th>
			<th align="center">总课时</th>
			<th align="center">总课时费</th>
			<th align="center">下单时间</th>
			<th align="center">机构实际应得</th>
			<th align="center">订单状态</th>
		</tr>
		';
        foreach ($lists as $key=>&$value) {
            if (($key%2)==0){
                $str .='<tr align="center" style="background-color:#fdffcc;min-height:30px;">';
            }else{
                $str .='<tr align="center" style="min-height:30px;">';
            }
            if($value['o_status']==1){
                $statue =  "待支付";
            }elseif($value['o_status']==2){
                $statue = "上课中";
            }elseif($value['o_status']==3){
                $statue = "已完成";
            }elseif($value['o_status']==4){
                $statue = "已取消";
            }elseif($value['o_status']==5){
                if($value['tui_status']==1){
                    if($value['st_status']==1){
                        $statue = "审核中";
                    }else{
                        $statue = "已同意，等待后台退款";
                    }
                }elseif($value['tui_status']==2){
                    $statue = "已退款";
                }else{
                    $statue = "退款申请被驳回";
                }
            }
            $sex = $value['child_sex']==1?'男':'女';
            $num = 1;
            $str .= '
			<td width="120" rowspan='.$num.'>'.$value['sn'].'</td>
			<td rowspan='.$num.'>'.$value['child_name'].'</td>
			<td rowspan='.$num.'>'.$sex.'</td>
			<td rowspan='.$num.'>'.$value['f_phone'].'</td>
			<td rowspan='.$num.'>'.$value['subject'].'</td>
			<td rowspan='.$num.'>'.$value['class_num'].'</td>
			<td rowspan='.$num.'>'.$value['total_money'].'</td>
			<td rowspan='.$num.'>'.$value['o_time'].'</td>
			<td rowspan='.$num.'>'.$value['money'].'</td>
			<td rowspan='.$num.'>'.$statue.'</td>
			</tr>';
        }
        $str .="</table>";
        header('Content-Length: '.strlen($str));
        header("Content-type:application/vnd.ms-excel;charset=UTF-8");
        header("Content-Disposition:filename=订单表".date('Y-m-d H:i:s').".xls");
        echo $str;
    }

    public function city()
    {
        $city = M('Areas')->where(['parentId'=>$_GET['areaid']])->select();
        echo json_encode($city);
    }

    public function pay()
    {
        M('Order')->where(['sn'=>$_GET['sn']])->save(['status'=>2]);
    }

    //完成
    public function finish()
    {
        $id = $_REQUEST['id'];
        M('EducationalOrder')->where(array('id'=>$id))->save(['status'=>3]);
        echo json_encode(['code'=>1]);
        exit();
    }

    //审核
    public function status()
    {
        $id = $_REQUEST['id'];
        $status = $_REQUEST['status'];  //1.审核通过 2.审核不通过
        if ($status==1){
            M('Application')->where(array('oid'=>$id, 'o_type'=>2))->save(['status'=>2]);
            echo json_encode(['code'=>1]);
            exit();
        }else{
            M('Application')->where(array('oid'=>$id, 'o_type'=>2))->save(['status'=>3]);
            echo json_encode(['code'=>1]);
            exit();
        }
    }

    /**
     * 通过--退款申请
     * sunfan
     * 2017.10.24
     */
    public function passRefunds()
    {
        $id = $_REQUEST['id'];
        $application = M('Application')->where(['id'=>$id])->find();
        $order = M('Order')->where(['id'=>$application['oid']])->find();
        $trade_no = $order['trade_no'];
        $out_trade_no = $order['sn'];
        $money = $application['money'];
        $config = M('Config')->find();
        if ($order['paytype']==2){
            //微信退款
            $refund_fee = $money*100;
            $time_stamp = time();
            //商户退款单号，商户自定义，此处仅作举例
            $out_refund_no = "$out_trade_no"."$time_stamp";
            //总金额需与订单号out_trade_no对应，demo中的所有订单的总金额为1分
            $total_fee = $order['money']*100;

            //使用退款接口
            $refund = new \Refund_pub();
            //设置必填参数
            //appid已填,商户无需重复填写
            //mch_id已填,商户无需重复填写
            //noncestr已填,商户无需重复填写
            //sign已填,商户无需重复填写
            $refund->setParameter("out_trade_no","$out_trade_no");//商户订单号
            $refund->setParameter("out_refund_no","$out_refund_no");//商户退款单号
            $refund->setParameter("total_fee",$total_fee);//总金额
            $refund->setParameter("refund_fee",$refund_fee);//退款金额
            $refund->setParameter("op_user_id",\WxPayConf_pub::MCHID);//操作员
            //非必填参数，商户可根据实际情况选填

            //调用结果
            $refundResult = $refund->getResult();

            //商户根据实际情况设置相应的处理流程,此处仅作举例
            if (!$refundResult){
                $this->error('退款失败');
            }
            if ($refundResult["return_code"] == "FAIL") {
                echo "通信出错：".$refundResult['return_msg']."<br>";
            }
            else{

                M('Application')->where(['id'=>$id])->save(['status'=>2]);

                M('MoneyLog')->add([
                    'uid'       =>  $order['parent_id'],
                    'u_type'    =>  1,
                    'money'     =>  $money,
                    'status'    =>  3,  //1家长支付  2老师提现  3家长退款
                    'paytype'   =>  2,  //1支付宝 2微信
                    'state'     =>  2,  //1 收入  2支出
                    'm_time'    => date('Y-m-d H:i:s'),
                    'msg'       =>  '退款订单'.$out_trade_no,
                    'order_sn'  =>  $out_trade_no,
                    'm_name'    =>  $order['parent_name'],
                    'm_phone'    =>  $order['phone']
                ]);

                //退款成功后，把老师实际应得的钱放进老师钱包，并更新订单的钱
                $total = $order['total_money']; //订单总金额
                $m_money = $total-$money;       //除去退款的实际支付到账金额
                $t_money = $m_money*(1-$config['radio']);   //除去平台抽成，老师应得的钱
                $map = [
                    'platform_money'=>  $m_money*$config['radio'],
                    'money'         =>  $t_money,
                    'refunds_money' =>  $money      //退款金额
                ];
                M('Order')->where(['id'=>$application['oid']])->save($map);
                M('Teacher')->where(['id'=>$order['teacher_id']])->setInc('balance', $t_money);

                //给老师推送
                $push = new \JPushSF(C('JPush.TAPPID'),C('JPush.TAPPSECRET'));
                $receive = array('alias'=>['teacher'.$order['teacher_id']]);//别名
                $result = $push->push($receive,3);

                M('TeacherTime')->where(array('id'=>$order['teacher_time_id']))->save(array('status'=>1));
                echo 1;
            }

        }else{
            //支付宝退款
            require_once 'public/ali/aop/AopClient.php';
            require_once 'public/ali/aop/request/AlipayTradeRefundRequest.php';
            $aop = new \AopClient ();
            $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
            $aop->appId = "2017111309905854";
            $aop->rsaPrivateKey = 'MIIEowIBAAKCAQEAt1zaP2u+l7+1hY/PNj2FVIz9UDaDmbuv1PqHWhsH0JuAsZp2ArI2Q2kgNtRg/T+onvafd9sIaIbOv5ZIZ4c9eJtMqd/d2l3FAuRwfsxgJ7WXElYT2RNwcW7an/ljYuh5RmY1YL/R47UVbXPjS6z1rNAJ3apDnOUeE83yqtwfHiNsy79/+AItUg248IrIMmAcgcHgqoV0rKUnqPa9k7U9HeBHjymxhbMcrRIMupD+Iz7FxqWZUzyZwCdZ+O4nRXS1ExFU671pjtdqpPAUxWhhX/ZUw2eaWxA5x9FXRdK0x209Zqy8BmfEAQFg3c9014nsPJvAcBjjkhm3+wEgGzovcwIDAQABAoIBAF+0CP2dIOdXWkkR3Fc1GQgeZoCdU2bD/WwuLsMq5JNO9oa8VefmWEgq8HNtugm0VjBSDL2kBul3oyWALN5MShtPA16Ox96XnqJ51PV5ep5/OxCI+OcOLFwoPdfNdMBFEjdaVXXf6I7vDvYHzJTM+5VtKBkYKx4Sv/YaQhRHu74gYLdSMSUuYzCaiR8Pn3kdG/aiGrmZDHasHmLYutqwyH2Bh8IWPoJIPm+2cQoQeERUXxDf85lv5B3eeDZr1zvyaOK0Txfglq3e6w+ZJ2HMJY9b33c8+ZTIfqDhEYXwawNpcSz56xjTFD+gbU34+NqhlYkw3xqb+InQEdtcBYweNxECgYEA5YzArGIpLZMm2fC4sedAMRJO4FT/bnWBOA+Y75D6P7FNohLQhV6mQtf4eU0YqedwIECKMP8PNzQY0jbesxLNuOXB1NZgok6+uVS/hbp+ajWwvgao8cPmBLlFhhusvYavyT/qMU9MZj8Lee06XHi7lXzhP579+QEXZLdnGynloRUCgYEAzH2s17D5MVPljlL2i6Nbg8SaOP2eDDRpbNC10dEg9YD5aH5S+KDXHCQ+rR/GLQLZQ3VR9nxbBrB/8Xin/yniNS6p6MyTI2+IkozDnQQIOIy03ufCh3fC21AE4FW0vV9U3fFrl2cV/+b07BX4geI5E0v2QPd13nlLhZsuXJhU4GcCgYAPlw6i5ovLZ5oU5S92DbGjY43t2Hf8pYhgKVcGtj74wm72WfbFiBccpRRgEKdjKq/H0PpRt5Dt++DPriBT4ywLqbcPYHvxqg10Ath7GZ5qUjktvsAMo3Rkz7x0Dj8eJB6eOXQLY0paC2AZKM5051I+JdwaeQ7gsX1IPtiG1MKevQKBgQCgOWByaDH4WnolOBABfW+5IRSNzvpFKdPo9OdhjPC8K+A/5arxMGUbobKSR7Epl2/QkV41OV7BMQ4uj5FiNtkNPpDW3WP0gfGKkky6/GvMk0Ms3H7pUgcVe+82LzE8qDOA6yaYLKdqKPaC/PwIGM8LtZsvcDWkqXlpKBv9ZUYrxQKBgC5bV+rltzHsS1UXQAZAF3rwtbcoaUBHESKJlr+XHhUxYvbiui/w3Zj0KTSV8AhDAdb4S+H1OkxCMiYc4IcjUI8bWPqrCROhh2qc8e3xcrF7yAevZNcvCyr5xOkAO/zC5kfYdW7+w5I4Tb3V9UHU+5OPmBAQIhs5ifsDlp0RvpUS' ;
            $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhhMOH57Y6vu0jM04mU7+Fk+v5VtHbv1t8KuMWOWGwoCZPsm0IeEUoIjTX/Iwoszb2818GmPjwhacW/rwzq1ME+5sC85FJYzOCO52J97uEIt1VEoVkmnbwHuZJ4yCwyYJPJ9tulFmL15jjYin6p8pxf3eAIO8DmZ6Wib4oQzU3DfcdKqSJE8tIqepxH8EvyVPxi25N8D80tV75SgR2iOH33ZT7GKyv8IS2GSgqTD/+DkiLsq+QY7XB7O5GExquZRMsNN95mlsOanFXjc74T60uu2sPZ0NiY1jMWCGGU1Eo3rCaeC3iS2FA73MhQ9f4TUJJRllThuEGD8pOJ3cNTdPNwIDAQAB';
            $aop->apiVersion = '1.0';
            $aop->signType = 'RSA2';
            $aop->postCharset='UTF-8';
            $aop->format='json';
            $request = new \AlipayTradeRefundRequest ();
            $request->setBizContent("{" .
                "\"out_trade_no\":\"$out_trade_no\"," .
                "\"trade_no\":\"$trade_no\"," .
                "\"refund_amount\":\"$money\"," .
                "\"refund_reason\":\"正常退款\"," .
                "\"out_request_no\":\"HZ01RF001\"," .
                "\"operator_id\":\"\"," .
                "\"store_id\":\"\"," .
                "\"terminal_id\":\"\"" .
                "  }");
            $result = $aop->execute ( $request);

            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            if(!empty($resultCode)&&$resultCode == 10000){
                M('Application')->where(['id'=>$id])->save(['status'=>2]);

                M('MoneyLog')->add([
                    'uid'   =>  $order['parent_id'],
                    'u_type'    =>  1,
                    'money'     =>  $order['total_money'],
                    'status'    =>  3,  //1家长支付  2老师提现  3家长退款
                    'paytype'   =>  1,  //1支付宝 2微信
                    'state'     =>  2,  //1 收入  2支出
                    'm_time'    => date('Y-m-d H:i:s'),
                    'msg'       =>  '退款订单'.$out_trade_no,
                    'order_sn'  =>  $out_trade_no
                ]);

                //退款成功后，把老师实际应得的钱放进老师钱包，并更新订单的钱
                $total = $order['total_money']; //订单总金额
                $m_money = $total-$money;       //除去退款的实际支付到账金额
                $t_money = $m_money*(1-$config['radio']);   //除去平台抽成，老师应得的钱
                $map = [
                    'platform_money'=>  $m_money*$config['radio'],
                    'money'         =>  $t_money,
                    'refunds_money' =>  $money      //退款金额
                ];
                M('Order')->where(['id'=>$application['oid']])->save($map);
                M('Teacher')->where(['id'=>$order['teacher_id']])->setInc('balance', $t_money);

                echo 1;
            } else {
//                echo "失败";
                echo $result->alipay_trade_refund_response->sub_msg;
            }
        }


    }
}