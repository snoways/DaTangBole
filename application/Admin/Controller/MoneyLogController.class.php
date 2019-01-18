<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/10/24
 * Time: 11:56
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class MoneyLogController extends AdminbaseController
{

    /**
     * 财务管理
     * sunfan
     * 2017.10.24
     */
    public function index()
    {
        $where=array();
        $request=I('request.');
        if(!empty($request['keyword'])){
            $keyword = $request['keyword'];
            $where['m_name|m_phone']=['like', "%".$keyword."%"];
        }
        if (!empty($request['start']) && !empty($request['end']))
            $where['ml.m_time'] = array('between', array($request['start'], $request['end']));
        else if (!empty($request['start']))
            $where['ml.m_time'] = array('egt', $request['start']);
        else if (!empty($request['end']))
            $where['ml.m_time'] = array('elt', $request['end']);

        $MoneyLog = M('MoneyLog');
        $count=$MoneyLog
            ->alias('ml')
            ->join('LEFT JOIN __PARENTS__ p ON ml.uid = p.id')
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $lists = $MoneyLog
            ->alias('ml')
            ->join('LEFT JOIN __PARENTS__ p ON ml.uid = p.id')
            ->where($where)
            ->order("ml.m_time DESC")
            ->field('ml.*,p.phone,p.parent_name')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        //echo $oauth_user_model->getLastSql();exit;
        $total = $MoneyLog
            ->alias('ml')
            ->join('LEFT JOIN __PARENTS__ p ON ml.uid = p.id')
            ->where($where)
            ->field("sum(money) total_money,sum(agent_money) total_agent_money,sum(platform_money) total_platform_money")
            ->find();
        $this->assign("page", $page->show('Admin'));
        $this->assign('lists', $lists);
        $this->assign('total', $total);
        $this->display();
    }

    /**
     * 财务导出
     * sunfan
     * 2017.10.24
     */
    public function oexcel1()
    {
        $where=array();
        $request=I('request.');


        if(!empty($request['keyword'])){
            $keyword = $request['keyword'];
            $where['m_name|m_phone']=['like', "%".$keyword."%"];
        }
        if (!empty($request['start']) && !empty($request['end']))
            $where['ml.m_time'] = array('between', array($request['start'], $request['end']));
        else if (!empty($request['start']))
            $where['ml.m_time'] = array('egt', $request['start']);
        else if (!empty($request['end']))
            $where['ml.m_time'] = array('elt', $request['end']);

        $oauth_user_model=M('MoneyLog');
        $lists = $oauth_user_model
            ->alias('ml')
            ->join('LEFT JOIN __PARENTS__ p ON ml.uid = p.id')
            ->where($where)
            ->order("ml.m_time DESC")
            ->field('ml.*,p.phone,p.parent_name')
            ->select();
        $str = '
		<table border="1">
		<tr style="background-color:#ccffcc;min-height:30px;">
			<th align="center">用户ID</th>
			<th align="center">用户手机号</th>
            <th align="center">用户类型</th>
            <th align="center">总付金额</th>
            <th align="center">平台所得</th>
            <th align="center">商家所得</th>
            <th align="center">交易类型</th>
            <th align="center">交易方式</th>
            <th align="center">交易时间</th>
		</tr>
		';
        foreach ($lists as $key=>&$value) {
            if ($value['state']==1){
                $str .='<tr align="center" style="background-color:#fdffcc;min-height:30px;">';
            }else{
                $str .='<tr align="center" style="min-height:30px;">';
            }
            if($value['status']==5){
                $status="课程订单支付";
            }
            if($value['status']==6){
                $status="托管课程支付";
            }
            if($value['status']==7){
                $status="线上课堂支付";
            }
            if($value['status']==8){
                $status="线下活动支付";
            }
            if($value['u_type']==1){$u_type="家长";}else{$u_type="老师";}
            if($value['paytype']==1){$paytype="支付宝";}else{$paytype="微信支付";}
            $num = 1;
            $str .= '
			<td rowspan='.$num.'>'.$value['uid'].'</td>
		    <td rowspan='.$num.'>'.$value['phone'].'</td>
			<td rowspan='.$num.'>'.$value['parent_name'].'</td>
			<td rowspan='.$num.'>'.$u_type.'</td>
			<td rowspan='.$num.'>'.$value['money'].'</td>
			<td rowspan='.$num.'>'.$value['platform_money'].'</td>
			<td rowspan='.$num.'>'.$value['agent_money'].'</td>
			<td rowspan='.$num.'>'.$status.'</td>
			<td rowspan='.$num.'>'.$paytype.'</td>
			<td rowspan='.$num.'>'.$value['m_time'].'</td>
			</tr>';
        }
        $str .="</table>";
        header('Content-Length: '.strlen($str));
        header("Content-type:application/vnd.ms-excel;charset=UTF-8");
        header("Content-Disposition:filename=凤之梦财务表".date('Y-m-d H:i:s').".xls");
        echo $str;
    }

    public function oexcel()
    {
        Vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();

        $where=array();
        $request=I('request.');

        if(!empty($request['keyword'])){
            $keyword = $request['keyword'];
            $where['m_name|m_phone']=['like', "%".$keyword."%"];
        }
        if (!empty($request['start']) && !empty($request['end']))
            $where['ml.m_time'] = array('between', array($request['start'], $request['end']));
        else if (!empty($request['start']))
            $where['ml.m_time'] = array('egt', $request['start']);
        else if (!empty($request['end']))
            $where['ml.m_time'] = array('elt', $request['end']);

        $oauth_user_model=M('MoneyLog');
        $lists = $oauth_user_model
            ->alias('ml')
            ->join('LEFT JOIN __PARENTS__ p ON ml.uid = p.id')
            ->where($where)
            ->order("ml.m_time DESC")
            ->field('ml.*,p.phone,p.parent_name')
            ->select();

        //设置默认行高

        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(15);
        $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(18);
        //水平居中
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        //垂直居中
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $objPHPExcel->getActiveSheet()->getStyle( 'A1:J1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->getActiveSheet()->getStyle( 'A1:J1')->getFill()->getStartColor()->setARGB('FFCCFFCC');
        $objPHPExcel->getActiveSheet()->setCellValue('A1', '用户ID');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', '用户姓名');
        $objPHPExcel->getActiveSheet()->setCellValue('C1', '用户手机号');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', '用户类型');
        $objPHPExcel->getActiveSheet()->setCellValue('E1', '总付金额');
        $objPHPExcel->getActiveSheet()->setCellValue('F1', '平台所得');
        $objPHPExcel->getActiveSheet()->setCellValue('G1', '商家所得');
        $objPHPExcel->getActiveSheet()->setCellValue('H1', '交易');
        $objPHPExcel->getActiveSheet()->setCellValue('I1', '方式');
        $objPHPExcel->getActiveSheet()->setCellValue('J1', '交易时间');

        foreach($lists as $k => $v){

            $num=$k+2;
            /*if (($k%2)==0){
                $objPHPExcel->getActiveSheet()->getStyle( 'A'.$num.':H'.$num)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                $objPHPExcel->getActiveSheet()->getStyle( 'A'.$num.':H'.$num)->getFill()->getStartColor()->setARGB('FFFDFFCC');
            }*/

            if($v['status']==5){
                $status="课程订单支付";
            }
            if($v['status']==6){
                $status="托管课程支付";
            }
            if($v['status']==7){
                $status="线上课堂支付";
            }
            if($v['status']==8){
                $status="线下活动支付";
            }
            if($v['u_type']==1){$u_type="家长";}else{$u_type="老师";}
            if($v['paytype']==1){$paytype="支付宝";}else{$paytype="微信支付";}


            $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
            ->setCellValue('A'.$num, $v['uid'])
                ->setCellValue('B'.$num, $v['phone'])
                ->setCellValue('C'.$num, $v['parent_name'])
                ->setCellValue('D'.$num, $u_type)
                ->setCellValue('E'.$num, $v['money'])
                ->setCellValue('F'.$num, $v['platform_money'])
                ->setCellValue('G'.$num, $v['agent_money'])
                ->setCellValue('H'.$num, $status)
                ->setCellValue('I'.$num, $paytype)
                ->setCellValue('J'.$num, $v['m_time']);
        }

        $name="凤之梦财务表".date('Y-m-d H:i:s');
        $objPHPExcel->getActiveSheet()->setTitle('User');
        $objPHPExcel->setActiveSheetIndex(0);
        header('Content-Type: applicationnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$name.'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}