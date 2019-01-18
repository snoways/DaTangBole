<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/27 0027
 * Time: 上午 11:18
 */

namespace Client\Controller;



class CircleController extends UserApiBaseController
{

    /**
     * 青春日记---亲子时光
     * sunfan
     * 二期新增
     * @param $u_type 1.家长 2.老师 3.商户
     */
    public function index($u_type)
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = !empty($data['page'])?$data['page']:1;
        $db = M('Circle');
        $where = [];
        $where['option'] = !empty($data['option'])?$data['option']:1;    //默认亲子时光--1.亲子时光 2.青春日记
        $type = !empty($data['type'])?$data['type']:1;    //默认全部列表--1.全部列表 2.我的发布
        if ($type==2){
            $where['uid'] = $id;
            $where['u_type'] = $u_type;
        }

        $list = $db
            ->alias('c')
            ->where($where)
            ->page($page)
            ->order('c_time DESC')
            ->field('c.id as circle_id, c_time, content, img_list, video_url, zan, c.view_num, c_type, uid, u_type, video_cover, option, comment_num')
            ->select();

        foreach ($list as $k=>$item) {
            $db->where(['id'=>$item['circle_id']])->setInc('view_num');

            $list[$k]['c_time'] = substr($item['c_time'], 5);

            //图文
            $img_list = $item['img_list'];
            $list[$k]['img_list']=[];
            if($item['c_type']==2){
                $list[$k]['img_list'] = explode(',', $img_list);
            }

            if ($type==2){
                $list[$k]['my'] = -1;   //不是自己发的
            }
            if ($item['u_type']==1){
                //家长用户
                $user = M('Parents')->where(['id'=>$item['uid']])->find();
                $list[$k]['img'] = $user['p_img'];
                $list[$k]['uname'] = $user['parent_name'];

                if ($u_type==1){
                    //判断是否是自己发的
                    if($item['uid']==$id){
                        $list[$k]['my'] = 1;
                    }
                }

                //是否关注了该家长
                $where_c['type'] = 3;
            }elseif ($item['u_type']==2){
                //老师用户
                $user = M('Teacher')->where(['id'=>$item['uid']])->find();
                $list[$k]['img'] = $user['t_img'];
                $list[$k]['uname'] = $user['name'];

                if ($u_type==2){
                    //判断是否是自己发的
                    if($item['uid']==$id){
                        $list[$k]['my'] = 1;
                    }
                }
                //是否关注了该老师
                $where_c['type'] = 1;
            }elseif ($item['u_type']==3){
                //商户用户
                $user = M('SmallTable')->where(['id'=>$item['uid']])->find();
                $list[$k]['img'] = $user['s_img'];
                $list[$k]['uname'] = $user['s_name'];

                if ($u_type==3){
                    //判断是否是自己发的
                    if($item['uid']==$id){
                        $list[$k]['my'] = 1;
                    }
                }
                //是否关注了该商户
                $where_c['type'] = 2;
            }else{
                //后台用户
                $list[$k]['img'] = "/public/images/img_new.png";
                $list[$k]['uname'] = M('Config')->getField('circle_name');

                $where_c['type'] = 0;
            }

            //是否关注了该老师、家长
            $list[$k]['collection'] = -1;
            $where_c['parent_id'] = $id;
            $where_c['item_id'] = $item['uid'];
            if (M('Collection')->where($where_c)->find()){
                $list[$k]['collection'] = 1;
            }

            //是否点赞了
            $list[$k]['is_zan'] = -1;
            if (M('CircleLog')->where(['uid'=>$id, 'circle_id'=>$item['circle_id'], 'u_type'=>$u_type])->find()){
                $list[$k]['is_zan'] = 1;
            }

            //查评论
            $comment = M('Comment')
                ->alias('cm')
                ->join('LEFT JOIN fzm_circle c ON c.id=cm.cid')
                ->where(['cid'=>$item['circle_id']])
                ->field('cm.id as comment_id, cm.content, cm.uid, cm.u_type, cm.item_id, cm.item_type')
                ->select();
            foreach ($comment as &$c){
                if ($c['u_type']==1){
                    //家长用户
                    $user = M('Parents')->where(['id'=>$c['uid']])->find();
                    $c['img'] = $user['p_img'];
                    $c['uname'] = $user['parent_name'];

                    if ($u_type==1){
                        //判断是否是自己发的
                        if($c['uid']==$id){
                            $c['my'] = 1;
                        }
                    }
                }elseif ($c['u_type']==2){
                    //老师用户
                    $user = M('Teacher')->where(['id'=>$c['uid']])->find();
                    $c['img'] = $user['t_img'];
                    $c['uname'] = $user['name'];

                    if ($u_type==2){
                        //判断是否是自己发的
                        if($c['uid']==$id){
                            $c['my'] = 1;
                        }
                    }
                }elseif ($c['u_type']==3){
                    //商户用户
                    $user = M('SmallTable')->where(['id'=>$c['uid']])->find();
                    $c['img'] = $user['s_img'];
                    $c['uname'] = $user['s_name'];

                    if ($u_type==3){
                        //判断是否是自己发的
                        if($c['uid']==$id){
                            $c['my'] = 1;
                        }
                    }
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

            $list[$k]['comment'] = $comment;
        }

        $this->ApiReturn(1, 'success', $list);
    }

    /**
     * 用户个人主页
     * @param $u_type 1.家长 2.老师 3.商户
     */
    public function person($u_type)
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = !empty($data['page'])?$data['page']:1;
        $db = M('Circle');
        $where = [];
        $where['uid'] = !empty($data['uid'])?$data['uid']:$this->ApiReturn(-1, 'uid不能为空');
        $where['u_type'] = !empty($data['u_type'])?$data['u_type']:$this->ApiReturn(-1, 'u_type不能为空'); //1家长 2.老师 3.商户 4.平台
        $where['option'] = !empty($data['option'])?$data['option']:1;    //默认亲子时光--1.亲子时光 2.青春日记

        $list = $db
            ->alias('c')
            ->where($where)
            ->page($page)
            ->order('c_time DESC')
            ->field('c.id as circle_id, c_time, content, img_list, video_url, zan, c.view_num, c_type, uid, u_type, video_cover, option, comment_num')
            ->select();

        foreach ($list as $k=>$item) {
            $list[$k]['is_person'] = 1;
            $list[$k]['c_time'] = substr($item['c_time'], 5);

            //图文
            $img_list = $item['img_list'];
            $list[$k]['img_list']=[];
            if($item['c_type']==2){
                $list[$k]['img_list'] = explode(',', $img_list);
            }

            $list[$k]['my'] = -1;   //不是自己发的
            if ($item['u_type']==1){
                //家长用户
                $user = M('Parents')->where(['id'=>$item['uid']])->find();
                $list[$k]['img'] = $user['p_img'];
                $list[$k]['uname'] = $user['parent_name'];

                //是否关注了该家长
                $where_c['type'] = 3;
            }elseif ($item['u_type']==2){
                //老师用户
                $user = M('Teacher')->where(['id'=>$item['uid']])->find();
                $list[$k]['img'] = $user['t_img'];
                $list[$k]['uname'] = $user['name'];

                //是否关注了该老师
                $where_c['type'] = 1;
            }elseif ($item['u_type']==3){
                //商户用户
                $user = M('SmallTable')->where(['id'=>$item['uid']])->find();
                $list[$k]['img'] = $user['s_img'];
                $list[$k]['uname'] = $user['s_name'];

                //是否关注了该商户
                $where_c['type'] = 2;
            }else{
                //后台用户
                $list[$k]['img'] = "/public/images/img_new.png";
                $list[$k]['uname'] = M('Config')->getField('circle_name');

                $where_c['type'] = 0;
            }

            //是否关注了该老师、家长、商户
            $list[$k]['collection'] = -1;
            $where_c['parent_id'] = $id;
            $where_c['item_id'] = $item['uid'];
            if (M('Collection')->where($where_c)->find()){
                $list[$k]['collection'] = 1;
            }

            //是否点赞了
            $list[$k]['is_zan'] = -1;
            if (M('CircleLog')->where(['uid'=>$id, 'circle_id'=>$item['circle_id'], 'u_type'=>$u_type])->find()){
                $list[$k]['is_zan'] = 1;
            }

            //查评论
            $comment = M('Comment')
                ->alias('cm')
                ->join('LEFT JOIN fzm_circle c ON c.id=cm.cid')
                ->where(['cid'=>$item['circle_id']])
                ->field('cm.id as comment_id, cm.content, cm.uid, cm.u_type, cm.item_id, cm.item_type')
                ->select();
            foreach ($comment as &$c){
                if ($c['u_type']==1){
                    //家长用户
                    $user = M('Parents')->where(['id'=>$c['uid']])->find();
                    $c['img'] = $user['p_img'];
                    $c['uname'] = $user['parent_name'];

                    if ($u_type==1){
                        //判断是否是自己发的
                        if($c['uid']==$id){
                            $c['my'] = 1;
                        }
                    }
                }elseif ($c['u_type']==2){
                    //老师用户
                    $user = M('Teacher')->where(['id'=>$c['uid']])->find();
                    $c['img'] = $user['t_img'];
                    $c['uname'] = $user['name'];

                    if ($u_type==2){
                        //判断是否是自己发的
                        if($c['uid']==$id){
                            $c['my'] = 1;
                        }
                    }
                }elseif ($c['u_type']==3){
                    //商户用户
                    $user = M('SmallTable')->where(['id'=>$c['uid']])->find();
                    $c['img'] = $user['s_img'];
                    $c['uname'] = $user['s_name'];

                    if ($u_type==3){
                        //判断是否是自己发的
                        if($c['uid']==$id){
                            $c['my'] = 1;
                        }
                    }
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

            $list[$k]['comment'] = $comment;

            $db->where(['id'=>$item['circle_id']])->setInc('view_num');
        }

        $this->ApiReturn(1, 'success', $list);
    }

    public function zan($u_type)
    {
        $data = $this->data;
        $id = S($data['token']);
        $cid = !empty($data['cid'])?$data['cid']:$this->ApiReturn(-1, 'false');
        $db = M('CircleLog');
        if ($db->where([
            'uid'       =>  $id,
            'circle_id' =>  $cid,
            'u_type'    =>  $u_type
        ])->find()){
            $this->ApiReturn(-1, '您已经赞过了！');
        }
        $db->add([
            'uid'       =>  $id,
            'circle_id' =>  $cid,
            'l_time'    =>  date('Y-m-d H:i:s'),
            'u_type'    =>  $u_type
        ]);
        M('Circle')->where(['id'=>$cid])->setInc('zan');
        $this->ApiReturn(1, 'success');
    }

    public function delete()
    {
        $data = $this->data;
        $id = S($data['token']);
        $cid = !empty($data['cid'])?$data['cid']:$this->ApiReturn(-1, 'false');
        if (!M('Circle')->where(['id'=>$cid, 'uid'=>$id])->find()){
            $this->ApiReturn(-1, '非法操作');
        }
        M('CircleLog')->where(['circle_id'=>$cid])->delete();
        M('Circle')->delete($cid);
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
        $data = $this->data;
        $id = S($data['token']);
        $cid = $data['cid']??$this->ApiReturn(-1, 'id不能为空');
        $circleid = M('Comment')->where(['id'=>$cid])->getField('cid');
        M('Comment')->delete($cid);
        M('Circle')->where(['id'=>$circleid])->setDec('comment_num');
        $this->ApiReturn(1, 'success');
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

    /**
     * 举报原因
     */
    public function getReportReason()
    {
        $list = M('ReportReason')->field('id as rrid, title')->select();
        if (!$list){
            $this->ApiReturn(0, '没有数据');
        }
        $this->ApiReturn(1, 'success', $list);
    }

    /**
     * 举报视频
     * 二期
     * sunfan
     * 2018.7.12
     */
    public function reportCircle()
    {
        $data = $this->data;
        $id = S($data['token']);

        $first = substr($data['token'] , 0 , 1);
        if ($first=="p"){
            $u_type=1;
        }elseif ($first=="t"){
            $u_type=2;
        }else{
            $u_type=3;
        }
        $reason = $data['reason']??$this->ApiReturn(-1, 'reason不能为空');
        $cid = $data['cid']??$this->ApiReturn(-1, 'id不能为空');
        $model = M("Circle");
        $info = $model->where(['id'=>$cid])->setInc('report_num');

        M('ReportLog')->add([
            'cid'   =>  $cid,
            'uid'   =>  $id,
            'u_type'=>  $u_type,
            'reason'=>  $reason
        ]);
        $this->ApiReturn(1, '举报成功');
    }
}