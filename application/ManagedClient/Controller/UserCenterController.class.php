<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/17 0017
 * Time: 下午 01:02
 */

namespace ManagedClient\Controller;


use Client\Controller\UserApiBaseController;

class UserCenterController extends UserApiBaseController
{

    public function index()
    {
        $data = $this->data;
        $id = S($data['token']);
        $info = M('SmallTable')
            ->where(array('id'=>$id))
//            ->field('s_img as logo, address, s_name, s_phone, s_content as content, c_name, s_status, s_type, business_license, holding_card, card1, card2, store1, store2, fail_reason')
            ->field(' s_status, s_type, fail_reason')
            ->find();
        $info['list'] = M("examine_table")->where(['uid'=>$id])->field('option_key,option_value,status')->select();

        //会员是否开启的功能
        $arr['svip_option'] = M('Config')->getField('svip_option');
        $this->ApiReturn(1, 'success', $info);
    }

    /**
     * 查看我的访客
     */
    public function visitor()
    {
        $data = $this->data;
        $id = S($data['token']);
        $where['type'] = 2;
        $where['owner_id'] = $id;
        $page = $data['page']??1;

        $oauth_user_model=M('Visitor');
        $lists = $oauth_user_model
            ->alias('v')
            ->join('LEFT JOIN fzm_parents p ON p.id=v.user_id')
            ->where($where)
            ->order("visit_time DESC")
            ->field('user_id, visit_time, parent_name, p_img, phone')
            ->page($page)
            ->select();

        foreach ($lists as $k=>$item){
            $lists[$k]['accid'] = "p".$item['user_id'];
        }
        if (!$lists){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $lists);
        }
    }

    //修改
    public function editInfo()
    {
        $data = $this->data;
        $small = M("SmallTable");
        $token = !empty($data['token'])?$data['token']:$this->ApiReturn(20003, 'token无效或已过期');
        $id = S($token);
        $find = $small->where(array('id'=>$id))->find();
        if(!$find){
            $this->ApiReturn(-1, '账号不存在');
        }

        //如果是没审核的商家，重新提交信息后改为未审核状态
        if ($find['s_status']==3){
            $map['s_status']=1;
        }

        //如果是审核通过的商家，重新提交部分信息后改为未审核
        if (!empty($data['business_license']) || !empty($data['holding_card']) || !empty($data['card1']) || !empty($data['card2'])){
            $map['s_status']=1;
        }

        !empty($data['logo'])?$map['s_img']=$data['logo']:1;
        !empty($data['s_name'])?$map['s_name']=$data['s_name']:1;
        !empty($data['c_name'])?$map['c_name']=$data['c_name']:1;
        !empty($data['address'])?$map['address']=$data['address']:1;
        !empty($data['xy'])?$map['s_xy']=$data['xy']:1;
        !empty($data['business_license'])?$map['business_license']=$data['business_license']:1;
        !empty($data['holding_card'])?$map['holding_card']=$data['holding_card']:1;
        !empty($data['card1'])?$map['card1']=$data['card1']:1;
        !empty($data['card2'])?$map['card2']=$data['card2']:1;
        !empty($data['store1'])?$map['store1']=$data['store1']:1;
        !empty($data['store2'])?$map['store2']=$data['store2']:1;
        !empty($data['content'])?$map['s_content']=$data['content']:1;

        //根据区code，计算属于哪个代理商
        if (!empty($data['area_code'])){
            $map['agent_id'] = M('Users')->where(['areacode'=>['like', "%".$data['area_code']."%"]])->getField('id');
        }
        $small_info = $small->where(['id'=>$id])->find();

        (!empty($map['s_img']) && $map['s_img'] != $small_info['s_img'])?$arr['s_img'] = $map['s_img']:1;
        (!empty($map['s_name']) && $map['s_name'] != $small_info['s_name'])?$arr['s_name'] = $map['s_name']:1;
        (!empty($map['c_name']) && $map['c_name'] != $small_info['c_name'])?$arr['c_name'] = $map['c_name']:1;
        (!empty($map['address']) &&$map['address'] != $small_info['address'])?$arr['address'] = $map['address']:1;
        (!empty($map['business_license']) && $map['business_license'] != $small_info['business_license'])?$arr['business_license'] = $map['business_license']:1;
        (!empty($map['holding_card']) && $map['holding_card'] != $small_info['holding_card'])?$arr['holding_card'] = $map['holding_card']:1;
        (!empty($map['card1']) && $map['card1'] != $small_info['card1'])?$arr['card1'] = $map['card1']:1;
        (!empty($map['card2']) && $map['card2'] != $small_info['card2'])?$arr['card2'] = $map['card2']:1;
        (!empty($map['store1']) && $map['store1'] != $small_info['store1'])?$arr['store1'] = $map['store1']:1;
        (!empty($map['store2']) && $map['store2'] != $small_info['store2'])?$arr['store2'] = $map['store2']:1;
        (!empty($map['s_content']) && $map['s_content'] != $small_info['s_content'])?$arr['s_content'] = $map['s_content']:1;

        if(!empty($arr)){
            $examine = D("examine_table");
            foreach($arr as $k=>$v){
                $examine->where(['option_key'=>$k,'uid'=>$id])->delete();
                $add = [
                    'option_key'=> $k,
                    'option_value'=>$v,
                    'uid'       => $id
                ];

                $examine->add($add);
            }
        }

        $add = $small->where(['id'=>$id])->save($map);
        $this->ApiReturn(1,"成功");
    }

}