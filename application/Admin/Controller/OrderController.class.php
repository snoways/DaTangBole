<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/10/23
 * Time: 15:02
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class OrderController extends AdminbaseController
{
    /**
     * 订单管理
     * sunfan
     * 2017.10.24
     */
    public function index()
    {
        $where=array();
        if($this->role=='agent'){
            $areas = D('Users')->where(array('id'=>$this->uid))->getField('areacode');
            $where['t.areaId'] = array('in',$areas);
        }
        $request=I('request.');

        if(!empty($request['uid'])){
            $where['id']=intval($request['uid']);
        }

        if(!empty($request['sex'])){
            $where['child_sex']=$request['sex'];
        }

        if(!empty($request['status'])){
            $where['fzm_order.status']=$request['status'];
        }

        if(!empty($request['keyword'])){
            $keyword=$request['keyword'];
            $where['sn|child_name|name|province|city|area|fzm_order.address|child_grade'] = ['like', "%$keyword%"];
        }

        $oauth_user_model=M('Order');
        $count=$oauth_user_model
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_order.parent_id')
            ->join('LEFT JOIN fzm_teacher as t ON t.id=fzm_order.teacher_id')
            ->where($where)
            ->count();
        $total=$oauth_user_model
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_order.parent_id')
            ->join('LEFT JOIN fzm_teacher as t ON t.id=fzm_order.teacher_id')
            ->where($where)
            ->sum('total_money');
        $this->assign('total',$total?$total:'0.00');
        $page = $this->page($count, 20);
        $lists = $oauth_user_model
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_order.parent_id')
            ->join('LEFT JOIN fzm_teacher as t ON t.id=fzm_order.teacher_id')
            ->where($where)
            ->order("start_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('*, fzm_parents.phone as f_phone, fzm_order.status as o_status, t.phone as t_phone, fzm_order.address as o_address, fzm_order.id as oid')
            ->select();
        foreach ($lists as $k=>$order)
        {
            $lists[$k]['tui_status'] = -1;
            if ($order['o_status']==5)
            {
                $lists[$k]['tui_status'] = M('Application')->where(['oid'=>$order['oid']])->getField('status');
            }
        }

        $status = [-1=>'待确认',1=>'待支付',2=>'进行中',3=>'已完成',4=>'已取消',5=>'已退款'];
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

        $where=array();
        $request=I('request.');

        if(!empty($request['uid'])){
            $where['id']=intval($request['uid']);
        }

        if(!empty($request['sex'])){
            $where['child_sex']=$request['sex'];
        }

        if(!empty($request['keyword'])){
            $keyword=$request['keyword'];
            $where['sn|child_name|name|province|city|area|fzm_order.address|child_grade'] = ['like', "%$keyword%"];
        }

        $oauth_user_model=M('Order');
        $lists = $oauth_user_model
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_order.parent_id')
            ->join('LEFT JOIN fzm_teacher as t ON t.id=fzm_order.teacher_id')
            ->where($where)
            ->order("start_time DESC")
            ->field('*, fzm_parents.phone as f_phone, fzm_order.status as o_status, t.phone as t_phone ,fzm_order.id as oid')
            ->select();
        foreach ($lists as $k=>$order)
        {
            $lists[$k]['tui_status'] = -1;
            if ($order['o_status']==5)
            {
                $lists[$k]['tui_status'] = M('Application')->where(['oid'=>$order['oid']])->getField('status');
            }
        }
        $str = '
		<table border="1">
		<tr style="background-color:#ccffcc;min-height:30px;">
			<th width="120" align="center">订单编号</th>
			<th align="center">学生姓名</th>
			<th align="center">学生性别</th>
			<th align="center">会员手机号</th>
			<th align="center">上课老师</th>
			<th align="center">老师手机号</th>
			<th align="center">科目</th>
			<th align="center">地区</th>
			<th align="center">总课时</th>
			<th align="center">课时费/h</th>
			<th align="center">总课时费</th>
			<th align="center">老师实际应得</th>
			<th align="center">平台应得金额</th>
			<th align="center">订单状态</th>
		</tr>
		';
        foreach ($lists as $key=>&$value) {
            if (($key%2)==0){
                $str .='<tr align="center" style="background-color:#fdffcc;min-height:30px;">';
            }else{
                $str .='<tr align="center" style="min-height:30px;">';
            }
            if($value['o_status']==-1){
                $status =  "待老师确认";
            }elseif($value['o_status']==1){
                $status =  "待支付";
            }elseif($value['o_status']==2){
                $status = "上课中";
            }elseif($value['o_status']==2){
                $status = "上课中";
            }elseif($value['o_status']==3){
                $status =  "已完成";
            }elseif($value['o_status']==4){
                $status =  "已取消";
            }elseif($value['o_status']==5){
                if($value['tui_status']==1){
                    $status =  "退款审核中";
                }elseif($value['tui_status']==2){
                    $status =  "已退款";
                }else{
                    $status =  "退款申请被驳回";
                }
            }
            $sex = $value['child_sex']==1?'男':'女';
            $num = 1;
            $str .= '
			<td width="120" rowspan='.$num.'>'.$value['sn'].'</td>
			<td rowspan='.$num.'>'.$value['child_name'].'</td>
			<td rowspan='.$num.'>'.$sex.'</td>
			<td rowspan='.$num.'>'.$value['f_phone'].'</td>
			<td rowspan='.$num.'>'.$value['name'].'</td>
			<td rowspan='.$num.'>'.$value['t_phone'].'</td>
			<td rowspan='.$num.'>'.$value['subject'].'</td>
			<td rowspan='.$num.'>'.$value['province'].$value['city'].$value['area'].$value['address'].'</td>
			<td rowspan='.$num.'>'.$value['class_num'].'</td>
			<td rowspan='.$num.'>'.$value['o_price'].'</td>
			<td rowspan='.$num.'>'.$value['total_money'].'</td>
			<td rowspan='.$num.'>'.$value['money'].'</td>
			<td rowspan='.$num.'>'.$value['platform_money'].'</td>
			<td rowspan='.$num.'>'.$status.'</td>
			</tr>';
        }
        $str .="</table>";
        header('Content-Length: '.strlen($str));
        header("Content-type:application/vnd.ms-excel;charset=UTF-8");
        header("Content-Disposition:filename=凤之梦订单表".date('Y-m-d H:i:s').".xls");
        echo  $str;
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

}