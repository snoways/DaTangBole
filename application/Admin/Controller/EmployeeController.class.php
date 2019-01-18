<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/28 0028
 * Time: 上午 09:04
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class EmployeeController extends AdminbaseController
{

    /**
     * 员工管理
     * 2018.6.28
     */
    public function index()
    {
        $db = M('Employee');
        $where = array();
        /*if($this->role=='agent'){
            $where['a.agent_id'] = $this->uid;
        }*/
        if($key = I('post.keyword')){
            $where['e_name|e_phone|e_num'] = array('like','%'.$key.'%');
        }
        if (I('status')){
            $where['e_status'] = I('status');
        }
        if (I('start_time') && I('end_time')){
            $where['e_time'] = ['between', [I('start_time'), I('end_time')]];
        }
        if ($sort = I('sort')){
            $order['count'.$sort] = "desc";
        }else{
            $order['e_time'] = "desc";
        }
        $count = $db
            ->where($where)
            ->count();
        $page = $this->page($count,12);
        $list = $db
            ->where($where)
            ->order($order)
            ->limit($page->firstRow.','.$page->listRows)
            ->select();


        $arr = [];
        $trr = [];
        //实例化表
        $teacher = D("teacher");
        $small_table = D("SmallTable");
        $online = D("online_class_order");
        $order = D("order");

        $ids = '';      //老师id集合
        $tab_ids = '';      //机构和托管id集合
        foreach($list as $k=>$v){
            //查找老师id
            $teacher_id[$k] = $teacher->where(['empnum'=>$v['e_num']])->field('id')->select();
            //查找机构和托管id
            $small_table_id[$k] = $small_table->where(['empnum'=>$v['e_num']])->field('id')->select();
            if(!empty($teacher_id[$k])){
                //进行老师id重组
                foreach($teacher_id[$k] as $n=>$c){
                    if($n == 0){
                        $ids = $c['id'];
                    }else{
                        $ids .= ','.$c['id'];
                    }
                }
                $list[$k]['ids'] = $ids;
            }else{
                $list[$k]['ids'] = '';
            }
            if(!empty($small_table_id[$k])){
                //进行老师id重组
                foreach($small_table_id[$k] as $a=>$w){
                    if($a == 0){
                        $tab_ids = $w['id'];
                    }else{
                        $tab_ids .= ','.$w['id'];
                    }
                }
                $list[$k]['tab_ids'] = $tab_ids;
            }else{
                $list[$k]['tab_ids'] = '';
            }

        }
        //线上课堂
        $result = $this->online_order($list);
        //普通订单
        $result1 = $this->teacher_order($result);
        //线下活动订单
        $result2 = $this->activity_order($result1);
        //教育机构订单
        $result3 = $this->educational_order($result2);
        //托管班订单
        $result4 = $this->hosting_order($result3);

        foreach($result4 as $n=>$c){
            $result4[$n]['teacher_money'] = $c['online_money'] + $c['order_money'];
            $result4[$n]['smalltable_money'] = $c['online_money1'] + $c['activity_money'] + $c['educational_money'] + $c['hosting_money'];
            unset($result4[$n]['online_money']);
            unset($result4[$n]['activity_money']);
            unset($result4[$n]['online_money1']);
            unset($result4[$n]['educational_money']);
            unset($result4[$n]['hosting_money']);
            unset($result4[$n]['order_money']);

        }

        $this->assign('list',$result4);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }

    public function status()
    {
        $id = I('id');
        $status = I('status');
        M('Employee')->where(['id'=>$id])->save(['e_status'=>$status]);
        $this->success('操作成功');
    }

    public function add()
    {
        if (IS_POST){
            $tel = I('e_phone');//手机号码
            if(strlen($tel) != "11")
            {
                $this->error('手机号码不正确');
            }else{
                if (M('Employee')->where(['e_phone'=>$tel])->find()){
                    $this->error('已存在相同手机号的员工');
                }
                $id = M('Employee')->add([
                    'e_name'     =>  I('e_name'),
                    'e_phone'   =>  $tel,
                    'e_time'    =>  date('Y-m-d H:i:s')
                ]);
                if ($id){
                    $num = sp_random_string().$id;
                    $url = C('WEBHOST')."/fzh_teacherweb/twoqi/sign.html?empnum=".$num;
                    $img = qrcode($url, $num);
                    M('Employee')->where(['id'=>$id])->save(['e_num'=>$num, 'e_img'=>$img]);
                    $this->success('添加成功');
                }else{
                    $this->error('网络错误，请稍后再试');
                }
            }
        }
        $this->display();
    }

    public function edit()
    {
        $id = I('id');
        if (IS_POST){
            $tel = I('e_phone');//手机号码
            if(strlen($tel) != "11")
            {
                $this->error('手机号码不正确');
            }else{
                if (M('Employee')->where(['e_phone'=>$tel, 'id'=>['neq', $id]])->find()){
                    $this->error('已存在相同手机号的员工');
                }
                $id = M('Employee')->where(['id'=>$id])->save([
                    'e_name'     =>  I('e_name'),
                    'e_phone'   =>  $tel
                ]);
                $this->success('修改成功');
            }
        }
        $rs = M('Employee')->where(['id'=>$id])->find();
        $this->assign('rs', $rs);
        $this->display();
    }

    /**
     * 导出EXCEL
     * sunfan
     *  2017.10.24
     */
    public function oexcel() {

        $db = M('Employee');
        $where = array();
        /*if($this->role=='agent'){
            $where['a.agent_id'] = $this->uid;
        }*/
        if($key = I('post.keyword')){
            $where['e_name|e_phone'] = array('like','%'.$key.'%');
        }
        if (I('status')){
            $where['e_status'] = I('status');
        }
        if (I('start_time') && I('end_time')){
            $where['e_time'] = ['between', [I('start_time'), I('end_time')]];
        }
        if ($sort = I('sort')){
            $order['count'.$sort] = "desc";
        }else{
            $order['e_time'] = "desc";
        }
        $list = $db
            ->where($where)
            ->order($order)
            ->select();
        $str = '
		<table border="1">
		<tr style="background-color:#ccffcc;min-height:30px;">
			<th width="120" align="center">姓名</th>
			<th align="center">手机号</th>
			<th align="center">员工编号</th>
			<th align="center">员工二维码</th>
			<th align="center">总注册人数</th>
			<th align="center">注册家长人数</th>
			<th align="center">注册老师人数</th>
			<th align="center">注册机构人数</th>
			<th align="center">创建时间</th>
			<th align="center">状态</th>
		</tr>
		';
        foreach ($list as $key=>&$value) {
            if (($key%2)==0){
                $str .='<tr align="center" style="background-color:#fdffcc;min-height:30px;">';
            }else{
                $str .='<tr align="center" style="min-height:30px;">';
            }
            $e_status = $value['e_status']==1?'正常':'冻结';
            $num = 1;
            $str .= '
			<td align="center" width="120" rowspan='.$num.'>'.$value['e_name'].'</td>
			<td align="center" rowspan='.$num.'>'.$value['e_phone'].'</td>
			<td align="center" rowspan='.$num.'>'.$value['e_num'].'</td>
			<td align="center" width="250" height="150" rowspan='.$num.'><img src="'.C('WEBHOST').$value['e_img'].'" width="120"></td>
			<td align="center" rowspan='.$num.'>'.$value['count1'].'</td>
			<td align="center" rowspan='.$num.'>'.$value['count2'].'</td>
			<td align="center" rowspan='.$num.'>'.$value['count3'].'</td>
			<td align="center" rowspan='.$num.'>'.$value['count4'].'</td>
			<td align="center" rowspan='.$num.'>'.$value['e_time'].'</td>
			<td align="center" rowspan='.$num.'>'.$e_status.'</td>
			</tr>';
        }
        $str .="</table>";
        header('Content-Length: '.strlen($str));
        header("Content-type:application/vnd.ms-excel;charset=UTF-8");
        header("Content-Disposition:filename=员工表".date('Y-m-d H:i:s').".xls");
        echo $str;
    }

    public function parents(){
        $where=array();
        $request=I('request.');

        if(!empty($request['num'])){
            $where['empnum']=$request['num'];
        }

        $users_model=M("Parents");

        $count=$users_model->where($where)->count();
        $page = $this->page($count, 20);

        $list = $users_model
            ->alias('u')
            ->join('LEFT JOIN fzm_level l ON l.id=u.level_id')
            ->where($where)
            ->order("p_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('u.*, l.name as level_name')
            ->select();

        $this->assign('list', $list);
        $this->assign("page", $page->show('Admin'));

        $this->display();
    }

    public function teacher(){
        $where=array();

        $request=I('request.');
        if(!empty($request['num'])){
            $where['empnum']=$request['num'];
        }

        $oauth_user_model=M('Teacher');
        $count=$oauth_user_model
            ->alias('u')
            ->join('LEFT JOIN fzm_level l ON l.id=u.level_id')
            ->where($where)
            ->count();
        $page = $this->page($count, 20);
        $lists = $oauth_user_model
            ->alias('u')
            ->join('LEFT JOIN fzm_level l ON l.id=u.level_id')
            ->where($where)
            ->order("sort ASC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('u.*, l.name as level_name')
            ->select();

        $this->assign("page", $page->show('Admin'));
        $this->assign('lists', $lists);
        $this->display();
    }

    public function shop()
    {
        $request=I('request.');
        if(!empty($request['num'])){
            $where['empnum']=$request['num'];
        }
        $count = M('small_table')->where($where)->count();
        $page=$this->page($count,10);
        $list = M('small_table')
            ->alias('st')
            ->join('LEFT JOIN fzm_level l ON l.id=st.level_id')
            ->where($where)
            ->order('s_time desc')
            ->limit($page->firstRow, $page->listRows)
            ->field('st.*, l.name as level_name')
            ->select();
        $this->assign("list",$list);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }


    //老师/机构 线上课堂订单统计
    public function online_order($list){
        $OnlineClassOrder = D("OnlineClassOrder");
        foreach($list as $k=>$v){
            //老师
            $where['u_type'] = 1;
            $where['status'] = 2;
            $where['tid'] = ['in',$v['ids']];
            $money = $OnlineClassOrder->where($where)->sum('money');
            if($money){
                $list[$k]['online_money'] = $money;
            }else{
                $list[$k]['online_money'] = 0.00;
            }
            //机构
            $where1['u_type'] = 2;
            $where1['status'] = 2;
            $where1['tid'] = ['in',$v['tab_ids']];
            $money1 = $OnlineClassOrder->where($where1)->sum('money');

            if($money1){
                $list[$k]['online_money1'] = $money1;
            }else{
                $list[$k]['online_money1'] = 0.00;
            }
        }
        return $list;
    }
    //老师订单 order
    public function teacher_order($list){
        $order = D("Order");
        $where['status'] = 3;
        foreach($list as $k=>$v){
            $where['teacher_id'] = ['in',$v['ids']];
            $money = $order->where($where)->sum("money");
            if($money){
                $list[$k]['order_money'] = $money;
            }else{
                $list[$k]['order_money'] = 0.00;
            }
        }
        return $list;
    }
    //线下活动订单 activity_order
    public function activity_order($list){
        $activity_order = D("ActivityOrder");
        $where['status'] = 2;
        foreach($list as $k=>$v){
            $where['uid'] = ['in',$v['tab_ids']];
            $money = $activity_order->where($where)->sum("pay_money");
            if($money){
                $list[$k]['activity_money'] = $money;
            }else{
                $list[$k]['activity_money'] = 0.00;
            }
        }
        return $list;
    }
    //教育机构订单
    public function educational_order($list){
        $educational_order = D("EducationalOrder");
        $where['status'] = 3;
        foreach($list as $k=>$v){
            $where['st_id'] = ['in',$v['tab_ids']];
            $money = $educational_order->where($where)->sum("money");
            if($money){
                $list[$k]['educational_money'] = $money;
            }else{
                $list[$k]['educational_money'] = 0.00;
            }
        }
        return $list;
    }
    //托管班订单 hosting_order
    public function hosting_order($list){
        $hosting_order = D("HostingOrder");
        $where['status'] = 3;
        foreach($list as $k=>$v){
            $where['st_id'] = ['in',$v['tab_ids']];
            $money = $hosting_order->where($where)->sum("money");
            if($money){
                $list[$k]['hosting_money'] = $money;
            }else{
                $list[$k]['hosting_money'] = 0.00;
            }
        }
        return $list;
    }





}