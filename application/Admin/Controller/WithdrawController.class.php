<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/10/24
 * Time: 15:13
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;
use JPush\Client;
require('./application/Common/common/JPushSF.php');

class WithdrawController extends AdminbaseController
{

    function _initialize()
    {
        parent::_initialize();
        vendor('WxPayPubHelper.WxPayPubHelper');
    }

    /**
     * 提现申请列表
     * sunfan
     * 2017.10.24
     */
    public function index()
    {
        $where=['a.type'=>2, 'a.u_type'=>2];
        if($this->role=='agent'){
            $areas = D('Users')->where(array('id'=>$this->uid))->getField('areacode');
            $where['t.areaId'] = array('in',$areas);
        }
        $request=I('request.');

        if(!empty($request['status'])){
            $where['a.status']=intval($request['status']);
        }

        $oauth_user_model=M('Application');
        $count=$oauth_user_model
            ->alias('a')
            ->join('left join __TEACHER__ t on a.uid = t.id')
            ->where($where)->count();
        $page = $this->page($count, 20);
        $lists = $oauth_user_model
            ->alias('a')
            ->join('left join __TEACHER__ t on a.uid = t.id')
            ->where($where)
            ->order("a_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.*')
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign('lists', $lists);
        $this->display();
    }
    public function index2()
    {
        $where=['a.type'=>2, 'a.u_type'=>3];

        $request=I('request.');

        if(!empty($request['status'])){
            $where['a.status']=intval($request['status']);
        }

        $oauth_user_model=M('Application');
        $count=$oauth_user_model
            ->alias('a')
            ->join('left join __SMALL_TABLE__ t on a.uid = t.id')
            ->where($where)->count();
        $page = $this->page($count, 20);
        $lists = $oauth_user_model
            ->alias('a')
            ->join('left join __SMALL_TABLE__ t on a.uid = t.id')
            ->where($where)
            ->order("a_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.*,t.s_name,t.s_phone,t.s_type')
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign('lists', $lists);
        $this->display();
    }

    //查看代理商收入支出信息（审核体现用）
    public function show_info()
    {
        $id = I('id');
        $info = M('small_table')->where(['id'=>$id])->field('id,balance,s_name,freeze')->find();
        if ($info) {
            //收入
            $income = M('small_table_money_log')
                ->where(['t_id'=>$id])
                ->select();
            $info['total_money'] = sprintf('%.2f',array_sum(array_column($income,'money')));
            foreach ($income as $key=>&$value) {
                $where['id']=$value['o_id'];
                $field = 'sn';
                $value['money'] = '+'.$value['money'];
                $value['transaction_type'] = 1;//收入
                switch ($value['o_type']) {
                    // O课程订单 A活动订单 C课程订单 H托管订单
                    case "O":
                        $db = M('OnlineClassOrder');
                        $where['tid'] = $value['t_id'];
                        $field .= ',o_time create_time,paytype';
                        break;
                    case "A":
                        $where['st_id'] = $value['t_id'];
                        $db = M('ActivityOrder');
                        $field .= ',sign_time create_time,paytype';
                        break;
                    case "C":
                        $where['st_id'] = $value['t_id'];
                        $db = M('EducationalOrder');
                        $field .= ',o_time create_time,paytype';
                        break;
                    case "H":
                        $where['st_id'] = $value['t_id'];
                        $db = M('HostingOrder');
                        $field .= ',o_time create_time,paytype';
                        break;
                }
                $order = $db->where($where)->field($field)->find();
                $value = array_merge($value,$order);
                unset($income[$key]['t_id']);
                unset($income[$key]['o_id']);
            }

            //支出（提现）
            $expenditure = M('application')
                ->where(['uid'=>$id,'u_type'=>3,'status'=>2,'type'=>2])
                ->field('id,money,t_type paytype,sn,a_time create_time')
                ->select();
            if ($expenditure) {
                foreach ($expenditure as &$item) {
                    $item['transaction_type'] = 2;//支出（提现）
                    $item['money'] =  '-'.$item['money'];
                }
                $info['capital_flow'] = array_merge($income,$expenditure);
            } else {
                $info['capital_flow'] = $income;
            }
            foreach ($info['capital_flow'] as $key => $row) {
                $volume[$key]  = $row['create_time'];
            }

            array_multisort($volume, SORT_DESC,$info['capital_flow']);
        }
        $this->assign('lists', $info);
        $this->display();
    }


    /**
     * 通过--提现申请
     * sunfan
     * 2017.10.24
     */
    public function pass()
    {
        $type = I("post.type");
        $id = $_REQUEST['id'];
        $ap = M('Application');
        $Application = $ap->where(['id'=>$id])->find();
        //支付宝打款
        if($type == 2 && $Application['t_type'] == 2){

            $out_trade_no = $Application['sn'];
            $amount = $Application['money'];
            $number = $Application['number'];
            $card_name = $Application['card_name'];


            require_once('./public/ali/aop/AopClient.php');
            require_once('./public/ali/aop/request/AlipayFundTransToaccountTransferRequest.php');
            $aop = new \AopClient ();
            $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
            $str = $_REQUEST['str'];
            //老师端
//            if($str == 't'){
//                $aop->appId = '2017112200090326';
//                $aop->rsaPrivateKey = 'MIIEogIBAAKCAQEAtpYSL00LlBEKE9bqLNH8T1rqQxIwSB3f9CIRGYW9xfWOU7F1cOtpuCGtdO/kRFcFEZBuEHInZtX+UKgMEmw9M5dhHFHH+e7FZgJkgHk9xGKRATWMHD0cWLU5IeL17UqdeJN6LRD1Du4r3Ypj0FegX1ioIMM/LzNrB4EHH/Sime0OdemuLUAiicBBdxkXfj58HhP6NVJl1IEphOEk5eU4arQD+NFoaYuyt3xer/yVxM8eRWcm1Npl2Pl8Y7E82eshcH1YVWGaFuCVvlJUsSCzWMlizJnbaOCsA+vtQQR/r5Id6jrkD2644dGZR4xF2wY0OTe6Y2FjT4nSSnC/F8EO+QIDAQABAoIBAAPT9aGkPd/m041C7jnuVRnc0BiD4xs/9RgLNsyQL0BdO5Spncq2RMsleZuABAsiv+p0Wrphik3voptSSp6AQnA4dkK/vC+TP/Q5jJ3c7NyXLG8YDk3xQgziD8aUGY/WBqMmhGM5fcnIWIcWha0yiRw2oZ++OC7nJxFLNTkISfhsJMsEkDxu4bPJorUCzcHU1bosv7gfhCYvypci9VNm2x6wHJx9jehk7N2H+aB43d+kkZ2041Wk1ZVzYFa/G48W/9iyh9nvXuy+lScPneUkKdpHHVFB4mhgFjUjVYDh5XbPtfMTiSe4xqNzwE+yLVIgoCvis0lhALy1Ry1xoC48CDkCgYEA7wOTi66oRozJ6z7xtzxl3YJG/9l2vnArDKF2uEfASm25sh98H+9Z5Kb5pGCo8Pal3FjvqmT0i/31fkEzkunIclxhdqRaXa5xXh/YR8rOtEF861WXYZK4ZzQ7duEa1CLsp1SpS0hTgT+/G87EEVfI+DIIWWhgalo2o4vLHlvHXlMCgYEAw4/k/inMAMEXaLsKKtokuCHcRpYhqJCw5BMycWUY6HvRFnzjjitp1ozQZIVETSQoRxbaabTNxg2D2HnKXqEIbM5bIJR69xOQxggpYhd/Rc8zM/Lfgrs8SE83PJNyvC+kNBvQKAH+1HhDuNLeX655i603uySvWAiy+JQEWNeCvAMCgYATS9uenFQzlew7VNKY84RZ1Mm8oCbpCw8+rs5x5EEPATrLuaUAwwcj4aMn9THOems7leaLgCkKIE+wiL0MMFmhefnYZT5yb8HxUmrYqPP1M5BNQ5S8KOdAVcQzPcs3szYd8ETWshkjxyy7pv7HU6oC968a4MVf8LaWj5OveMNoxwKBgCAn0OdZyAl3tnmqB4n0RIViS+3vUal94Rgfb/PlQ6s2cLLZ5jDCQqzciod8wjZM87J8t30aFZuzLTKzE+trXw9E/wbkYzOtK+jj/qn6Yxr/btPj44yDbO4W2GZFeGApFT7cM+XgLh6Rh9EkGxxwe9vTp45GAe7fv03QSMay6PQxAoGAZIZjrOBAr8aiZGSsimoAyXGN/oOizRCSF5w01ogEd96ZU1kZeu3KqnTZNGF+1hhD9/RXiGKd1p94ue/y539rSPxN5nOJsRj1//K73sNJ5Ce3hq2JsqIubn+MdplkKYfGPG7NZesnuaN2CV5+nLN2ZVnwrPo7/vYiR67zeti41cU=';
//                $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhhMOH57Y6vu0jM04mU7+Fk+v5VtHbv1t8KuMWOWGwoCZPsm0IeEUoIjTX/Iwoszb2818GmPjwhacW/rwzq1ME+5sC85FJYzOCO52J97uEIt1VEoVkmnbwHuZJ4yCwyYJPJ9tulFmL15jjYin6p8pxf3eAIO8DmZ6Wib4oQzU3DfcdKqSJE8tIqepxH8EvyVPxi25N8D80tV75SgR2iOH33ZT7GKyv8IS2GSgqTD/+DkiLsq+QY7XB7O5GExquZRMsNN95mlsOanFXjc74T60uu2sPZ0NiY1jMWCGGU1Eo3rCaeC3iS2FA73MhQ9f4TUJJRllThuEGD8pOJ3cNTdPNwIDAQAB';
//            }else{

                //商户端
                $aop->appId = '2018041102536222';
                $aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAwZXRdKdWHOEr12lG7QWWjWpKImFjecsJcRhz4cHr5S5dpkzCOLEF3BtIKRNYntJYaPIEyJWgJ9N0Oe5Vzv/cbxAGL+IBDd73ZJw5Ke1tizTMJIbN4aUVFQynXfwA5/Oea1p9N684MyBugslVdrpp9mxRJvMrAD62A29JJqQqo30BDIRamaLK/YpROZUy5vkMPNFKQysPqbyJDv1Pk47D6pqGMgIk09yG314Lllpy26qOQ1asAbhLRCaFx3oRk8JgW2BATt/SqN+yOp1J9Ntbeyh6rx3f3G1daGZnkxjD0GC2JSnO6N1w0PvCbV3KMsTG4w51L4dynHoFaI2lKafwsQIDAQABAoIBAE8kWdpQIyNxZwQM9UMnerJb1u0RVaLQQA7tiUqthixO9VhsnyagMJ4YxTqNvzhHFH/rGcuLaEt/5k9cwdX7wnnhWjYvNnEeS5PlTnV2rMcxkZgJSJhMbj8Jyk7hHNm3PnfzKagfSWGVi7iKaRc+BN4K0G6VEWprOOxUjdpvWUM2vBXXKtae8OMuAKukr/vEyQdp0SLwESt2SRNdJVIHpwwkt0fwgpXCO37/U3JuArIwtMHuogjKkeXUKyq+XxZpTXf1qRrVCml0S0aFj1grkARIU0cm/v0aKdiasOIQX1oliwvLcd6vFokyPM7k75zg9qvKId1Lax99mPDj1n9VSgECgYEA8dr6OsuM5WrwbQnKZ/X/FAGewYl14fDPgVoltH1t/fKaQwDLn4kAm1vUhwGv7MYIwChOUrTFr1VMoxvubMk9h+6FOpCSFuhkzf2JN4Ctyn9SCRS0gryICk/iJpUyXHE+nmUyE8rsB7ntc30ABtneaOK2gfay9eQfv5G538fsVRECgYEAzOglsBy5HY19izxJTQ2nBHMpNBKkKRynY9sKOGJk6EqTRvDkeoziT0XJYKNd0TE9y9NSEs+t6d3+9TUF/mNOEbtk7OCTOQAKe5F3px5W0mgPldSFVrPvdnDiGvnRVOW438tLgB/zLRCDWCEbfwN5nCXPPodAqXJbePsjKFkbYaECgYBRNNpyJWhwm6CQrAnnMETufcDFcRdAvu+dmhww5zCoZO4A82Jrdb/balEI57sfQDst8hqiUIpT3cs2tSkwI73iR2c6i9JRmMRIGgoZtb4k0O1FmUsm3pC7Dal8lPns6iVBX+8ZkDgCPB6LeXwp0LuJ8h2fs6rRP0CdvRtxFRq4UQKBgQCczFB6saAeMzWMpHdbFUVnLFCtXk5sf1bAHM93UiPxdY+5y4CrHr/W9Yoh/yE9gTbOkEjPyEhHG++L6CVMAuWsv/99HGTMS3G6GRi8s4SwwZybhOL78/kcY0lCZ0R+eMO9zS1bQBevtmErwTnvOdOHX491Q76Ba9b/fv3qVDWVwQKBgQCtTYZ//mI/KOZB+MftlUXnmihqDMjMwdmnCIZUp0nRC4SJRMiaFndDmbyfFLjaez+cz+VtnL7EgrqvsusvM2lsKpAhQ7WE9G0N8XtxD73RIrVvChk+R7A4hz9lxMYMd5I3Q916zyI3jT+yF3LCVg/dtOwya8jq+yQg3Zyu/vkjAw==';
                $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhhMOH57Y6vu0jM04mU7+Fk+v5VtHbv1t8KuMWOWGwoCZPsm0IeEUoIjTX/Iwoszb2818GmPjwhacW/rwzq1ME+5sC85FJYzOCO52J97uEIt1VEoVkmnbwHuZJ4yCwyYJPJ9tulFmL15jjYin6p8pxf3eAIO8DmZ6Wib4oQzU3DfcdKqSJE8tIqepxH8EvyVPxi25N8D80tV75SgR2iOH33ZT7GKyv8IS2GSgqTD/+DkiLsq+QY7XB7O5GExquZRMsNN95mlsOanFXjc74T60uu2sPZ0NiY1jMWCGGU1Eo3rCaeC3iS2FA73MhQ9f4TUJJRllThuEGD8pOJ3cNTdPNwIDAQAB';
//            }

            $aop->apiVersion = '1.0';
            $aop->signType = 'RSA2';
            $aop->postCharset='UTF-8';
            $aop->format='json';
            $request = new \AlipayFundTransToaccountTransferRequest ();
            $request->setBizContent("{" .
                "\"out_biz_no\":\"".$out_trade_no."\"," .
                "\"payee_type\":\"ALIPAY_LOGONID\"," .
                "\"payee_account\":\"".$number."\"," .
                "\"amount\":\"".$amount."\"," .
                "\"payer_show_name\":\"大唐伯乐提现\"," .
                "\"payee_real_name\":\"".$card_name."\"," .
                "\"remark\":\"\"" .
                "  }");
            $result = $aop->execute ( $request);
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            if(!empty($resultCode)&&$resultCode == 10000){
                $save = $ap->where(['id'=>$id])->save(['status'=>2]);
                if($save){
                    M('small_table')->where(['id'=>$Application['uid']])->setInc('freeze',$Application['money']);
                    echo json_encode(['code'=>1, 'msg'=>"成功"]);
                }
            }else{
                echo json_encode(['code'=>0, 'msg'=>$result->$responseNode->sub_msg]);
            }
//        }else{
//            //银行卡提现
//            $save = $ap->where(['id'=>$id])->save(['status'=>2]);
//            if($save){
//                $ap->where(['id'=>$Application['uid']])->save(array('freeze'=>0));
//                echo "成功";
//            }else{
//                echo "失败";
//            }
        }

//        $id = $_REQUEST['id'];
//        M('Application')->where(['id'=>$id])->save(['status'=>2]);
//        $Application = M('Application')->where(['id'=>$id])->find();
//        // M('Teacher')->where(['id'=>$Application['uid']])->setDec('balance', $Application['money']);
//        M('Teacher')->where(['id'=>$Application['uid']])->save(array('freeze'=>0));
//        echo 1;
//        echo M('Teacher')->getLastSql();
    }

    /**
     * 不通过--提现申请
     * sunfan
     * 2017.10.24
     */
    public function refuse()
    {
        $id = I('post.id');
        $reason = I('post.reason');
        M('Application')->where(['id'=>$id])->save(['status'=>3, 'reason'=>$reason]);

        $Application = M('Application')->where(['id'=>$id])->find();
        $type = I("post.type");
        if($type == 2){
            //把钱给商户
            $model = D("small_table");
        }else{
            //把钱给老师
            $model = D("Teacher");
        }
        $teacher = $model->where(array('id'=>$Application['uid']))->find();
        $total = $teacher['balance'] +$teacher['freeze'];
        $model->where(['id'=>$Application['uid']])->save(array('balance'=>$total,'freeze'=>0));
        echo 1;
    }

    /**
     * 退款申请  老师课程订单
     * sunfan
     * 2017.10.24
     */
    public function refunds()
    {
        $where=['type'=>1];
        $request=I('request.');

        if($this->role == 'agent'){
            $areas = D('Users')->where(array('id'=>$this->uid))->getField('areacode');
            $where['t.areaId'] = array('in',$areas);
        }

        if(!empty($request['status'])){
            $where['fzm_application.status']=intval($request['status']);
        }

        $oauth_user_model=M('Application');
        $count=$oauth_user_model
            ->join('LEFT JOIN fzm_educational_order ON fzm_educational_order.id = fzm_application.oid')
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $lists = $oauth_user_model
            ->join('LEFT JOIN fzm_educational_order ON fzm_educational_order.id = fzm_application.oid')
            ->where($where)
            ->order("a_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('fzm_application.*, fzm_educational_order.sn')
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign('lists', $lists);
        $this->display();
    }
    //退款列表   线下活动订单
    public function refund_actity(){
        $where['o.refund_status'] = ['neq',1];
        if (!empty(I('status'))){
            $where['o.status'] = I('status');
        }else{
            $where['o.status'] = ['in',[3,4]];
        }
        $model = D("ActivityOrder");
        $count = $model->alias('o')
            ->join("LEFT JOIN fzm_parents p on o.uid = p.id")
            ->where($where)->count();
        $page = $this->page($count,15);
        $list = $model->alias('o')
            ->join("LEFT JOIN fzm_parents p on o.uid = p.id")
            ->where($where)
            ->limit($page->firstRow,$page->listRows)
            ->field('o.*')
            ->order("o.sign_time desc")
            ->select();
        $activity = D("activity");
        foreach($list as $k=>$v){
            $list[$k]['title'] = $activity->where(['id'=>$v['activity_id']])->getField('title');
            $list[$k]['shop_id'] = $activity->where(['id'=>$v['activity_id']])->getField('shop_id');
        }
        $this->assign("page", $page->show('Admin'));
        $this->assign('lists', $list);
        $this->display();
    }
    public function alirefunds()
    {
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
            "\"out_trade_no\":\"2017120699999857\"," .
            "\"trade_no\":\"2017120621001004640210851357\"," .
            "\"refund_amount\":\"0.01\"," .
            "\"refund_reason\":\"正常退款\"," .
            "\"out_request_no\":\"HZ01RF001\"," .
            "\"operator_id\":\"\"," .
            "\"store_id\":\"\"," .
            "\"terminal_id\":\"\"" .
            "  }");
        $result = $aop->execute ( $request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        dump($result);
    }

    public function wxrefunds()
    {
        $out_trade_no="D2018011110198505";
        //微信退款
        $refund_fee = 1;
        $time_stamp = time();
        //商户退款单号，商户自定义，此处仅作举例
        $out_refund_no = "$out_trade_no"."$time_stamp";
        //总金额需与订单号out_trade_no对应，demo中的所有订单的总金额为1分
        $total_fee = 1;

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
        dump($refundResult);die();
        if (!$refundResult){
            $this->error('退款失败');
        }
        if ($refundResult["return_code"] == "FAIL") {
            echo "通信出错：".$refundResult['return_msg']."<br>";
        }
    }

    //线下活动订单退款
    public function activityRefunds(){
        $id = $_REQUEST['id'];
        $activity_order = D("activity_order")->where(['id'=>$id])->find();
        $user_info = D("parents")->where(['id'=>$activity_order['uid']])->find();
        $trade_no = $activity_order['trade_no'];
        $out_trade_no = $activity_order['sn'];
        $money = $activity_order['pay_money'];
        if($activity_order['paytype'] == 2) {
            //微信退款
            $refund_fee = $activity_order['now_refund_money'] * 100;
            $time_stamp = time();
            //商户退款单号，商户自定义，此处仅作举例
            $out_refund_no = "$out_trade_no" . "$time_stamp";
            //总金额需与订单号out_trade_no对应，demo中的所有订单的总金额为1分
            $total_fee = $money * 100;

            //使用退款接口
            $refund = new \Refund_pub();
            //设置必填参数
            //appid已填,商户无需重复填写
            //mch_id已填,商户无需重复填写
            //noncestr已填,商户无需重复填写
            //sign已填,商户无需重复填写
            $refund->setParameter("out_trade_no", "$out_trade_no");//商户订单号
            $refund->setParameter("out_refund_no", "$out_refund_no");//商户退款单号
            $refund->setParameter("total_fee", $total_fee);//总金额
            $refund->setParameter("refund_fee", $refund_fee);//退款金额
            $refund->setParameter("op_user_id", \WxPayConf_pub::MCHID);//操作员
            //非必填参数，商户可根据实际情况选填

            //调用结果
            $refundResult = $refund->getResult();

            //商户根据实际情况设置相应的处理流程,此处仅作举例
            if (!$refundResult) {
                $this->error('退款失败');
            }
            if ($refundResult["return_code"] == "FAIL") {
                echo "通信出错：" . $refundResult['return_msg'] . "<br>";
            } else {
                $this->refundBack($activity_order['id'], 2);
                echo "退款成功";

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

            //同一订单 退款多次的订单批号
            $num = time().$activity_order['id'];
            $refund_fee = $activity_order['now_refund_money'];
            $request = new \AlipayTradeRefundRequest ();
            $request->setBizContent("{" .
                "\"out_trade_no\":\"$out_trade_no\"," .
                "\"trade_no\":\"$trade_no\"," .
                "\"refund_amount\":\"$refund_fee\"," .
                "\"refund_reason\":\"正常退款\"," .
                "\"out_request_no\":\"$num\"," .
                "\"operator_id\":\"\"," .
                "\"store_id\":\"\"," .
                "\"terminal_id\":\"\"" .
                "  }");
            $result = $aop->execute ( $request);

            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            if(!empty($resultCode)&&$resultCode == 10000){
                $this->refundBack($activity_order['id'], 1);
                echo 1;
            } else {
                echo $result->alipay_trade_refund_response->sub_msg;
            }
        }
    }

    /**
     * 退款成功后的操作
     * @param $id 订单id
     * @param $type 1.支付宝 2.微信
     * sunfan
     * 2018.8.21
     */
    protected function refundBack($id, $type){
        $activity_order = D("activity_order")->where(['id'=>$id])->find();
        $user_info = D("parents")->where(['id'=>$activity_order['uid']])->find();
        $trade_no = $activity_order['trade_no'];
        $out_trade_no = $activity_order['sn'];
        $money = $activity_order['pay_money'];
        //全额退款
        if($activity_order['refund_money'] == $money){
            M('activity_order')->where(['id'=>$id])->save(['status'=>4,'refund_status'=>4, "platform_money"=>0.00, "money"=>0.00]);
            M("ActivityPreview")->where(['ao_id'=>$activity_order['id']])->delete();
        }else{
            //部分退款
            $config = M('Config')->find();
            $total = $activity_order['pay_money']-$activity_order['refund_money'];
            $platform_money = $total*$config['activity_radio'];
            $money = $total*(1-$config['activity_radio']);

            M('activity_order')->where(['id'=>$id])->save(['status'=>2,'refund_status'=>4, "platform_money"=>$platform_money, "money"=>$money]);

            $config = M('Config')->find();

            $now_refund_num1 = $activity_order['now_refund_money']*(1-$config['activity_radio']);
            $now_refund_num = sprintf("%.2f" ,$now_refund_num1);
            //删除临时表里数据
            M("ActivityPreview")->where(['ao_id'=>$activity_order['id']])->setDec('money', $now_refund_num);
        }
        M("act_order_detail")->where(['order_id'=>$activity_order['id'],'status'=>2])->save(['status'=>3]);

        M('MoneyLog')->add([
            'uid'       =>  $activity_order['uid'],
            'u_type'    =>  1,
            'money'     =>  $money,
            'status'    =>  3,  //1家长支付  2老师提现  3家长退款
            'paytype'   =>  $type,  //1支付宝 2微信
            'state'     =>  1,  //1 收入  2支出
            'm_time'    => date('Y-m-d H:i:s'),
            'msg'       =>  '退款订单'.$out_trade_no,
            'order_sn'  =>  $out_trade_no,
            'm_name'    =>  $user_info['parent_name'],
            'm_phone'    =>  $user_info['phone']
        ]);
    }

    /**
     * 通过--退款申请 老师课程订单
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
                    'refunds_money' =>  $money,      //退款金额
                    'status'        =>  5
                ];
                M('Order')->where(['id'=>$application['oid']])->save($map);
                //老师的钱在每节课结束之后就给了，这里不再给钱
//                M('Teacher')->where(['id'=>$order['teacher_id']])->setInc('balance', $t_money);
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
                    'refunds_money' =>  $money,      //退款金额
                    'status'        =>  5
                ];
                M('Order')->where(['id'=>$application['oid']])->save($map);
                //老师的钱在每节课结束之后就给了，这里不再给钱
//                M('Teacher')->where(['id'=>$order['teacher_id']])->setInc('balance', $t_money);
                M('TeacherTime')->where(array('id'=>$order['teacher_time_id']))->save(array('status'=>1));
                echo 1;
            } else {
//                echo "失败";
                echo $result->alipay_trade_refund_response->sub_msg;
            }
        }


    }
}