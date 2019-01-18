<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/11/3
 * Time: 10:03
 */

namespace Parents\Controller;


use Client\Controller\MapiBaseController;
use Client\Controller\UserApiBaseController;

class ManagedController extends UserApiBaseController
{

    /**
     * 托管列表
     * sunfan
     * 2017.11.3
     */
    public function index()
    {
        $data = $this->data;
        $page = $data['page']??1;
        $id = S($data['token']);
        $where['s_type'] = 1;
        $where['s_status'] = 2;
        $where['status'] = 1;
        if (!empty($data['keyword'])){
            $where['s_name|address|c_name|s_content|s_phone'] = ['like', '%'.$data['keyword'].'%'];
        }
        $list = M('SmallTable')
            ->where($where)
            ->page($page, 20)
            ->field('id as st_id, s_name, s_img, address, c_name, s_content, s_phone, s_xy,view_num,star')
            ->select();

        $start = M('Parents')->where(['id'=>$id])->getField('p_xy');
        foreach ($list as $k=>$item)
        {
            $dis = getDistance($start, $item['s_xy']);
            $list[$k]['distance']=$dis['length'];
        }
        $this->ApiReturn(1, 'success', $list);
    }

//    public function index()
//    {
//        $data = $this->data;
//        $page = $data['page']??1;
//        $id = S($data['token']);
//        $where['s_type'] = 1;
//        $where['pid'] = $id;
//        if (!M('SmallTableSort')->where($where)->find()){
//            //如果这个人还没有数据，先拿别人的
//            $where['pid'] =  M('SmallTableSort')->where(['s_type'=>1])->getField('pid');
//        }
//        if (!empty($data['keyword'])){
//            $where['s_name|address|c_name|s_content|s_phone'] = ['like', '%'.$data['keyword'].'%'];
//        }
//        $order=[];
//        if (!empty($data['distance']))          //距离
//        {
//            $order['distance1'] = 'ASC';
//        }
//        if (!empty($data['star']))          //星级
//        {
//            $order['star'] = 'DESC';
//        }
//
//        $list = M('SmallTableSort')
//            ->where($where)
//            ->page($page)
//            ->field('sid as st_id, s_name, s_img, address, c_name, s_content, s_phone, distance, star, view_num')
//            ->order($order)
//            ->select();
//
//        if (!$list){
//            $this->ApiReturn(0, 'success');
//        }else{
//            $this->ApiReturn(1, 'success', $list);
//        }
//    }

    /**
     * 托管详情
     * sunfan
     * 2017.11.3
     */
    public function detail()
    {
        $data = $this->data;
        $uid = S($data['token']);
        $id = $data['st_id']??$this->ApiReturn(-1, 'id不能为空');
        $rs = M('SmallTable')
            ->where(['id'=>$id])
            ->field('id as st_id, s_img, level_id, star, view_num, address, s_name, s_phone, s_xy, introduction, s_content')
            ->find();
        if (!$rs){
            $this->ApiReturn(-1, '托管异常');
        }

        //云信id
        $rs['accid'] = "s".$rs['st_id'];

        //会员等级图标
        $rs['icon'] = M('Level')->where(['id'=>$rs['level_id']])->getField('icon');

        //收藏
        $rs['collection'] = -1;
        if (M('Collection')->where(['parent_id'=>$uid, 'item_id'=>$id])->find())
        {
            $rs['collection'] = 1;
        }

        //视频
            $rs['video'] = M('SmallVideo')->where(['st_id' => $id])->order('is_top desc')->limit(1)->field('id as vid, img_url')->select();

        //形象展示
        $rs['imgs'] = M('SmallImages')->where(['st_id'=>$id])->field('url')->order('sort')->select();

        //增加一条访客记录
        $this->addVisitor($uid, $id, 2);

        //增加浏览量
        M('SmallTable')->where(['id'=>$id])->setInc('view_num');
        M('SmallTableSort')->where(['sid'=>$id])->setInc('view_num');


        $circle = D("circle");
        //亲子时光
        $where['uid'] = $id;
        $where['u_type'] = 3;
        $where['option'] = 1;
        $rs['crafts'] = $circle->where($where)->count();

        //校园日记
        $sql['uid'] = $id;
        $sql['u_type'] = 3;
        $sql['option'] = 2;
        $rs['campus'] = $circle->where($sql)->count();

        //线上课堂
//        if ($id=471){
//            $rs['online_list']=[];
//        }else {
            $online_class = D("OnlineClass");
            $on['u_type'] = 2;
            $on['status'] = ['NOT IN', [3, 2]];
            $on['tid'] = $id;
            $rs['online_list'] = $online_class->where($on)->count();
//        }

        //师资列表
        $sma['st_id'] = $id;
        $rs['small_teacher'] = M("small_teacher")->where($sma)->count();
        $rs['small_teacher_list'] = M("small_teacher")->limit(4)->order('top desc')->field('id teacher_id,name,desc,img')->where($sma)->select();

        //优惠券
        $rs['coupon'] = M('Coupon')->where(['u_id'=>$id])->order('create_time desc')->field('money, min_consumption')->find();

        $this->ApiReturn(1, 'success', $rs);
    }

