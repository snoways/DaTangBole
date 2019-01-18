<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/29 0029
 * Time: 下午 05:42
 */

namespace ManagedClient\Controller;


use Client\Controller\UserApiBaseController;

class VideoController extends UserApiBaseController
{

    //教育机构视频管理

    public function index()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;

        $where['sv.status'] = ['neq', 3];
        $where['st_id'] = $id;
        $oauth_user_model=M('SmallVideo');
        $lists = $oauth_user_model
            ->alias('sv')
            ->where($where)
            ->order("add_time DESC")
            ->page($page)
            ->field('sv.id as vid, title, img_url, video_url, add_time, status')
            ->select();
        if (!$lists){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $lists);
        }
    }

    public function del()
    {
        $data = $this->data;
        $id = S($data['token']);
        $vid = !empty($data['vid'])?$data['vid']:$this->ApiReturn(-1, '视频id不能为空');
        M('SmallVideo')->where(array('id'=>$vid, 'st_id'=>$id))->save(['status'=>3]);
        $this->ApiReturn(1, 'success');
    }

    //视频上架、下架
    public function status()
    {
        $data = $this->data;
        $id = S($data['token']);
        $vid = !empty($data['vid'])?$data['vid']:$this->ApiReturn(-1, '视频id不能为空');
        $status = !empty($data['status'])?$data['status']:$this->ApiReturn(-1, '状态不能为空');  //1.上架 2.下架 3.删除
        M('SmallVideo')->where(array('id'=>$vid))->save(['status'=>$status]);
        $this->ApiReturn(1, 'success');
    }

    //视频置顶
    public function top()
    {
        $data = $this->data;
        $id = S($data['token']);
        $vid = !empty($data['vid'])?$data['vid']:$this->ApiReturn(-1, '视频id不能为空');
        $where['st_id'] = $id;
        M('SmallVideo')->where($where)->save(['is_top'=>0]);
        M('SmallVideo')->where(['id'=>$vid])->save(['is_top'=>1]);
        $this->ApiReturn(1, 'success');
    }

    /**
     * 发布视频
     * sunfan
     * 2018.8.6
     */
    public function add()
    {
        $data = $this->data;
        $id = S($data['token']);
        $title = $data['title']??$this->ApiReturn(-1, '标题不能为空');
        $desc = $data['desc']??$this->ApiReturn(-1, '描述不能为空');
        $img_url = $data['img_url']??$this->ApiReturn(-1, '缩略图不能为空');
        $video_url = $data['video_url']??$this->ApiReturn(-1, '视频地址不能为空');
        $db = M('SmallVideo');
        $db->add([
            'title'     =>  $title,
            'desc'      =>  $desc,
            'img_url'   =>  $img_url,
            'video_url' =>  $video_url,
            'add_time'  =>  date('Y-m-d H:i:s'),
            'st_id'     =>  $id,
        ]);
        $this->ApiReturn(1, 'success');
    }

}