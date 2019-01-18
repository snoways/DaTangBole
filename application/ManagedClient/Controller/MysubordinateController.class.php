<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/4
 * Time: 上午 10:21
 */

namespace ManagedClient\Controller;

use Client\Controller\UserApiBaseController;
use Think\Exception;

class MysubordinateController extends UserApiBaseController
{

    //我的下级
    public function index()
    {
        $data = $this->data;
        $id   = S($data['token']);
        $p    = $data['p']??1;
        try {
            $code = -1;
            $msg  = '不是直属代理商';
            if (M("SmallTable")->where(['id'=>$id,'s_type'=>4])->find()) {
                $xaji = M('parents')
                    ->alias('p')
                    ->page($p,10)
                    ->join('LEFT JOIN __DISTRIBUTION_RECORD__ dr ON dr.parent_id = p.id AND dr.st_id = p.st_id')
                    ->where(['p.st_id'=>$id])
                    ->field('p.id,p.p_img,p.parent_name,COALESCE(SUM(money),0) as total_money')
                    ->select();
                $code = 0;
                $msg  = 'success';
                if ($xaji[0]['id']) {
                    $info = $xaji;
                    $code = 1;
                }
            }
        } catch (Exception $e) {
            $code = -1;
            $msg  = $e->getMessage();
        }
        $this->ApiReturn($code , $msg , $info);
    }

    //帮赚详情
    public function help_earn_details()
    {
        $data = $this->data;
        $id   = S($data['token']);
        $p_id = $data['p_id'];
        if (!$p_id)  $this->ApiReturn(-1,'下级id必传',[]);
        try {
            $info['p_info'] = M('parents')->where(['id'=>$p_id])->field('p_img,parent_name')->find();
            if (!$info['p_info']) $this->ApiReturn(-1,'用户不存在',[]);
            $distribution_record = M('distribution_record');
            $online_class_order  = M('online_class_order');
            $activity_order      = M('activity_order');
            $info['p_info']['total_money'] = '0.00';
            $info['p_info']['amount_of_consumption'] = '0.00';
            $where = ['st_id'=>$id,'parent_id'=>$p_id];
            $record = $distribution_record->where($where)->field('order_id,order_type,money')->select();
            if (empty($record)) $this->ApiReturn(0,'success',$info);
            $info['p_info']['total_money'] = sprintf('%.2f',array_sum(array_column($record,'money'))??"0");
            $oco_ids = [];
            $ao_ids = [];
            foreach ($record as $value) {
                if ($value['order_type'] == 1) {
                    $oco_ids[] = $value['order_id'];
                }
                if ($value['order_type'] == 2) {
                    $ao_ids[] = $value['order_id'];
                }
            }
            unset($record);
            if (!empty($oco_ids)) {
                $amount1 = $online_class_order
                    ->where(['id'=>['in',$oco_ids],'u_type'=>3,'parent_id'=>$p_id,'status'=>2])
                    ->sum('money');
            }
            if (!empty($ao_ids)) {
                $amount2 = $activity_order
                    ->where(['id'=>['in',$ao_ids],'st_id'=>0,'uid'=>$p_id,'status'=>3])
                    ->sum('pay_money');
            }
            $info['p_info']['amount_of_consumption'] = sprintf('%.2f',$amount1 + $amount2);
            $this->detail_list($info);
        } catch (Exception $e) {
            $code = -1;
            $msg = $e->getMessage();
            $this->ApiReturn($code,$msg,$info);
        }
    }
    public function detail_list($info = [])
    {
        $data = $this->data;
        $id   = S($data['token']);
        $p_id = $data['p_id'];
        $p = !empty($info)?1:($data['p']?$data['p']:2);
        $distribution_record = M('distribution_record');
        $online_class_order  = M('online_class_order');
        $where = ['st_id'=>$id,'parent_id'=>$p_id];
        $list  = $distribution_record
            ->where($where)
            ->page($p,10)
            ->field('order_id,order_type,money,create_time')
            ->select();
        $code = 0;
        $msg = 'success';
        if ($list) {
            foreach ($list as &$item) {
                //订单类型 1线上课堂订单 2线下活动订单
                if ($item['order_type'] == 1) {
                    $online_class_info = $online_class_order
                        ->alias('oco')
                        ->join('LEFT JOIN __ONLINE_CLASS__ oc on oco.oc_id = oc.id')
                        ->where(['oco.id'=>$item['order_id'],'oco.u_type'=>3,'oco.parent_id'=>$p_id,'oco.status'=>2])
                        ->field('oco.money as order_money,oc.title')
                        ->find();
                    if ($online_class_info) $item = array_merge($item,$online_class_info);
                    unset($online_class_info);
                }
                unset($item['order_id']);
            }
            if (!empty($info)) {
                $info['list'] = $list;
            } else {
                $info = $list;
            }
            $code = 1;
        }
        $this->ApiReturn($code,$msg,$info);
    }
}