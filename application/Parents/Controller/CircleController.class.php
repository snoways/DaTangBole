<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/27 0027
 * Time: 上午 11:18
 */

namespace Parents\Controller;


use Client\Controller\UserApiBaseController;
require('./application/Common/common/JPushSF.php');

class CircleController extends UserApiBaseController
{

    /**
     * 青春日记---亲子时光
     * sunfan
     * 二期新增
     */


    public function index()
    {
        $circle = new \Client\Controller\CircleController();
        $circle->index(1);
    }

    public function person()
    {
        $circle = new \Client\Controller\CircleController();
        $circle->person(1);
    }

    /**
     * 发布
     */
    public function add()
    {
        $data = $this->data;
        $id = S($data['token']);
        $option = !empty($data['option'])?$data['option']:$this->ApiReturn(-1, '请传入类型');    //1.亲子时光 2.青春日记
        $c_type = !empty($data['c_type'])?$data['c_type']:$this->ApiReturn(-1, '请传入发布类型');

        //1.纯文本 2.文本+图片 3.文本+视频
        if ($c_type==1){
            $content = !empty($data['content'])?$data['content']:$this->ApiReturn(-1, '文字内容不能为空');
            $img_list = '';
            $video_url = '';
        }elseif ($c_type==2){
            $content = $data['content'];
            $img_list = !empty($data['img_list'])?$data['img_list']:$this->ApiReturn(-1, '图片不能为空');
            $video_url = '';
        }else{
            $content = $data['content'];
            $video_url = !empty($data['video_url'])?$data['video_url']:$this->ApiReturn(-1, '视频不能为空');
            $video_cover = !empty($data['video_cover'])?$data['video_cover']:'/public/images/circlepic.jpg';
            $img_list = '';
        }

        $cid = M('Circle')->add([
            'uid'       =>  $id,
            'u_type'    =>  1,
            'c_time'    =>  date('Y-m-d H:i:s'),
            'content'   =>  $content,
            'img_list'  =>  $img_list,
            'video_url' =>  $video_url,
            'video_cover' =>  $video_cover,
            'c_type'    =>  $c_type,
            'option'    =>  $option
        ]);

        //推送
        //查到所有关注我的人
        $collect = M('Collection')->where(['item_id'=>$id, 'type'=>3])->select();
        //给家长推送
        $push = new \JPushSF(C('JPush.PAPPID'),C('JPush.PAPPSECRET'));
        if (!empty($collect))foreach ($collect as $k=>$item){
            $alias = 'parent'.$item['parent_id'];
            $receive = array('alias'=>[$alias]);//别名
            $result = $push->push($receive, 9, $option);
        }

        $this->ApiReturn(1, 'success');
    }

    public function zan()
    {
        $circle = new \Client\Controller\CircleController();
        $circle->zan(1);
    }

    public function delete()
    {
        $circle = new \Client\Controller\CircleController();
        $circle->delete();
    }

    /**
     * 添加评论
     * sunfan
     * 2018.7.26
     */
    public function addComment()
    {
        $data = $this->data;
        $id = S($data['token']);
        $cid = $data['cid']??$this->ApiReturn(-1, 'id不能为空');
        $circle = M('Circle')->where(['id'=>$cid])->find();
        if (!$circle){
            $this->ApiReturn(-1, '网络错误');
        }
        $content = $data['content']??$this->ApiReturn(-1, '内容不能为空');
        M('Comment')->add([
            'cid'           =>  $cid,
            'content'       =>  $content,
            'uid'           =>  $id,
            'u_type'        =>  1,      //1家长 2老师 3商户
            'item_id'       =>  $data['item_id'],
            'item_type'     =>  $data['item_type'],
            'create_time'   =>  date('Y-m-d H:i:s')
        ]);

        M('Circle')->where(['id'=>$cid])->setInc('comment_num');

        if ($circle['u_type']==1){
            $appid = C('JPush.PAPPID');
            $secret = C('JPush.PAPPSECRET');
            $name = "parent";
        }elseif ($circle['u_type']==2){
            $appid = C('JPush.TAPPID');
            $secret = C('JPush.TAPPSECRET');
            $name = "teacher";
        }else{
            $appid = C('JPush.SAPPID');
            $secret = C('JPush.SAPPSECRET');
            $name = "smalltable";
        }
        $push = new \JPushSF($appid,$secret);
        $alias = $name.$circle['uid'];
        $receive = array('alias'=>[$alias]);//别名
        $result = $push->push($receive, 8, $circle['option']);
        $this->ApiReturn(1, 'success');
    }

    /**
     * 删除评论
     * sunfan
     * 2018.8.2
     * 三端通用
     */
    public function delComment()
    {
        $circle = new \Client\Controller\CircleController();
        $circle->delComment();
    }

    public function commontList()
    {
        $data = $this->data;
        $id = S($data['token']);
        $cid = $data['cid']??$this->ApiReturn(-1, 'id不能为空');
        //查评论
        $comment = M('Comment')
            ->alias('cm')
            ->join('LEFT JOIN fzm_circle c ON c.id=cm.cid')
            ->where(['cid'=>$cid])
            ->field('cm.id as comment_id, cm.content, cm.uid, cm.u_type, cm.item_id, cm.item_type')
            ->select();
        foreach ($comment as &$c){
            if ($c['u_type']==1){
                //家长用户
                $user = M('Parents')->where(['id'=>$c['uid']])->find();
                $c['img'] = $user['p_img'];
                $c['uname'] = $user['parent_name'];

                //判断是否是自己发的
                if($c['uid']==$id){
                    $c['my'] = 1;
                }
            }elseif ($c['u_type']==2){
                //老师用户
                $user = M('Teacher')->where(['id'=>$c['uid']])->find();
                $c['img'] = $user['t_img'];
                $c['uname'] = $user['name'];
            }elseif ($c['u_type']==3){
                //商户用户
                $user = M('SmallTable')->where(['id'=>$c['uid']])->find();
                $c['img'] = $user['s_img'];
                $c['uname'] = $user['s_name'];
            }

            $c['item_name'] = "";
            if (!empty($c['item_id'])){
                if ($c['item_type']==1){
                    $db = M('Parents');
                    $key = "parent_name";
                }elseif ($c['item_type']==2){
                    $db = M('Teacher');
                    $key = "name";
                }else{
                    $db = M('SmallTable');
                    $key = "s_name";
                }
                $c['item_name'] = $db->where(['id'=>$c['item_id']])->getField($key);
            }
        }

        $this->ApiReturn(1, 'success', $comment);
    }
}