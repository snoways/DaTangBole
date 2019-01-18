<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 21:19
 */

namespace ManagedClient\Controller;

use Client\Controller\MapiBaseController;
use Think\Page;
require('./application/Common/common/JPushSF.php');

class TravelsController extends MapiBaseController
{
    public function getPageList()
    {
        $data = $this->data;
        $page = $data['page'] ?? 1;
        $keyword = trim($data['keyword']);
        if (!empty($keyword)) {
            $where['title|author'] = ['like',"%{$keyword}%"];
        }
        $where['status'] = 1;
        $list = M('Travels')
            ->where($where)
            ->order("sort asc, add_time DESC")
            ->page($page,8)
            ->field('id,img_url,title,author,viewnum,add_date')
            ->select();

        $this->ApiReturn(1, 'success', $list);
    }

    public function getDetail()
    {
        $data = $this->data;
        $uid = S($data['token']);
        $where['id'] = $data['travels_id']?$data['travels_id']:$this->ApiReturn(-1,'缺少必传参数tra_id');
        $where['status'] = 1;
        $list = M('Travels')
            ->where($where)
            ->field('img_list,status,is_home,sort,add_time,add_date,agent_id',true)
            ->find();
        if($list){
            $list['thumbs'] = "0";//未点赞
            if ($uid) {
                if (M('travels_thumbs')->where(['uid'=>$uid,'tra_id'=>$data['travels_id']])->find()) {
                    $list['thumbs'] = 1; //已点赞
                }
            }
            $this->ApiReturn(1, 'success', $list);
        }
        $this->ApiReturn(1, 'success', []);
    }

    /**
     *  提交评价
     */

    public function postComment()
    {

        $data = $this->data;
        $uid = S($data['token'])?S($data['token']):$this->ApiReturn(20003,'token无效或已过期');
        $tra_id = $data['tra_id']?$data['tra_id']:$this->ApiReturn(-1,'缺少必传参数tra_id');

        if (D('travels')->where(['id'=>$tra_id,'can_comment'=>1])->getField('id')) {
            $comment_id = $data['comment_id']!=""?$data['comment_id']:0;
            $user_id = $data['user_id']?$data['user_id']:0;
            $content = $data['content']?$data['content']:$this->ApiReturn(-1,'请填写内容');
            if ($comment_id) {
                if (!D('travels_comment')->where(['tra_id'=>$tra_id,'id'=>$comment_id])->getField('id'))
                    $this->ApiReturn(-1,'您要回复的评论不存在');
            }
            if ($user_id) {
                if (!$comment_id)  $this->ApiReturn(-1,'缺少comment_id');
                if (!D('travels_comment')->where(['tra_id'=>$tra_id,'user_id'=>$user_id])->getField('id'))
                    $this->ApiReturn(-1,'您要回复的评论不存在');
            }
            $insert = array(
                'tra_id'=>$tra_id,
                'user_id'=>$uid,
                'to_comment_id'=>$comment_id,
                'to_user_id'=>$user_id,
                'create_time'=>date('Y-m-d H:i:s'),
                'content'=>$content
            );
            $id =  D('travels_comment')->add($insert);
            if($id){
                D('travels')->where(['id'=>$tra_id])->setInc('commentnum',1);
                if ($user_id) {
                    $push = new \JPushSF(C('JPush.PAPPID'),C('JPush.PAPPSECRET'));
                    $alias = 'parent'.$user_id;
                    $receive = array('alias'=>[$alias]);//别名
                    $push->push($receive, 12, $tra_id);
                }
                $this->ApiReturn(1,"成功",array('id'=>$id));
            }
            $this->ApiReturn(-1,'失败');
        }
        $this->ApiReturn(-1,'不可评论');
    }
    /**
     *  获取评论列表
     */
    public function comments()
    {
        $data = $this->data;
        $pageNo = $data['page']?$data['page']:1;
        $uid = S($data['token']);
        $tra_id = $data['tra_id']?$data['tra_id']:$this->ApiReturn(-1,'缺少必传参数tra_id');
        $where = array(
            'c.tra_id' => $tra_id,
            'c.to_user_id'=>0,
            'c.to_comment_id'=>0
        );
        $travels_comment = D('travels_comment');
        $count = $travels_comment
            ->alias('c')
            ->join('left join __PARENTS__ u on c.user_id = u.id')
            ->where($where)
            ->count();
        $page = new Page($count,8);
        $page->show();
        $totalPages = $page->totalPages;
        $list = $travels_comment
            ->alias('c')
            ->join('left join __PARENTS__ u on c.user_id = u.id')
            ->where($where)
            ->page($pageNo,8)
            ->field('c.id,c.tra_id,c.create_time,c.user_id,c.content,u.parent_name as user_name,u.p_img as avatar')
            ->order('c.create_time desc')
            ->select();

        foreach ($list as &$item){
            $item['create_time'] = current(explode(' ',$item['create_time']));
            $item['status'] = 0;//不可删除
            //如果是用户评论的
            if ($uid && $uid == $item['user_id']) {
                $item['status'] = 1;//可删除
            }
            $reply = $travels_comment
                ->alias('c')
                ->join('left join __PARENTS__ u on c.user_id = u.id')
                ->join('left join __PARENTS__ t on c.to_user_id = t.id')
                ->where(array(
                    'c.to_comment_id'=>$item['id']
                ))
                ->field('c.id,c.user_id,c.to_comment_id,c.create_time,c.content,u.parent_name as user_name,u.p_img as avatar,t.parent_name at')
                ->order('c.create_time desc')
                ->select();
            foreach ($reply as &$value) {
                $value['create_time'] = current(explode(' ',$value['create_time']));
                $value['status'] = 0;// 不可删除
                if ($uid && $uid == $value['user_id']) {
                    $value['status'] = 1;//可删除
                }
                $value['at'] = $value['at']??"";
            }
            $item['reply_no'] = (string)count($reply);
            $item['reply'] = $reply;
        }

        $info = array(
            'list'=>array(),
            'totalPages'=>$totalPages,
            'currentPag'=>$pageNo,
            'total'=>$count
        );

        if($list){
            $info['list'] = $list;
            $this->ApiReturn(1,'成功',$info);
        }else{
            $this->ApiReturn(0,'成功',$info);
        }
    }

