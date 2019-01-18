<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/4 0004
 * Time: 下午 01:53
 */

namespace ManagedClient\Controller;

use Client\Controller\UserApiBaseController;

class CourseController extends UserApiBaseController
{

    public function index()
    {
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $where['status'] = ['neq', 3];
        $where['st_id'] = $id;

        $lists = M('EducationalCourse')  
            ->where($where)
            ->order("add_time DESC")
            ->field('id as ec_id, title, img, grade, sub1, sub2, price, class as total_class')
            ->page($page)
            ->select();
        if (!$lists){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $lists);
        }
    }

    public function add()
    {
        $data = $this->data;
        $id = S($data['token']);
        $title = !empty($data['title'])?$data['title']:$this->ApiReturn(-1, '标题不能为空');
        $img = !empty($data['img'])?$data['img']:$this->ApiReturn(-1, '缩略图不能为空');
        $grade = !empty($data['grade'])?$data['grade']:$this->ApiReturn(-1, '年级不能为空');
        $sub1 = !empty($data['sub1'])?$data['sub1']:$this->ApiReturn(-1, '科目不能为空');
        $sub2 = !empty($data['sub2'])?$data['sub2']:$this->ApiReturn(-1, '科目不能为空');
        $price = !empty($data['price'])?$data['price']:$this->ApiReturn(-1, '总费用不能为空');
        $class = !empty($data['total_class'])?$data['total_class']:$this->ApiReturn(-1, '总课时不能为空');
        $tui_price = !empty($data['tui_price'])?$data['tui_price']:$this->ApiReturn(-1, '单节退款费不能为空');
        $no_class = !empty($data['no_class'])?$data['no_class']:$this->ApiReturn(-1, '不能退款天数不能为空');
        $content = !empty($data['content'])?$data['content']:$this->ApiReturn(-1, '服务详情介绍不能为空');
        $refunds = !empty($data['refunds'])?$data['refunds']:$this->ApiReturn(-1, '退款说明不能为空');

        $id = M('EducationalCourse')->add([
            'st_id'     =>  $id,
            'title'     =>  $title,
            'img'       =>  $img,
            'grade'     =>  $grade,
            'sub1'      =>  $sub1,
            'sub2'      =>  $sub2,
            'price'     =>  $price,
            'class'     =>  $class,
            'tui_price' =>  $tui_price,
            'no_class'   =>  $no_class,
            'content'   =>  $content,
            'add_time'   =>  date('Y-m-d H:i:s'),
            'refunds'   =>  $refunds
        ]);

        if($id){
            $this->ApiReturn(1, '添加成功');
        }else{
            $this->ApiReturn(0, '网络错误');
        }

    }


    public function del()
    {
        $data = $this->data;
        $id = S($data['token']);
        $ids = !empty($data['ec_ids'])?$data['ec_ids']:$this->ApiReturn(-1, 'id不能为空');
        $del = M('EducationalCourse')->where(array('id'=>['in', $ids], 'st_id'=>$id))->delete();
        $this->ApiReturn(1, '删除成功');
    }

    public function getDetail()
    {
        $data = $this->data;
        $id = S($data['token']);
        $ec_id = !empty($data['ec_id'])?$data['ec_id']:$this->ApiReturn(-1, 'id不能为空');
        $rs = M('EducationalCourse')
            ->where(['id'=>$ec_id, 'st_id'=>$id])
            ->field('id as ec_id, title, img, grade, sub1, sub2, price, class as total_class, tui_price, no_class, content ,refunds')
            ->find();
        $this->ApiReturn(1, 'success', $rs);
    }

    public function edit()
    {
        $data = $this->data;
        $id = S($data['token']);
        $ec_id = !empty($data['ec_id'])?$data['ec_id']:$this->ApiReturn(-1, '课程id不能为空');
        $title = !empty($data['title'])?$data['title']:$this->ApiReturn(-1, '标题不能为空');
        $img = !empty($data['img'])?$data['img']:$this->ApiReturn(-1, '缩略图不能为空');
        $grade = !empty($data['grade'])?$data['grade']:$this->ApiReturn(-1, '年级不能为空');
        $sub1 = !empty($data['sub1'])?$data['sub1']:$this->ApiReturn(-1, '科目不能为空');
        $sub2 = !empty($data['sub2'])?$data['sub2']:$this->ApiReturn(-1, '科目不能为空');
        $price = !empty($data['price'])?$data['price']:$this->ApiReturn(-1, '总费用不能为空');
        $class = !empty($data['total_class'])?$data['total_class']:$this->ApiReturn(-1, '总课时不能为空');
        $tui_price = !empty($data['tui_price'])?$data['tui_price']:$this->ApiReturn(-1, '单节退款费不能为空');
        $no_class = !empty($data['no_class'])?$data['no_class']:$this->ApiReturn(-1, '不能退款天数不能为空');
//        $content = !empty($data['content'])?$data['content']:$this->ApiReturn(-1, '服务详情介绍不能为空');
        $refunds = !empty($data['refunds'])?$data['refunds']:$this->ApiReturn(-1, '退款说明不能为空');

        M('EducationalCourse')->where(['id'=>$ec_id])->save([
            'title'     =>  $title,
            'img'       =>  $img,
            'grade'     =>  $grade,
            'sub1'      =>  $sub1,
            'sub2'      =>  $sub2,
            'price'     =>  $price,
            'class'     =>  $class,
            'tui_price' =>  $tui_price,
            'no_class'   =>  $no_class,
//            'content'   =>  $content,
            'refunds'   =>  $refunds
        ]);
        $this->ApiReturn(1, '添加成功');
    }

    //选择科目列表
    public function subject()
    {
        $list = M('Subject')->where(array('parentid'=>0, 'show'=>1))->order('sort asc')->select();
        foreach($list as &$v){
            $v['child'] = M('Subject')->where(array('parentid'=>$v['id']))->order('sort asc')->select();
            foreach ($v['child'] as &$value) {
                $value['child'] = M('Subject')->where(array('parentid'=>$value['id']))->order('sort asc')->select();
            }
        }
        if(!$list){
            $this->ApiReturn(0,'没有科目列表');
        }
        $this->ApiReturn(1,'成功',$list);
    }

    //选择年级
    public function chose()
    {
        $data = $this->data;
        $id = S($data['token']);
        $list = M('Grade')->select();
        if(!$list){
            $this->ApiReturn(0,'没有年级列表');
        }
        $this->ApiReturn(1,'成功',$list);
    }
}