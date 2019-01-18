<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/2 0002
 * Time: 下午 02:31
 */

namespace Parents\Controller;


use Client\Controller\UserApiBaseController;

class CourseController extends UserApiBaseController
{

    /**
     * 二期新增
     * 教育机构课程
     * sunfan
     * 2018.5.2
     */


    //教育课程列表
    public function index()
    {
        $data = $this->data;
        $id = S($data['token']);
        $st_id = $data['st_id']??$this->ApiReturn(-1, '请传入st_id');
        $page = !empty($data['page'])?$data['page']:1;

        $where['status'] = 1;   //1.正常  3.删除
        $where['st_id'] = $st_id;
        $rs = M('EducationalCourse')
            ->where($where)
            ->page($page)
            ->field('id as ec_id, title, grade, sub1, sub2, price, img, class, star, buy_num')
            ->select();

        $this->ApiReturn(1, 'success', $rs);
    }

    //详情
    public function detail()
    {
        $data = $this->data;
        $ec_id = !empty($data['ec_id'])?$data['ec_id']:$this->ApiReturn(-1,'id不能为空');
        $rs = M('EducationalCourse')
            ->where(['id'=>$ec_id])
            ->field('id as ec_id, title, grade, sub1, sub2, price, img, class, star, content, buy_num, st_id, tui_price, no_class')
            ->find();

        //评价
        $assess = M('Assess')
            ->join('LEFT JOIN fzm_educational_order ON fzm_educational_order.id=fzm_assess.order_id')
            ->join('LEFT JOIN fzm_parents ON fzm_parents.id=fzm_assess.u_id')
            ->where(['fzm_assess.t_id'=>$rs['st_id'], 'type'=>2])
            ->field('child_name, child_sex, p_img, subject, content, a_time, fzm_assess.status, img1, img2, img3, star')
            ->order('fzm_assess.a_time DESC')
            ->select();
        foreach($assess as $k=>$v){
            $assess[$k]['a_time'] = substr($v['a_time'],5,11);
            if ($v['status'] == 2){
                $assess[$k]['child_name'] = "匿名用户";
            }
            if (empty($v['img1'])){
                unset($assess[$k]['img1']);
            }
            if (empty($v['img2'])){
                unset($assess[$k]['img2']);
            }
            if (empty($v['img3'])){
                unset($assess[$k]['img3']);
            }
        }

        //云信id
        $rs['accid'] = "s".$rs['st_id'];

        //机构信息
        $educational = M('SmallTableSort')
            ->where(['sid'=>$rs['st_id']])
            ->field('sid as st_id, s_name, s_img, address, c_name, s_content, s_phone, distance, star, view_num, level_id')
            ->find();
        //会员等级图标
        $educational['icon'] = M('Level')->where(['id'=>$educational['level_id']])->getField('icon');

        $rs['educational'] = $educational;
        $rs['assess'] = $assess;
        $this->ApiReturn(1, 'success', $rs);
    }
    
    //下单
    public function addOrder()
    {
        $data = $this->data;
        $id = S($data['token']);
        $ec_id = $data['ec_id']??$this->ApiReturn(-1, '课程id不能为空');
        $coupon_id = $data['cr_id'];//优惠券id

        $course = M('EducationalCourse')->where(['id'=>$ec_id])->find();
        if (!$course)$this->ApiReturn(-1, '课程异常，请选择其他课程');
        $config = M('Config')->find();
        $total = $course['price'];
        $platform_money = $total*$config['course_radio'];
        $money = $total*(1-$config['course_radio']);
        $order_sn = "C";
        $order_sn .= sp_get_order_sn();

        //使用优惠券
        $coupon_money=0;
        if (!empty($coupon_id)){
            $coupon_money = $this->useCoupon($id, 3, $total, $coupon_id);
        }
        $total -= $coupon_money;

        $o_time = date('Y-m-d H:i:s');
        $oid = M('EducationalOrder')->add([
            'sn'            =>  $order_sn,
            'ec_id'         =>  $ec_id,
            'parent_id'     =>  $id,
            'class_num'     =>  $course['class'],
            'st_id'         =>  $course['st_id'],
            'total_money'   =>  $total,
            'platform_money'=>  $platform_money,
            'money'         =>  $money,
            'coupon_money'  =>  $coupon_money??0.00,
            'subject'       =>  $course['sub1']." ".$course['sub2'],
            'o_time'        =>  $o_time,
        ]);
        if ($oid){
            $rs['sn'] = $order_sn;
            $rs['title'] = $course['title'];
            $rs['grade'] = $course['grade'];
            $rs['class_num'] = $course['class'];
            $rs['o_time'] = $o_time;
            $rs['subject'] = $course['sub1']." ".$course['sub2'];
            $this->ApiReturn(1, 'success', $rs);
        }else{
            $this->ApiReturn(-1, '失败', ['kefu_tel'=>$config['kefu_tel']]);
        }
    }

    /**
     * 评价订单
     * sunfan
     * 2018.6.22
     */
    public function assess()
    {
        $data = $this->data;
        $id = S($data['token']);
        $order_sn = $data['order_sn']??$this->ApiReturn(-1, '订单编号不能为空');
        $order = M('EducationalOrder')->where(['sn'=>$order_sn, 'parent_id'=>$id])->find();
        $content = $data['content']??$this->ApiReturn(-1, '评论内容不能为空');
        $star = $data['star']??$this->ApiReturn(-1, '评论星级不能为空');
        $status = !in_array([1, 2], $data['status'])?$data['status']:1;   //2.匿名
        $img1 = $data['img1'];
        $img2 = $data['img2'];
        $img3 = $data['img3'];
        if (!$order){
            $this->ApiReturn(-1, '订单异常');
        }
        if ($order['status']!=3){
            $this->ApiReturn(-1, '订单暂不能评价');
        }
        if (M('Assess')->where(['order_id'=>$order['id'], 'type'=>2])->find()){
            $this->ApiReturn(-1, '订单已评价');
        }
        M('Assess')->add([
            'order_id'   =>  $order['id'],
            'content'=>  $content,
            'u_id' =>  $id,
            't_id' =>  $order['st_id'],
            'a_time'=>  date('Y-m-d H:i:s'),
            'star'  =>  $star,
            'status'=>  $status,
            'img1'=>  $img1,
            'img2'=>  $img2,
            'img3'=>  $img3,
            'type'=>2       //1家长（给老师的评价）  2.家长给机构课程的评价 3.家长给托管课程的评价
        ]);

        //修改课程星级
        $course = M('EducationalCourse')->where(['id'=>$order['ec_id']])->find();
        $star = ($course['star']+$star)/2;
        M('EducationalCourse')->where(['id'=>$order['ec_id']])->save(['star'=>$star]);
        $this->ApiReturn(1, '评价成功');
    }
}