    /*
   * 获取评论详情
   */
    public function commentDetail()
    {
        $data = $this->data;
        $uid = S($data['token']);
        $cid = $data['comment_id']?$data['comment_id']:$this->ApiReturn(-1,'缺少必传参数comment_id');

        $travels_comment = D('travels_comment');
        $comment = $travels_comment
            ->alias('c')
            ->join('left join __PARENTS__ u on c.user_id = u.id')
            ->where(array('c.id'=>$cid))
            ->field('c.id,c.tra_id,c.user_id,c.create_time,c.content,u.parent_name as user_name,u.p_img as avatar')
            ->find();
        $comment['status'] = 0;//不可删除
        if ($uid && $uid == $comment['user_id']) {
            $comment['status'] = 1;//可删除
        }
        $comment['create_time'] = current(explode(' ',$comment['create_time']));
        $replyNo = $travels_comment
            ->alias('c')
            ->join('left join __PARENTS__ u on c.user_id = u.id')
            ->where(array(
                'c.to_comment_id'=>$cid
            ))
            ->count();

        $reply = $travels_comment
            ->alias('c')
            ->join('left join __PARENTS__ u on c.user_id = u.id')
            ->join('left join __PARENTS__ t on c.to_user_id = t.id')
            ->where(array(
                'c.to_comment_id'=>$cid
            ))
            ->field('c.id,c.user_id,c.to_comment_id,c.content,c.create_time,u.parent_name as user_name,u.p_img as avatar,t.parent_name at')
            ->order('c.create_time desc')
            ->select();
        foreach ($reply as &$value) {
            $value['create_time'] = current(explode(' ',$value['create_time']));
            $comment['status'] = 0;//不可删除
            if ($uid && $uid == $value['user_id']) {
                $value['status'] = 1;//可删除
            }
            $value['at'] = $value['at']??"";
        }
        $page = new Page($replyNo,8);
        $page->show();
        $info = $comment;
        $info['replyNo'] = $replyNo;
        $info['pages'] = $page->totalPages;
        $info['reply'] = [];
        if($reply){
            $info['reply'] = $reply;
        }
        $this->ApiReturn(1,'成功',$info);
    }
    /**
     * 游记点赞
     */
    public function commentLike()
    {
        $data = $this->data;
        $uid = S($data['token'])?S($data['token']):$this->ApiReturn(20003,'token无效或已过期');;
        $travels_id = $data['travels_id']?$data['travels_id']:$this->ApiReturn(-1,'缺少游记id');;
        $where['id'] = $travels_id;
        $where['status'] = 1;
        $travels = M('travels');
        $travels_thumbs = M('travels_thumbs');
        $zannum = $travels->where($where)->field('zannum')->find();
        if ($zannum) {
            if (!$travels_thumbs->where(['uid'=>$uid,'tra_id'=>$travels_id])->find()) {
                if ($travels->where($where)->setInc('zannum',1)) {
                    $travels_thumbs->add(['uid'=>$uid,'tra_id'=>$travels_id]);
                    ++$zannum['zannum'];
                    $this->ApiReturn(1,'成功',$zannum);
                }
                $this->ApiReturn(-1,'点赞失败::'.$travels->getLastSql());
            }
            $this->ApiReturn(-1,'您已经点过赞了');
        }
        $this->ApiReturn(-1,'游记不存在');
    }


    /**
     * 删除自己的评论
     */
    public function delComment()
    {
        $data = $this->data;
        $uid = S($data['token']);
        if (!$uid) $this->ApiReturn(20003,'token无效或已过期');
        $cid = $data['comment_id']?$data['comment_id']:$this->ApiReturn(-1,'缺少必传参数comment_id');
        $travels_comment = D('travels_comment');
        if (!$travels_comment->where(['id'=>$cid,'user_id'=>$uid])->find())
            $this->ApiReturn(-1,'评论不存在');

        if ($travels_comment->delete($cid)) {
            $this->ApiReturn(1,'success');
        } else {
            $this->ApiReturn(-1,'error');
        }
    }
}