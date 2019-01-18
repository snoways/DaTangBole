<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/4 0004
 * Time: 下午 01:11
 */

namespace ManagedClient\Controller;


use Client\Controller\UserApiBaseController;

class OrderController extends UserApiBaseController
{

    //教育机构订单

    public function index()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $where=['o.st_id'=>$id];
        $where['o.status'] = !empty($data['status'])?$data['status']:2;

        $oauth_user_model=M('EducationalOrder');
        $lists = $oauth_user_model
            ->alias('o')
            ->join('LEFT JOIN fzm_parents p ON p.id=o.parent_id')
            ->join('LEFT JOIN fzm_educational_course ec ON ec.id=o.ec_id')
            ->where($where)
            ->order("o_time DESC")
            ->page($page)
            ->field('o.id as oid, o.sn, o.subject, money, refunds_money, class_num, o.status, p.p_img, p.parent_name, ec.title, ec.img')
            ->select();
        $config = M('Config')->find();
        if (!empty($lists))foreach ($lists as $k=>$order)
        {
            $lists[$k]['tui_status'] = -1;
            $lists[$k]['st_status'] = -1;
            if ($order['status']==5)
            {
                $lists[$k]['tui_status'] = M('Application')->where(['oid'=>$order['oid'], 'o_type'=>2])->getField('status');
                $lists[$k]['st_status'] = M('Application')->where(['oid'=>$order['oid'], 'o_type'=>2])->getField('st_status');
            }
            $lists[$k]['refunds_money'] = $order['refunds_money']*(1-$config['course_radio']);
        }

        $re['count1'] = $oauth_user_model
            ->alias('o')
            ->join('LEFT JOIN fzm_parents p ON p.id=o.parent_id')
            ->join('LEFT JOIN fzm_educational_course ec ON ec.id=o.ec_id')
            ->where(['o.st_id'=>$id, 'o.status'=>2])
            ->count();
        $re['count2'] = $oauth_user_model
            ->alias('o')
            ->join('LEFT JOIN fzm_parents p ON p.id=o.parent_id')
            ->join('LEFT JOIN fzm_educational_course ec ON ec.id=o.ec_id')
            ->where(['o.st_id'=>$id, 'o.status'=>3])
            ->count();
        $re['count3'] = $oauth_user_model
            ->alias('o')
            ->join('LEFT JOIN fzm_parents p ON p.id=o.parent_id')
            ->join('LEFT JOIN fzm_educational_course ec ON ec.id=o.ec_id')
            ->where(['o.st_id'=>$id, 'o.status'=>5])
            ->count();
        $re['list'] = $lists;
        $this->ApiReturn(1, 'success', $re);
    }

    //完成
    public function finish()
    {
        $data = $this->data;
        $id = S($data['token']);
        $oid = $data['oid']??$this->ApiReturn(-1, '订单id不能为空');
        M('EducationalOrder')->where(array('id'=>$oid))->save(['status'=>3]);
        $this->ApiReturn(1, 'success');
    }

    //审核
    public function status()
    {
        $data = $this->data;
        $id = S($data['token']);
        $oid = $data['oid']??$this->ApiReturn(-1, '订单id不能为空');
        $status = !empty($data['status'])?$data['status']:$this->ApiReturn(-1, '修改状态不能为空');  //1.审核通过 2.审核不通过

        if ($status==1){
            M('Application')->where(array('oid'=>$oid, 'o_type'=>2))->save(['st_status'=>2]);
        }else{
            M('Application')->where(array('oid'=>$oid, 'o_type'=>2))->save(['status'=>3]);
        }
        $this->ApiReturn(1, 'success');
    }

    public function getDetail()
    {
        $data = $this->data;
        $id = S($data['token']);
        $where=['o.st_id'=>$id];
        $where['o.id'] = $data['oid']??$this->ApiReturn(-1, '订单id不能为空');

        $config = M('Config')->find();
        $oauth_user_model=M('EducationalOrder');
        $lists = $oauth_user_model
            ->alias('o')
            ->join('LEFT JOIN fzm_parents p ON p.id=o.parent_id')
            ->join('LEFT JOIN fzm_educational_course ec ON ec.id=o.ec_id')
            ->where($where)
            ->field('o.id as oid, o.sn, o.subject, money, refunds_money, class_num, o.status, p.p_img, p.parent_name,o_time, ec.title, ec.img, p.phone')
            ->find();
        if (!$lists){
            $this->ApiReturn(0, 'success');
        }

        $lists['tui_status'] = -1;
        $lists['st_status'] = -1;
        if ($lists['status']==5)
        {
            $lists['tui_status'] = M('Application')->where(['oid'=>$lists['oid'], 'o_type'=>2])->getField('status');
            $lists['st_status'] = M('Application')->where(['oid'=>$lists['oid'], 'o_type'=>2])->getField('st_status');
            $lists['t_reason'] = M('Application')->where(['oid'=>$lists['oid'], 'o_type'=>2])->getField('t_reason');
        }
        $lists['refunds_money'] = $lists['refunds_money']*(1-$config['course_radio']);

        $this->ApiReturn(1, 'success', $lists);
    }


}