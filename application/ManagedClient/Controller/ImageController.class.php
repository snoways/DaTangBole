<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/3 0003
 * Time: 下午 02:33
 */

namespace ManagedClient\Controller;

use Client\Controller\MapiBaseController;
use Client\Controller\UserApiBaseController;

class ImageController extends UserApiBaseController
{

    /**
     * 图集管理
     */
    public function index()
    {
        $data = $this->data;
        $small_id = S($data['token']);
        $page = $data['page']??1;
        $list = M('SmallImages')
            ->where("st_id='{$small_id}'")
            ->order('sort')
            ->page($page)
            ->field('id as iid, url')
            ->select();

        if (!$list){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $list);
        }
    }

    /**
     * 批量删除图集
     */
    public function delBatchUrl(){
        $data = $this->data;
        $small_id = S($data['token']);
        $iids = $data['iids'];
        $pro = M('SmallImages');
        $ids = explode(',', $iids);
        foreach ($ids as $id){
            $tu1 = $pro->field('url')->find($id);
            $rootpath1 = './Date/Upload/admin/';
            //删除文件夹中的图片
            unlink('.'.$tu1['url']);
            $del = $pro->delete($id);
        }
        $this->ApiReturn(1, '删除成功');
    }



    /**
     * 删除图集
     */
    public function del(){
        $data = $this->data;
        $small_id = S($data['token']);
        $id = $data['iid'];
        $pro = M('SmallImages');
        $tu1 = $pro->field('url')->find($id);
        $rootpath1 = './Date/Upload/admin/';
        //删除文件夹中的图片
        unlink('.'.$tu1['url']);
        $del = $pro->delete($id);
        $this->ApiReturn(1, '删除成功');
    }

    public function add()
    {
        $data = $this->data;
        $id = S($data['token']);
        $db = M('SmallImages');
        $url = $data['url']??$this->ApiReturn(-1, '图片地址不能为空');
        $db->add([
           'st_id'      =>  $id,
            'url'       =>  $url,
        ]);
        $this->ApiReturn(1, 'success');
    }
}