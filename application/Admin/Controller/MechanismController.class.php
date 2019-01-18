<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/23
 * Time: 14:12
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MechanismController extends AdminbaseController
{
//列表页
    public function index()
    {
        $where = array();
        if($this->role=='agent'){
            $where['areaId'] = array(
                'in',
                D('Users')->where(array('id'=>$this->uid))->getField('areacode')
                );
        }
        if($_POST['status'] >0){
           $where['status'] = $_POST['status'];
        }
        if (!empty($_POST['start']) && !empty($_POST['end']))
            $where['m_time'] = array('between', array($_POST['start'], $_POST['end']));
        else if (!empty($_POST['start']))
            $where['m_time'] = array('egt', $_POST['start']);
        else if (!empty($_POST['end']))
            $where['m_time'] = array('elt', $_POST['end']);

        $count = M('Mechanism')->order('m_time desc')->where($where)->count();
        $page = $this->page($count, 20);
        $list = M('Mechanism')->order('m_time desc')->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        $_SESSION['list'] = $list;

        $where['status'] = 1;
        $stacount = M('Mechanism')->where($where)->count();
        $this->assign("count",$stacount);

        $this->assign("list",$list);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }
 //改变状态
    public function change()
    {
        $id = $_GET['id'];
        $update = M('Mechanism')->where(array('id'=>$id))->save(array('status'=>2));
        $this->success("成功");
    }

    /**
     * 导出EXCEL
     * sunfan
     *  2017.12.28
     */
    public function mexcel() {

        $lists =$_SESSION['list'];
        $str = '
		<table border="1">
		<tr style="background-color:#ccffcc;min-height:30px;">
			<th width="120" align="center">名称</th>
			<th align="center">电话</th>
			<th align="center">地址</th>
			<th align="center">内容</th>
			<th align="center">申请时间</th>
			<th align="center">状态</th>
		</tr>
		';
        foreach ($lists as $key=>&$value) {
            if (($key%2)==0){
                $str .='<tr align="center" style="background-color:#fdffcc;min-height:30px;">';
            }else{
                $str .='<tr align="center" style="min-height:30px;">';
            }
            $status = $value['status']==1?'未处理':'已处理';
            $num = 1;
            $str .= '
			<td width="120" rowspan='.$num.'>'.$value['name'].'</td>
			<td rowspan='.$num.'>'.$value['phone'].'</td>
			<td rowspan='.$num.'>'.$value['address'].'</td>
			<td rowspan='.$num.'>'.$value['content'].'</td>
			<td rowspan='.$num.'>'.$value['m_time'].'</td>
			<td rowspan='.$num.'>'.$status.'</td>
			</tr>';
        }
        $str .="</table>";
        header('Content-Length: '.strlen($str));
        header("Content-type:application/vnd.ms-excel;charset=UTF-8");
        header("Content-Disposition:filename=合作申请表".date('Y-m-d H:i:s').".xls");
        echo $str;
    }

}