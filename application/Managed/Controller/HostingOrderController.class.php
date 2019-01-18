<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/4 0004
 * Time: 下午 01:11
 */

namespace Managed\Controller;

use Think\Page;
use Think\Controller;

class HostingOrderController extends ManagedBaseController
{

    public function index()
    {
        $where=['fzm_hosting_course.st_id'=>$_SESSION['small_id']];
        $request=I('request.');

        if(!empty($request['uid'])){
            $where['id']=intval($request['uid']);
        }

        if(!empty($request['sex'])){
            $where['child_sex']=$request['sex'];
        }

        if(!empty($request['status'])){
            $where['fzm_hosting_order.status']=$request['status'];
        }

        if(!empty($request['keyword'])){
            $keyword=$request['keyword'];
            $where['sn|child_name|child_grade|title|phone'] = ['like', "%$keyword%"];
        }

        $oauth_user_model=M('HostingOrder');
        $count=$oauth_user_model
            ->join('LEFT JOIN fzm_hosting_course ON fzm_hosting_course.id = fzm_hosting_order.hc_id')
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_hosting_order.parent_id')
            ->where($where)
            ->count();
        $page = $this->page($count, 15);
        $lists = $oauth_user_model
            ->join('LEFT JOIN fzm_hosting_course ON fzm_hosting_course.id = fzm_hosting_order.hc_id')
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_hosting_order.parent_id')
            ->where($where)
            ->order("o_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('*, fzm_parents.phone as f_phone, fzm_hosting_order.status as o_status, fzm_hosting_order.id as oid , fzm_hosting_course.title')
            ->select();
        foreach ($lists as $k=>$order)
        {
            $lists[$k]['tui_status'] = -1;
            if ($order['o_status']==5)
            {
                $lists[$k]['tui_status'] = M('Application')->where(['oid'=>$order['oid'], 'o_type'=>3])->getField('status');
                $lists[$k]['st_status'] = M('Application')->where(['oid'=>$order['oid'], 'o_type'=>3])->getField('st_status');
                $lists[$k]['t_reason'] = M('Application')->where(['oid'=>$order['oid'], 'o_type'=>3])->getField('t_reason');
            }

            $lists[$k]['assess'] = M('Assess')->where(['order_id'=>$order['oid'], 'type'=>3])->find();
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

        $where=['fzm_hosting_course.st_id'=>$_SESSION['small_id']];
        $request=I('request.');

        if(!empty($request['uid'])){
            $where['id']=intval($request['uid']);
        }

        if(!empty($request['sex'])){
            $where['child_sex']=$request['sex'];
        }

        if(!empty($request['status'])){
            $where['fzm_hosting_order.status']=$request['status'];
        }

        if(!empty($request['keyword'])){
            $keyword=$request['keyword'];
            $where['sn|child_name|child_grade'] = ['like', "%$keyword%"];
        }

        $oauth_user_model=M('HostingOrder');
        $lists = $oauth_user_model
            ->join('LEFT JOIN fzm_hosting_course ON fzm_hosting_course.id = fzm_hosting_order.hc_id')
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_hosting_order.parent_id')
            ->where($where)
            ->order("o_time DESC")
            ->field('*, fzm_parents.phone as f_phone, fzm_hosting_order.id as oid , fzm_hosting_order.status as o_status')
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
			<th align="center">托管时长</th>
			<th align="center">订单总金额</th>
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
            if($value['type'] == 1){
                $time = '天';
            }else{
                $time = '个月';
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
			<td rowspan='.$num.'>'.$value['time_length'].$time.'</td>
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
        M('HostingOrder')->where(array('id'=>$id))->save(['status'=>3]);
        echo json_encode(['code'=>1]);
        exit();
    }

    //审核
    public function status()
    {
        $id = $_REQUEST['id'];
        $status = $_REQUEST['status'];  //1.审核通过 2.审核不通过
        if ($status==1){
            M('Application')->where(array('oid'=>$id, 'o_type'=>3))->save(['st_status'=>2]);

            $HostingOrder = D("HostingOrder");
            $parents = D("parents");
            $SmallTable = D("SmallTable");



            echo json_encode(['code'=>1]);
            exit();
        }else{
            M('Application')->where(array('oid'=>$id, 'o_type'=>3))->save(['status'=>3]);
            echo json_encode(['code'=>1]);
            exit();
        }
    }
}