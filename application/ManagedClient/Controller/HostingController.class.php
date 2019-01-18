<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/4 0004
 * Time: 下午 05:20
 */

namespace ManagedClient\Controller;

use Client\Controller\UserApiBaseController;

class HostingController extends UserApiBaseController
{
    public function index()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $where['status'] = ['neq', 3];
        $where['st_id'] = $id;

        $oauth_user_model=M('HostingCourse');
        $lists = $oauth_user_model
            ->where($where)
            ->order("add_time DESC")
            ->field('id as hc_id, title, img, month_price')
            ->page($page)
            ->select();

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
        $title = !empty($data['title'])?$data['title']:$this->ApiReturn(-1, '标题不能为空');
        $img = !empty($data['img'])?$data['img']:$this->ApiReturn(-1, '缩略图不能为空');
        $month_price = !empty($data['month_price'])?$data['month_price']:$this->ApiReturn(-1, '总资费不能为空');
        $day_price = !empty($data['day_price'])?$data['day_price']:$this->ApiReturn(-1, '每天资费不能为空');
        $tui_price = !empty($data['tui_price'])?$data['tui_price']:$this->ApiReturn(-1, '退款资费不能为空');
        $no_days = !empty($data['no_days'])?$data['no_days']:$this->ApiReturn(-1, '不能退款天数不能为空');
        $content = !empty($data['content'])?$data['content']:$this->ApiReturn(-1, '服务详情介绍不能为空');
        $refunds = !empty($data['refunds'])?$data['refunds']:$this->ApiReturn(-1, '退款说明不能为空');


        M('HostingCourse')->add([
            'st_id'     =>  $id,
            'title'     =>  $title,
            'img'       =>  $img,
            'month_price'=> $month_price,
            'day_price' =>  $day_price,
            'tui_price' =>  $tui_price,
            'no_days'   =>  $no_days,
            'content'   =>  $content,
            'add_time'   =>  date('Y-m-d H:i:s'),
            'refunds'   =>  $refunds
        ]);
        $this->ApiReturn(1, '添加成功');
    }


    public function del()
    {
        $data = $this->data;
        $id = S($data['token']);
        $hc_id = !empty($data['hc_ids'])?$data['hc_ids']:$this->ApiReturn(-1, 'id不能为空');
        M('HostingCourse')->where(['id'=>['in', $hc_id], 'st_id'=>$id])->save(['status'=>3]);
        $this->ApiReturn(1, '删除成功');
    }

    public function getDetail()
    {
        $data = $this->data;
        $id = S($data['token']);
        $hc_id = !empty($data['hc_id'])?$data['hc_id']:$this->ApiReturn(-1, 'id不能为空');
        $rs = M('HostingCourse')
            ->where(['id'=>$hc_id, 'st_id'=>$id])
            ->field('id as hc_id, title, img, month_price, day_price,tui_price, no_days, content, refunds')
            ->find();

        //云信id
        $rs['accid'] = "s".$rs['st_id'];
        $this->ApiReturn(1, 'success', $rs);
    }

    public function edit()
    {
        $data = $this->data;
        $id = S($data['token']);
        $hc_id = !empty($data['hc_id'])?$data['hc_id']:$this->ApiReturn(-1, 'id不能为空');
        $title = !empty($data['title'])?$data['title']:$this->ApiReturn(-1, '标题不能为空');
        $img = !empty($data['img'])?$data['img']:$this->ApiReturn(-1, '缩略图不能为空');
        $month_price = !empty($data['month_price'])?$data['month_price']:$this->ApiReturn(-1, '总资费不能为空');
        $day_price = !empty($data['day_price'])?$data['day_price']:$this->ApiReturn(-1, '每天资费不能为空');
        $tui_price = !empty($data['tui_price'])?$data['tui_price']:$this->ApiReturn(-1, '退款资费不能为空');
        $no_days = !empty($data['no_days'])?$data['no_days']:$this->ApiReturn(-1, '不能退款天数不能为空');
//        $content = !empty($data['content'])?$data['content']:$this->ApiReturn(-1, '服务详情介绍不能为空');
        $refunds = !empty($data['refunds'])?$data['refunds']:$this->ApiReturn(-1, '退款说明不能为空');

        M('HostingCourse')->where(['id'=>$hc_id])->save([
            'title'     =>  $title,
            'img'       =>  $img,
            'month_price'=> $month_price,
            'day_price' =>  $day_price,
            'tui_price' =>  $tui_price,
            'no_days'   =>  $no_days,
//            'content'   =>  $content,
            'refunds'   =>  $refunds
        ]);
        $this->ApiReturn(1, '修改成功');
    }

}