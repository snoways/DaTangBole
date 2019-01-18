<?php
/**
 * Created by PhpStorm.
 * User: Sunfan
 * Date: 2017/12/26 0026
 * Time: 上午 10:41
 */

namespace ManagedClient\Controller;



use Client\Controller\UserApiBaseController;

class TeacherController extends UserApiBaseController
{
    //师资


    public function index(){
        $data = $this->data;
        $id = S($data['token']);
        $page = $data['page']??1;
        $where = array();
        $where['st_id'] = $id;
        $model = M("SmallTeacher");
        $info = $model
            ->where($where)
            ->order(['top'=>'desc', 'create_time'=>'desc'])
            ->page($page)
            ->field('id as teacher_id, name, desc, img, top')
            ->select();
        if (!$info){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $info);
        }
    }

    public function getDetail()
    {
        $data = $this->data;
        $id = S($data['token']);
        $teacher_id = $data['teacher_id']??$this->ApiReturn(-1, 'teacher_id不能为空');
        $info = M('SmallTeacher')->where(['id'=>$teacher_id])->field('id as teacher_id, name, desc, img, top, content')->find();
        if (!$info){
            $this->ApiReturn(-1, '不存在');
        }else{
            $this->ApiReturn(1, 'success', $info);
        }
    }


    //活动上架、下架
    public function recommend()
    {
        $data = $this->data;
        $id = S($data['token']);
        $teacher_id = $data['teacher_id']??$this->ApiReturn(-1, 'teacher_id不能为空');
        $top = $data['top']??$this->ApiReturn(-1, 'top不能为空');
        M('SmallTeacher')->where(array('id'=>$teacher_id))->save(['top'=>$top]);
        $this->ApiReturn(1, 'success');
    }

    public function del()
    {
        $data = $this->data;
        $id = S($data['token']);
        $teacher_id = $data['teacher_ids']??$this->ApiReturn(-1, 'teacher_ids不能为空');
        $del = M('SmallTeacher')->where(array('id'=>['in', $teacher_id]))->delete();
        $this->ApiReturn(1, 'success');
    }
}