<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/13 0013
 * Time: 下午 01:36
 */

namespace ManagedClient\Controller;


use Client\Controller\UserApiBaseController;

class OnlineClassController extends UserApiBaseController
{
    //我的视频课
    public function index(){
        $data = $this->data;
        $tid = S($data['token']);
        $where = [];
        $where['tid'] = $tid;
        $where['status'] = ['neq',3];
        $where['u_type'] = 2;
        $t_sub1 = $data['t_sub1'];
        if($t_sub1 && $t_sub1!="全部"){
            $where['t_sub1'] = ['like',"%$t_sub1%"];
        }
        $level = M("SmallTable")->alias('m')->join("left join fzm_level l on m.level_id = l.id")->where(['m.id'=>$tid])->field('l.id,l.name')->find();
        $field = 'id,title,img_url,sale_num,price,status,state';
        $page = $data['page']?$data['page']:1;
        $arr['list'] = M("online_class")->where($where)->limit(($page-1)*10,10)->field($field)->order("oc_time desc")->select();

        $arr['subject'][0] = ['s_name'=>"全部"];
        $subject = M("subject")->where(['parentid'=>0])->order('sort asc')->field('s_name')->select();
        foreach ($subject as $item){
            array_push($arr['subject'], $item);
        }

        $this->ApiReturn(1,'ok',$arr);
    }

    /**
     * 发布视频课
     * sunfan
     * 2018.6.14
     */
    public function publishVideo()
    {
        $data = $this->data;
        $id = S($data['token']);
        $title = !empty($data['title'])?$data['title']:$this->ApiReturn(-1, '标题不能为空');
        $img_url = !empty($data['img_url'])?$data['img_url']:'/public/images/videopic.jpg';
        $video_url = !empty($data['video_url'])?$data['video_url']:$this->ApiReturn(-1, '视频不能为空');
        $content = !empty($data['content'])?$data['content']:$this->ApiReturn(-1, '课程说明不能为空');
        $t_sub1 = !empty($data['sub1'])?$data['sub1']:$this->ApiReturn(-1, '一级分类不能为空');
        $t_sub2 = !empty($data['sub2'])?$data['sub2']:$this->ApiReturn(-1, '二级分类不能为空');
        $t_sub3 = !empty($data['sub3'])?$data['sub3']:$this->ApiReturn(-1, '三级级分类不能为空');
        $price_type = in_array($data['price_type'],[1,0])?$data['price_type']:$this->ApiReturn(-1, '请选择付费还是免费');
        $price = $data['price'];
        if ($price_type == 1) {
            if($price <= 0 || !preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $price)){
                $this->ApiReturn(-1,'请输入大于0的并且最多2为小数的价格');
            }
            if($data['access_password'] == true && !preg_match('/^(\w){4,8}$/',$data['access_password'])){
                $this->ApiReturn(-1,'只能输入4-8个字母、数字、下划线');
            }
        }
        M('OnlineClass')->add([
            'title'           =>  $title,
            'tid'             =>  $id,
            'u_type'          =>  2,      //用户类型 1.老师 2.商户 3.后台
            'img_url'         =>  $img_url,
            'video_url'       =>  $video_url,
            'oc_time'         =>  date('Y-m-d H:i:s'),
            'price'           =>  $price,
            'content'         =>  $content,
            't_sub1'          =>  $t_sub1,
            't_sub2'          =>  $t_sub2,
            't_sub3'          =>  $t_sub3,
            'price_type'      =>  $price_type,
            'access_password' =>  $data['access_password'],
        ]);
        $this->ApiReturn(1, '上传成功');
    }
    //下架或删除
    public function shelves(){
        $data = $this->data;
        $tid = S($data['token']);
        $type = $data['type'];
        $id = $data['class_id'];
        if(!$id)$this->ApiReturn(-1,'缺少必传参数class_id');
        if(!in_array($type,[1,2,3]))$this->ApiReturn(-1,'参数错误');      //1下架  2删除  3.上架
        if($type == 1){
            //status 1.上架 2.下架 3.删除
            $info = M("online_class")->where(['id'=>$id,'tid'=>$tid, 'u_type'=>2])->save(['status'=>2]);
        }elseif($type == 2){
            $info = M('online_class')->where(['id'=>$id,'tid'=>$tid, 'u_type'=>2])->save(['status'=>3]);
        }else{
            $info = M("online_class")->where(['id'=>$id,'tid'=>$tid, 'u_type'=>2])->save(['status'=>1]);
        }
        if($info){
            $this->ApiReturn(1,'操作成功');
        }else{
            $this->ApiReturn(-1,'操作失败');
        }
    }
    //我的视频课  订单详情
    public function class_detail(){
        $data = $this->data;
        $tid = S($data['token']);
        $id = $data['class_id'];
        $page = $data['page']?$data['page']:1;
        if(!$id)$this->ApiReturn(-1,'缺少必传参数class_id');
        $level = M("SmallTable")->alias('m')->join("left join fzm_level l on m.level_id = l.id")->where(['m.id'=>$tid])->field('l.id,l.name')->find();
        $field = 'id,title,img_url, video_url,sale_num,price';
        $arr['info'] = M("online_class")->where(['id'=>$id])->field($field)->find();
        $where['o.status'] = 2;
        $where['o.oc_id'] = $id;
//        $where['o.tid'] = $tid;
        $arr['list'] = M("OnlineClassOrder")->alias('o')
            ->join("left join fzm_parents p on o.parent_id = p.id")
            ->where($where)->order('o.o_time desc')
            ->limit(($page-1)*10,10)
            ->field('o.sn,p.parent_name as child_name ,p.p_img,o.o_time')
            ->select();
        $this->ApiReturn(1,'ok',$arr);
    }

}