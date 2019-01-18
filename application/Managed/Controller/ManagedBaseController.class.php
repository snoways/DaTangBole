<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/4 0004
 * Time: 下午 03:51
 */

namespace Managed\Controller;

use Think\Controller;

class ManagedBaseController extends Controller
{

    function _initialize(){
        if (!$_SESSION['small_id'])
        {
            $this->redirect('Managed/Auth/login');
        }
    }

    /**
     *
     * {@inheritDoc}
     * @see \Common\Controller\AppframeController::page()
     */
    protected function page($total_size = 1, $page_size = 0, $current_page = 1, $listRows = 6, $pageParam = '', $pageLink = '', $static = false) {
        if ($page_size == 0) {
            $page_size = C("PAGE_LISTROWS");
        }

        if (empty($pageParam)) {
            $pageParam = C("VAR_PAGE");
        }

        $page = new \Page($total_size, $page_size, $current_page, $listRows, $pageParam, $pageLink, $static);
        $page->SetPager('Admin', '{first}{prev}&nbsp;{liststart}{list}&nbsp;{next}{last}<span>共{recordcount}条数据</span>', array("listlong" => "4", "first" => "首页", "last" => "尾页", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => ""));
        return $page;
    }

}