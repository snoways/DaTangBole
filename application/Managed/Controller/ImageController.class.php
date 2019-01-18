<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/3 0003
 * Time: 下午 02:33
 */

namespace Managed\Controller;

use Think\Page;

class ImageController extends ManagedBaseController
{

    /**
     * 图集管理
     */
    public function index()
    {
        $small_id = $_SESSION['small_id'];
        $count=M('small_images')
            ->where("st_id='{$small_id}'")
            ->count();
        $page = new Page($count, 20);

        $list = M('small_images')
            ->where("st_id='{$small_id}'")
            ->order('sort')
            ->limit($page->firstRow . ',' . $page->listRows)->select();

        if($list[0]['st_id']){
            $image = $list;
        }else{
            $image = '';
        }
        $this->assign('image',$image);
        $info = M('small_table')->where(array('id'=>$small_id))->find();
        $this->assign('info',$info);

        $this->assign("page", $page->show('Admin'));
        $this->display();
    }

    /**
     * 批量删除图集
     */
    public function del_batch_url(){
        $ids = I('ids');
        $pro = M('small_images');
        foreach ($ids as $id){
            $tu1 = $pro->field('url')->find($id);
            $rootpath1 = './Date/Upload/admin/';
            //删除文件夹中的图片
            unlink('.'.$tu1['url']);
            $del = $pro->delete($id);
        }
        $this->success('删除成功');
    }

    /**
     * 批量添加
     */
    public function add()
    {
        if (IS_POST){
            $data['st_id'] = $_SESSION['small_id'];
            foreach ($_POST['photos_url'] as $img){
                $data['url'] = image('/'.$img);
                $rees = M('small_images')->add($data);
            }
            $this->success('添加成功', U('Image/index'));
            exit();
        }
        $small_id = $_SESSION['small_id'];
        $rs = M('SmallTable')->where(['id'=>I('id')])->find();
        $this->assign('rs', $rs);
        $info = M('small_table')->where(array('id'=>$small_id))->find();
        $this->assign('info',$info);
        $this->display();
    }


    public function sort(){
        if ($_POST){
            foreach ($_POST['imgid'] as $k=>$item){
                M('small_images')->where(['id'=>$item])->save(['sort'=>$_POST['sort'][$k]]);
            }
            $this->success('保存成功！');
            exit();
        }
        $id = I('id',0,'intval');
        $list = M('small_images')->where("st_id='{$id}'")->order('sort asc')->select();
        if($list[0]['st_id']){
            $image = $list;
        }else{
            $image = '';
        }
        $this->assign('image',$image);
        $this->display();
    }

    /**
     * 删除图集
     */
    public function del(){
        $id = I('id');
        $pro = M('small_images');
        $tu1 = $pro->field('url')->find($id);
        $rootpath1 = './Date/Upload/admin/';
        //删除文件夹中的图片
        unlink('.'.$tu1['url']);
        $del = $pro->delete($id);
        echo json_encode(['code'=>1]);
    }
}