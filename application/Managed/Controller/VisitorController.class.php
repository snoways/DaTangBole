<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8 0008
 * Time: 下午 03:19
 */

namespace Managed\Controller;

use Think\Page;
class VisitorController extends ManagedBaseController
{

    /**
     * 查看我的访客
     */
    public function index()
    {
        if (M('SmallTable')->where(['id'=>$_SESSION['small_id']])->getField('level_id')<=1){
            //不是会员
            $this->display('updateTip');
            exit();
        }
        $where['type'] = 2;
        $where['owner_id'] = $_SESSION['small_id'];
        $request=I('request.');

        if(!empty($request['start_time']) && !empty($request['end_time'])){
            $where['visit_time'] = ['between', [$request['start_time'], $request['end_time']]];
        }

        $oauth_user_model=M('Visitor');
        $count=$oauth_user_model
            ->alias('v')
            ->join('LEFT JOIN fzm_parents p ON p.id=v.user_id')
            ->where($where)
            ->count();
        $page = $this->page($count, 15);
        $lists = $oauth_user_model
            ->alias('v')
            ->join('LEFT JOIN fzm_parents p ON p.id=v.user_id')
            ->where($where)
            ->order("visit_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $this->assign("page", $page->show('Admin'));
        $this->assign('lists', $lists);
        $this->display();
    }
}