<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/4 0004
 * Time: 上午 10:21
 */

namespace ManagedClient\Controller;

use Client\Controller\UserApiBaseController;

class ActivityController extends UserApiBaseController
{

    public function index()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $type = $data['type']??1; //1.进行中的 2.已结束的

        $now = date('Y-m-d H:i:s');
        if ($type==2){
            $where['end_time'] = ['lt', $now];
        }else{
            $where['end_time'] = ['egt', $now];
        }
        $where['a.status'] = ['neq', 3];
        $where['shop_id'] = $id;

        $oauth_user_model=M('Activity');
        $lists = $oauth_user_model
            ->alias('a')
            ->join('LEFT JOIN fzm_activity_cate ac ON ac.id=a.cate_id')
            ->where($where)
            ->order("add_time DESC")
            ->page($page)
            ->field('a.id as aid, a.title, a.img, a.people_num, a.now_num, a.end_time, a.money, a.content, ac.name as cate_name')
            ->select();
        foreach($lists as $k=>$v){
            if($now > $v['end']){
                $oauth_user_model->where(['id'=>$v['id']])->save(['state'=>2]);
            }
        }

        //调取活动结束后返送给商家余额
//        activity_managed();

        if (!$lists){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $lists);
        }
    }

    /**
     * 获取活动分类
     */
    public function getActivityCate()
    {
        $lists = M('ActivityCate')->field('id as cate_id, name')->select();
        if (!$lists){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $lists);
        }
    }

    public function add()
    {
        $data = $this->data;
        $id = S($data['token']);
        $title = $data['title']??$this->ApiReturn(-1, '标题不能为空');
        $img = $data['img']??$this->ApiReturn(-1, '缩略图不能为空');
        $cate_id = $data['cate_id']??$this->ApiReturn(-1, '分类id不能为空');
        $money = $data['money']??$this->ApiReturn(-1, '费用不能为空');
        $people_num = $data['people_num']??$this->ApiReturn(-1, '活动人数不能为空');
        $end_time = $data['end_time']??$this->ApiReturn(-1, '截止日期不能为空');
        $content = $data['content']??$this->ApiReturn(-1, '服务详情介绍不能为空');
        $address = $data['address']??$this->ApiReturn(-1, '活动地址不能为空');
        $a_xy = $data['a_xy']??$this->ApiReturn(-1, '活动地址坐标不能为空');
        $start = $data['start']??$this->ApiReturn(-1, '活动时间不能为空');
        $end = $data['end']??$this->ApiReturn(-1, '活动时间不能为空');
        M('Activity')->add([
            'shop_id'   =>  $id,
            'title'     =>  $title,
            'img'       =>  $img,
            'cate_id'   =>  $cate_id,
            'money'     =>  $money,
            'people_num'=>  $people_num,
            'end_time'  =>  $end_time,
            'content'   =>  $content,
            'address'   =>  $address,
            'a_xy'      =>  $a_xy,
            'start'      =>  $start,
            'end'      =>  $end,
            'add_time'  =>  date('Y-m-d H:i:s'),
        ]);
        $this->ApiReturn(1, '添加成功');
    }

    public function del()
    {
        $data = $this->data;
        $id = S($data['token']);
        $aid = !empty($data['aid'])?$data['aid']:$this->ApiReturn(-1, 'id不能为空');
        $find = M('ActivityOrder')->where(array('activity_id'=>$aid, 'status'=>['in', [1,2,3]]))->find();
        if($find){
            $this->ApiReturn(-1, '该活动已有人报名，不能删除');
        }else{
            $del = M('Activity')->where(array('id'=>$aid, 'shop_id'=>$id))->save(['status'=>3]);
            $this->ApiReturn(1, 'success');
        }
    }

    public function getDetail()
    {
        $data = $this->data;
        $id = S($data['token']);
        $aid = $data['aid']??$this->ApiReturn(-1, 'id不能为空');
        $rs = M('Activity')
            ->join('LEFT JOIN fzm_activity_cate ac ON ac.id=fzm_activity.cate_id')
            ->where(['fzm_activity.id'=>$aid])
            ->field('fzm_activity.id as aid, title, img, ac.id as cate_id, ac.name as cate_name, money, people_num, now_num, end_time, content, address, video_cover, video_url, start, end')
            ->find();
        $this->ApiReturn(1, 'success', $rs);
    }

    public function edit()
    {
        $data = $this->data;
        $id = S($data['token']);
        $aid = $data['aid']??$this->ApiReturn(-1, '活动id不能为空');

        $find = M('ActivityOrder')->where(array('activity_id'=>$aid, 'status'=>['in', [1,2,3]]))->find();
        if($find){
            $this->ApiReturn(-1, '该活动已有人报名，不能编辑');
        }

        $title = $data['title']??$this->ApiReturn(-1, '标题不能为空');
        $img = $data['img']??$this->ApiReturn(-1, '缩略图不能为空');
        $cate_id = $data['cate_id']??$this->ApiReturn(-1, '分类id不能为空');
        $money = $data['money']??$this->ApiReturn(-1, '费用不能为空');
        $people_num = $data['people_num']??$this->ApiReturn(-1, '活动人数不能为空');
        $end_time = $data['end_time']??$this->ApiReturn(-1, '截止日期不能为空');
//        $content = $data['content']??$this->ApiReturn(-1, '服务详情介绍不能为空');
        $address = $data['address']??$this->ApiReturn(-1, '活动地址不能为空');
        $a_xy = $data['a_xy']??$this->ApiReturn(-1, '活动地址坐标不能为空');
        $start = $data['start']??$this->ApiReturn(-1, '活动时间不能为空');
        $end = $data['end']??$this->ApiReturn(-1, '活动时间不能为空');
        M('Activity')->where(['id'=>$aid, 'shop_id'=>$id])->save([
            'title'     =>  $title,
            'img'       =>  $img,
            'cate_id'   =>  $cate_id,
            'money'     =>  $money,
            'people_num'=>  $people_num,
            'end_time'  =>  $end_time,
//            'content'   =>  $content,
            'address'   =>  $address,
            'a_xy'      =>  $a_xy,
            'start'      =>  $start,
            'end'      =>  $end,
        ]);
        $this->ApiReturn(1, '修改成功');
    }

    //活动上架、下架
    public function status()
    {
        $id = $_REQUEST['id'];
        $status = $_REQUEST['status'];  //活动当前状态 1.上架 2.下架 3.删除
        $find = M('ActivityOrder')->where(array('activity_id'=>$id, 'status'=>['in', [1,2,3]]))->find();
        if ($status==1){
            //去下架
            if($find){
                echo json_encode(['code'=>-1, 'msg'=>"该活动已有人报名，不能下架"]);
                exit();
            }else{
                M('Activity')->where(array('id'=>$id))->save(['status'=>2]);
                echo json_encode(['code'=>1]);
                exit();
            }
        }else{
            //去上架
            M('Activity')->where(array('id'=>$id))->save(['status'=>1]);
            echo json_encode(['code'=>1]);
            exit();
        }
    }


    public function orderList()
    {
        $data = $this->data;
        $id = S($data['token']);
        $aid = $data['aid']??$this->ApiReturn(-1, '活动id不能为空');
        $status = $data['status']??1;  //1.未支付2.支付成功3.申请退款
        $page = $data['page']??1;
        $db = M('ActivityOrder');
        $where['activity_id'] = $aid;
        $where['o.status'] =  $status;
        $list = $db
            ->alias('o')
            ->join('left join fzm_parents p on p.id = o.uid')
            ->where($where)
            ->order('sign_time desc')
            ->page($page)
            ->field('o.id as oid, o.sign_time, o.money, o.refund_money, o.now_refund_money, o.now_refund_num ,o.status, o.sn, p.parent_name, p.phone')
            ->select();

        $db2 = M('ActOrderDetail');
        $config = M('Config')->find();
        foreach ($list as $k=>$item){
            $list[$k]['pay_money'] = $item['money'];
            $list[$k]['list'] = $db2->where(['order_id'=>$item['oid'],'status'=>1])->field('member_name, member_phone, status')->select();
            $list[$k]['count'] = count($list[$k]['list']);
            $list[$k]['tui_count'] = 0;
            $list[$k]['refund_status'] = 0;
            if ($item['status']==3){
                $list[$k]['tui_count'] = $item['now_refund_num'];
                $list[$k]['tui_money'] = $item['now_refund_money']*(1-$config['activity_radio']);
                $list[$k]['refund_status'] = $db->where(['id'=>$item['oid']])->getField('refund_status');
                $list[$k]['t_reason'] = $db->where(['id'=>$item['oid']])->getField('reason');
            }

        }

        $res['count1'] = $db
            ->alias('o')
            ->join('left join fzm_parents p on p.id = o.uid')
            ->where(['activity_id'=>$aid, 'o.status'=>1])
            ->count();
        $res['count2'] = $db
            ->alias('o')
            ->join('left join fzm_parents p on p.id = o.uid')
            ->where(['activity_id'=>$aid, 'o.status'=>2])
            ->count();
        $res['count3'] = $db
            ->alias('o')
            ->join('left join fzm_parents p on p.id = o.uid')
            ->where(['activity_id'=>$aid, 'o.status'=>3])
            ->count();
        $res['list'] = $list;
        if (!$list){
            $this->ApiReturn(0, 'success', $res);
        }else{
            $this->ApiReturn(1, 'success', $res);
        }

    }

    public function refund()
    {
        $data = $this->data;
        $id = S($data['token']);
        $oid = !empty($data['oid'])?$data['oid']:$this->ApiReturn(-1, '订单id不能为空');
        $order = M('ActivityOrder')->where(['id'=>$oid])->find();
        if ($order['status']!=3){
            $this->ApiReturn(1, '非法操作');
        }

        M('ActivityOrder')->where(['id'=>$oid])->save(['refund_status'=>3]);
        $this->ApiReturn(1, '审核成功，等待后台退款');
    }

    public function upVideo()
    {
        $data = $this->data;
        $st_id = $data['st_id'];
//        $where['a.status'] = ['neq', 3];
        !empty($data['aid']) ? $where['id'] = $data['aid'] : $this->ApiReturn(-1, '活动id不能为空');
        $map['video_cover'] = $data['video_cover']??$this->ApiReturn(-1, '视频封面不能为空');
        $map['video_url'] = $data['video_url']??$this->ApiReturn(-1, '视频不能为空');
        M('Activity')->where($where)->save($map);
        $this->ApiReturn(1, '上传成功');
    }

}