<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/6
 * Time: 15:13
 */
namespace Teacher\Controller;

use Client\Controller\UserApiBaseController;

class ApproveController extends UserApiBaseController
{
    //实名认证列表
    public function certif_info()
    {
        $data = $this->data;
        $id = S($data['token']);
        $teacher = M('Teacher');
        $type = $data['t_type'];          //1家教  2老师  3外教
        if(!in_array($type,[1,2,3]))$this->ApiReturn(-1,'参数错误');
//        if($type == 1 || $type == 2){
//            $field = 't_card,idcard1,idcard2,hand_card,academic,health,state';
//        }else{
//            $field = 't_card,academic,nationality,state';
//        }
        if($type == 3){
            $info['nationality'] = $teacher->where(array('id'=>$id))->getField('nationality');
        }
        $info['list'] = D("examine")->where(['uid'=>$id])->field('option_key,option_value,status')->select();
        $info['fail_reason'] = $teacher->where(array('id'=>$id))->getField('fail_reason');

        $this->ApiReturn(1,'成功',$info);
    }

//实名认证提交
    public function certif()
    {
        $data = $this->data;
        $type = $data['type'];
        if(!in_array($type,[1,2,3]))$this->ApiReturn(-1,'参数错误');        //教师类型
        $id = S($data['token']);
        if($type == 1 || $type == 2){
            if($data['t_card'] !=""){       //教师资格证
                $t_card = image($data['t_card']);
                $where['t_card'] = $t_card;
            }
            if($data['idcard1'] !=""){      //正面
                $idcard1 = image($data['idcard1']);
                $where['idcard1'] = $idcard1;
            }
            if($data['idcard2'] !=""){      //反面
                $idcard2 = image($data['idcard2']);
                $where['idcard2'] = $idcard2;
            }
            if($data['hand_card'] !=""){        //手持
                $hand_card = image($data['hand_card']);
                $where['hand_card'] = $hand_card;
            }
            if($data['academic'] !=""){     //学历证
                $academic = image($data['academic']);
                $where['academic'] = $academic;
            }
            if($data['health'] !=""){           //健康证
                $health = image($data['health']);
                $where['health'] = $health;
            }
        }else{
            if($data['health'] !=""){           //健康证
                $health = image($data['health']);
                $where['health'] = $health;
            }
            if($data['t_card'] !=""){       //教师资格证
                $t_card = image($data['t_card']);
                $where['t_card'] = $t_card;
            }
            if($data['academic'] !=""){     //学历证
                $academic = image($data['academic']);
                $where['academic'] = $academic;
            }
             if($data['nationality'] !=""){     //国籍
                 $nationality = $data['nationality'];
                 $where['nationality'] = $nationality;
             }
        }


        if(count($where) ==0 ){
            $this->ApiReturn(-1,'没有添加要上传的内容');
        }

        $small_info = D("teacher")->where(['id'=>$id])->find();
        (!empty($where['t_card']) && $where['t_card'] != $small_info['t_card'])?$arr['t_card'] = $where['t_card']:1;
        (!empty($where['idcard1']) && $where['idcard1'] != $small_info['idcard1'])?$arr['idcard1'] = $where['idcard1']:1;
        (!empty($where['idcard2']) && $where['idcard2'] != $small_info['idcard2'])?$arr['idcard2'] = $where['idcard2']:1;
        (!empty($where['hand_card']) && $where['hand_card'] != $small_info['hand_card'])?$arr['hand_card'] = $where['hand_card']:1;
        (!empty($where['academic']) && $where['academic'] != $small_info['academic'])?$arr['academic'] = $where['academic']:1;
        (!empty($where['health']) && $where['health'] != $small_info['health'])?$arr['health'] = $where['health']:1;

        $examine = D("examine");
        foreach($arr as $k=>$v){
            $examine->where(['option_key'=>$k,'uid'=>$id])->delete();
            $add = [
                'option_key'=> $k,
                'option_value'=>$v,
                'uid'       => $id
            ];
            $examine->add($add);
        }
        if($data['state'] != 2){
            $where['state'] = 1;
        }


        M('Teacher')->where(['id'=>$id])->save($where);
        $this->ApiReturn(1,'上传成功');
    }

    //教育机构认证
    public function educa()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['name'] ==""){
            $this->ApiReturn(-1, '机构名称不存在');
        }
        if($data['address'] ==""){
            $this->ApiReturn(-1, '地址不存在');
        }
        if($data['phone'] ==""){
            $this->ApiReturn(-1, '电话不存在');
        }
        if($data['content'] ==""){
            $this->ApiReturn(-1, '内容不存在');
        }
        $add = M('Mechanism')->add(array('name'=>$data['name'],'address'=>$data['address'],'phone'=>$data['phone'],'content'=>$data['content'],'m_time'=>date('Y-m-d H:i:s'),'status'=>1));
        if(!$add){
            $this->ApiReturn(-1,'提交失败');
        }
        $this->ApiReturn(1,'提交成功');
    }

    //测评题库
    public function question()
    {
        $list = M('Question')->select();
        foreach($list as $k=>$v){
            $list[$k]['answer'] = M('Answer')->where(array('q_id'=>$v['id']))->select();
        }
        if(!$list){
            $this->ApiReturn(-1,'题库为空');
        }
        $this->ApiReturn(1,'成功',$list);
    }

    //测评结果
    public function upshot()
    {
        $data = $this->data;
        $id = S($data['token']);
        if($data['score'] ==""){
            $this->ApiReturn(-1, '分数不存在');
        }
        $find = M('ScoreDesc')->where(array('sleft <='.$data['score'],'sright >'.$data['score']))->find();
        //$find = M('ScoreDesc')->where(['sleft'=>['<=', $data['score']], 'sright'=>['>', $data['score']]])->find();
        if(!$find){
            $find = M('ScoreDesc')->order('sleft desc')->find();
            $find['eval_score'] = $find['sleft'];
            $save = M('Teacher')->where(array('id'=>$id))->save(array('evaluation'=>$find['desc'],'eval_score'=>$find['sleft']));
            $this->ApiReturn(1,'成功',$find);
        }
        $find['eval_score'] = $data['score'];
        $save = M('Teacher')->where(array('id'=>$id))->save(array('evaluation'=>$find['desc'],'eval_score'=>$data['score']));
        $this->ApiReturn(1,'成功',$find);
    }

    //测评结果查询
    public function search_score()
    {
        $data = $this->data;
        $id = S($data['token']);
        $find = M('Teacher')->field('evaluation,eval_score')->where(array('id'=>$id))->find();
        $this->ApiReturn(1,'成功',$find);
    }

}