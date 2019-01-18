<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/28 0028
 * Time: 下午 01:28
 */

namespace Parents\Controller;


use Client\Controller\MapiBaseController;

class ShareController extends MapiBaseController
{

    public function activity()
    {
        $data = $this->data;
        $st_id = $data['st_id'];
        $where['a.status'] = ['neq', 3];
        !empty($data['aid'])?$where['a.id']=$data['aid']:$this->ApiReturn(-1, '活动id不能为空');

        //浏览量加1
        M('Activity')->where(['id'=>$data['aid']])->setInc('view_num');
        $now = date('Y-m-d H:i:s');

        $oauth_user_model=M('Activity');
        $lists = $oauth_user_model
            ->alias('a')
            ->join('LEFT JOIN fzm_activity_cate ac ON ac.id=a.cate_id')
            ->join('LEFT JOIN fzm_small_table st ON st.id=a.shop_id')
            ->where($where)
            ->field('a.id as aid, a.title, a.img, a.people_num, a.now_num, a.end_time, a.money, a.content, ac.name as cate_name, st.s_name, st.id as st_id, a.view_num')
            ->find();
        $lists['can'] = 1;//可以报名
        if ($lists['end_time'] < $now){
            //过期
            $lists['can'] = 0;//不能报名
        }

        //云信id
        $lists['accid'] = "s".$lists['st_id'];

        $this->ApiReturn(1, 'success', $lists);
    }

    public function circle()
    {
        $data = $this->data;
        $page = !empty($data['page'])?$data['page']:1;
        $db = M('Circle');
        $where['id'] = $data['cid'];

        $list = $db
            ->alias('c')
            ->where($where)
            ->page($page)
            ->field('c.id as circle_id, c_time, content, img_list, video_url, video_cover, zan, c.view_num, c_type, uid, option, u_type')
            ->find();

        $list['c_time'] = substr($list['c_time'], 5);

        //图文
        $img_list = $list['img_list'];
        $list['img_list']=[];
        if($list['c_type']==2){
            $list['img_list'] = explode(',', $img_list);
        }

        if ($list['u_type']==1){
            //家长用户
            $user = M('Parents')->where(['id'=>$list['uid']])->find();
            $list['img'] = $user['p_img'];
            $list['uname'] = $user['parent_name'];

            //是否关注了该家长
            $where_c['type'] = 1;
        }elseif ($list['u_type']==2){
            //老师用户
            $user = M('Teacher')->where(['id'=>$list['uid']])->find();
            $list['img'] = $user['t_img'];
            $list['uname'] = $user['name'];

            //是否关注了该老师
            $where_c['type'] = 2;
        }elseif ($list['u_type']==3){
            //商户用户
            $user = M('SmallTable')->where(['id'=>$list['uid']])->find();
            $list['img'] = $user['s_img'];
            $list['uname'] = $user['s_name'];
        }

        $db->where(['id'=>$list['circle_id']])->setInc('view_num');

        $this->ApiReturn(1, 'success', $list);
    }

    public function activityVideo()
    {
        $data = $this->data;
        $id = $data['vid'];

        //视频
        $video = M('SmallVideo')->where(['id'=>$id])->order('is_top desc')->field('id as vid, img_url, st_id')->find();
        $small = M('SmallTable')->where(['id'=>$video['st_id']])->find();
        $video['s_name'] = $small['s_name'];
        $video['content'] = $small['s_content'];
        $this->ApiReturn(1, 'success', $video);
    }

    /*
     *  分享视频
     */
    public function afterShared()
    {
        $request = $this->data;
        $type = $request['type']?$request['type']:1;
        if(!$request['id']){
            $this->ApiReturn(-1,'缺少参数id');
        }
        /*  托管  */
        try{
            if($type == 1){
                $video =  D('SmallVideo')->alias('v')
                    ->join('left join fzm_small_table as m on v.st_id = m.id')
//                    ->where(array('v.id'=>$request['id'],'m.s_type'=>2))
                    ->where(array('v.id'=>$request['id']))
                    ->field('m.s_name,m.s_img,v.title,v.desc,v.img_url,v.video_url')
                    ->find();
            }else{
                $video =  D('Video')->alias('v')
                    ->where(array('v.id'=>$request['id']))
                    ->field('v.title,v.desc,v.img_url,v.video_url')
                    ->find();
            }
            $this->ApiReturn(1,'成功',$video);
        }catch (\Exception $e){
            $this->ApiReturn(-1,$e->getMessage());
        }
    }

    /**
     *  老师分享页
     */
    public function share_teacher(){
        $data = $this->data;
        $teacher_id = $data['t_id'];
        $info = M('Teacher')
            ->where(['id'=>$teacher_id])
            ->field('id as t_id,name,sex,age,t_img,teach_age,score,level_id')
            ->find();
        $arr['id'] = $info['t_id'];
        $arr['name'] = $info['name'];
        $arr['t_img'] = $info['t_img'];
        $arr['score'] = $info['score'];
        $arr['age'] = $info['age'];
        $arr['teach_age'] = $info['teach_age'];
        $arr['sex'] = $info['sex'];
        $order = M('Order');
        $arr['student_num'] = $order->where(array('teacher_id'=>$teacher_id, 'status'=>array('in', array(1,2,3))))->count();       //订单数（学生数）
        $find = $order->where(array('teacher_id'=>$teacher_id, 'status'=>array('in', array(1,2,3))))->select();  //总课时
        $tomal = "";
        if($find){
            foreach($find as $k=>$v){
                $tomal +=$v['class_num'] *30;
            }
            $arr['class_num']  = $tomal;
        }else{
            $arr['class_num']  = 0;
        }
        $select = M('assess')->select();         //评价个数
        $count = 0;
        if($select){
            foreach($select as $k=>$v){
                $find = M('Order')->where(array('id'=>$v['order_id'],'teacher_id'=>$teacher_id))->find();
                if($find){
                    $count++;
                }

            }
        }
        $arr['pingjia'] = $count;
        $arr['level_name'] = D("level")->where(['id'=>$info['level_id']])->getField('name');
        $this->ApiReturn(1,'成功',$arr);
    }

    /**
     * 机构、托管详情分享
     */
    public function getManagedDetail()
    {
        $data = $this->data;
        $id = $data['st_id']??$this->ApiReturn(-1, 'id不能为空');
        $rs = M('SmallTable')
            ->where(['id'=>$id])
            ->field('id as st_id, s_img, level_id, star, view_num, address, s_name, s_phone, s_xy, introduction, s_content')
            ->find();

        if (!$rs){
            $this->ApiReturn(-1, '托管异常');
        }

        //会员等级图标
        $rs['icon'] = M('Level')->where(['id'=>$rs['level_id']])->getField('icon');
        $this->ApiReturn(1, 'success', $rs);
    }

}