    /**
     * 托管师资列表
     * sunfan
     * 2018.7.24
     */
    public function smallTeacherList()
    {
        $data = $this->data;
        $uid = S($data['token']);
        $id = $data['st_id']??$this->ApiReturn(-1, 'id不能为空');
        $rs = M('SmallTable')
            ->where(['id'=>$id])
            ->field('id as st_id, s_img, level_id, star, view_num, address, s_name, s_phone, s_xy, introduction, s_content')
            ->find();
        if (!$rs){
            $this->ApiReturn(-1, '托管异常');
        }

        //云信id
        $rs['accid'] = "s".$rs['st_id'];

        //会员等级图标
        $rs['icon'] = M('Level')->where(['id'=>$rs['level_id']])->getField('icon');

        //收藏
        $rs['collection'] = -1;
        if (M('Collection')->where(['parent_id'=>$uid, 'item_id'=>$id])->find())
        {
            $rs['collection'] = 1;
        }
        //师资列表
        $sma['st_id'] = $id;
        $rs['small_teacher_list'] = M("small_teacher")->order('top desc')->field('id teacher_id,name,desc,img')->where($sma)->select();

        $this->ApiReturn(1, 'success', $rs);
    }

    public function smallTeacherDetail()
    {
        $data = $this->data;
        $uid = S($data['token']);
        $id = $data['st_id']??$this->ApiReturn(-1, 'id不能为空');
        $teacher_id = $data['teacher_id']??$this->ApiReturn(-1, 'id不能为空');
        $rs = M('SmallTable')
            ->where(['id'=>$id])
            ->field('id as st_id, s_phone')
            ->find();
        if (!$rs){
            $this->ApiReturn(-1, '托管异常');
        }

        //云信id
        $rs['accid'] = "s".$rs['st_id'];

        //收藏
        $rs['collection'] = -1;
        if (M('Collection')->where(['parent_id'=>$uid, 'item_id'=>$id])->find())
        {
            $rs['collection'] = 1;
        }
        //师资列表
        $sma['st_id'] = $id;
        $sma['id'] = $teacher_id;
        $rs['small_teacher_list'] = M("small_teacher")->field('id teacher_id,name,desc,img, content')->where($sma)->find();

        $this->ApiReturn(1, 'success', $rs);
    }

    /**
     * 收藏托管
     * sunfan
     * 2017.11.17
     */
    public function collectManaged()
    {
        $data = $this->data;
        $id = S($data['token']);
        $managed_id = $data['managed_id']??$this->ApiReturn(-1, '托管id不能为空');
        if (M('Collection')->where(['parent_id'=>$id, 'item_id'=>$managed_id,'type'=>2])->find())
        {
            $this->ApiReturn(-1, '已经收藏过了');
        }

        M('Collection')->add([
            'parent_id'     =>  $id,
            'item_id'       =>  $managed_id,
            'c_time'        =>  date('Y-m-d H:i:s'),
            'type'          =>  2
        ]);
        $this->ApiReturn(1, '收藏成功');
    }
}