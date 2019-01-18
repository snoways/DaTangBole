<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/29 0029
 * Time: 下午 05:42
 */

namespace Parents\Controller;


use Client\Controller\MapiBaseController;
use Client\Controller\UserApiBaseController;

class VideoController extends MapiBaseController
{


    /**
     * 二期新增
     * 教育机构视频
     * sunfan
     * 2018.5.3
     */


    //教育机构视频管理

    public function index()
    {
        $data = $this->data;
        $st_id = $data['st_id'];
        $page = $data['page']??1;

        $where['sv.status'] = 1;
        if ($st_id){
            $where['st_id'] = $st_id;
        }
        if ($data['keyword']){
            $where['title'] = $data['keyword'];
        }
        $oauth_user_model=M('SmallVideo');
        $lists = $oauth_user_model
            ->alias('sv')
            ->where($where)
            ->order("add_time DESC")
            ->page($page)
            ->field('sv.id as vid, title, img_url, view_num')
            ->select();
        if (!$lists){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $lists);
        }
    }


    /**
     * 二期
     * 视频详情
     * sunfan
     * 2018.7.3
     */
    public function detail()
    {
        $data = $this->data;
        $video_id = $data['video_id'];
        $rs = M('SmallVideo')
            ->where(['id'=>$video_id])
            ->field('id as video_id, title, desc, img_url, video_url, add_time, view_num, st_id')
            ->find();

        $rs['list'] = M('SmallVideo')
            ->where(['st_id'=>$rs['st_id']])
            ->limit(4)
            ->order('add_time desc')
            ->field('id as video_id, title, img_url, view_num')
            ->order('view_num desc')
            ->select();

        M('SmallVideo')->where(['id'=>$video_id])->setInc('view_num');

        $this->ApiReturn(1, 'success', $rs);
    }

    /**
     * 举报视频
     * 二期
     * sunfan
     * 2018.7.3
     */
    public function reportClass()
    {
        $data = $this->data;
        $where['id'] = $data['video_id']??$this->ApiReturn(-1, 'id不能为空');
        $model = M("SmallVideo");
        $info = $model->where($where)->setInc('report_num');
        $this->ApiReturn(1, '举报成功');
    }

}