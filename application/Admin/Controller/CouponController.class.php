<?php
/**
 * Created by PhpStorm.
 * User: lixinguo
 * Date: 2017/12/26 0026
 * Time: 上午 10:41
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class CouponController extends AdminbaseController
{
    //满减优惠券
    public function index(){
        $where = array();
        $where['m.type'] = 1;
        $where['m.u_type'] = 1;
        $model = M("coupon");
        $count = $model->alias('m')
            ->join("LEFT JOIN fzm_pay_position pp on pp.id = m.pp_id")
            ->where($where)->count();
        $page = $this->page($count,15);
        $info = $model->alias('m')
            ->join("LEFT JOIN fzm_pay_position pp on pp.id = m.pp_id")
            ->order('create_time desc')->where($where)
            ->limit($page->firstRow,$page->listRows)
            ->field('m.*,pp.name')
            ->select();

        //检查一遍，把过期的优惠券改为失效
        foreach ($info as $item){
            if ($item['expire_date']<date('Y-m-d H:i:s')){
                $model->where(['id'=>$item['id']])->save(['c_status'=>2]);
            }
        }

        $this->assign('page',$page->show('Admin'));
        $this->assign('info',$info);
        $this->display();
    }

    //优惠券使用情况
    public function index_coupon(){
        $id = I('id',0,'intval');
        $where = array();
        $where['r.coupon_id'] = $id;
        if(!empty(I('keyword'))){
            $keyword=trim(I('keyword'));
            $keyword_complex=array();
            $keyword_complex['m.parent_name']  = array('like', "%$keyword%");
            $keyword_complex['c.title']  = array('like',"%$keyword%");
            $keyword_complex['_logic'] = 'or';
            $where['_complex'] = $keyword_complex;
        }
        $count = M('CouponRecords')->alias('r')
            ->join('LEFT JOIN fzm_coupon c on r.coupon_id = c.id')
            ->join('LEFT JOIN fzm_parents m on r.uid = m.id')
            ->join('LEFT JOIN fzm_pay_position pp on c.pp_id = pp.id')
            ->where($where)
            ->count();
        $page = $this->page($count,15);
        $info = M('CouponRecords')->alias('r')
            ->join('LEFT JOIN fzm_coupon c on r.coupon_id = c.id')
            ->join('LEFT JOIN fzm_parents m on r.uid = m.id')
            ->join('LEFT JOIN fzm_pay_position pp on c.pp_id = pp.id')
            ->field('r.*,m.parent_name as user_name,c.title,c.type,pp.name')
            ->limit($page->firstRow,$page->listRows)
            ->order('r.create_time desc')
            ->where($where)
            ->select();
        $this->assign('page',$page->show('Admin'));
        $this->assign('id',$id);
        $this->assign('info',$info);
        $this->display();

    }
    //发放抵现优惠券
    public function grant(){
        $id = I("id");
        $this->assign('id',$id);
        $this->display();
    }
    //添加优惠券
    public function add(){
        if(IS_POST){
            $data['pp_id']=I('pp_id');
            $data['title'] = I('title','未命名');
            $data['money'] = I('money',0);
            $data['min_consumption'] = I('min_consumption',0);
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['start_date'] = I('start_date',date('Y-m-d H:i:s'));
            $data['expire_date'] = I('expire_date',date('Y-m-d H:i:s'));
            $info = M('coupon')->add($data);
            if($info){
                $this->success('添加成功！',U('Coupon/index'));
            }else{
                $this->error('网络错误！');
            }
        }else{
            $terms = M('PayPosition')->select();
            $this->assign('terms',$terms);
            $this->display();
        }
    }
    //编辑优惠券
    public function edit_index(){
        if(IS_POST){
            $id = I("post.id");
            $data['pp_id'] = I('pp_id', 1);
            $data['title'] = I('title','未命名');
            $data['money'] = I('money',0);
            $data['min_consumption'] = I('min_consumption',0);
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['start_date'] = I('start_date',date('Y-m-d H:i:s'));
            $data['expire_date'] = I('expire_date',date('Y-m-d H:i:s'));

            $info = M('coupon')->where(array('id'=>$id))->save($data);
            if($info !== false){
                $this->success('保存成功！',U('Coupon/index'));
            }else{
                $this->error('网络错误！');
            }
        }else{
            $id = I("get.id",0,'intval');
            $info = M("coupon")->where(array('id'=>$id))->find();
            $this->assign('info',$info);

            $terms = M('PayPosition')->select();
            $this->assign('terms',$terms);
            $this->display();
        }
    }
    //禁用优惠券
    public function ban(){
        $type = I("type");
        $id = I('id');
        $status = I('c_status');
        if($id){
            if($status){
                $info = M('coupon')->where(array('id'=>$id))->save(array('c_status'=>$status));
                if($info){
                    if($type == 1){
                        $this->success('保存成功！',U('Coupon/index'));
                    }else{
                        $this->success('保存成功！',U('coupon/arrived'));
                    }
                }else{
                    $this->error('网路错误！');
                }
            }else{
                $this->error('数据失败！');
            }

        }else{
            $this->error('数据传入失败！');
        }
    }
    //删除优惠券
    public function delete(){
        $id = I('get.id',0,'intval');
        $type = I('get.type',0,'intval');
        $info = M('coupon')->where(array('id'=>$id))->delete();
        if($info){
            M('CouponRecords')->where(array('coupon_id'=>$id))->delete();
            if($type == 1){
                $this->success('删除成功！',U('Coupon/index'));
            }else{
                $this->success('删除成功！',U('coupon/arrived'));
            }
        }else{
            $this->error('网络错误！');
        }
    }

    //通过关键字搜索会员
    public function btn(){
        $where = array();
        if(!empty(I('keyword'))){
            $keyword=trim(I('keyword'));
            $where['user_name|phone']  = array('like',"%$keyword%");
        }
        $users_model = M('member');
        $list = $users_model->where($where)->select();
        echo json_encode($list);
        exit();
    }
    //判断优惠券数量并赋值
    public function coupon_num(){
        $coupon_id = I('id');                  //优惠券id
        $num = I('num');                       //发放的优惠券数量
        $member_id = I('user_id');             //发放人
        $msg = M('coupon')->where(array('id'=>$coupon_id))->find();
        $arr = array();
        if($num > $msg['num']){
            $arr['code'] = -1;   //库存不足！
            echo json_encode($arr);
            exit();
        }else{
            $data['coupon_id'] = $coupon_id;
            $data['member_id'] = $member_id;
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['money'] = $msg['money'];
            $data['expire_date'] = $msg['expire_date'];
            $data['start_date'] = $msg['start_date'];
//            dump($data['expire_date']);die;
            for($i=0;$i<$num;$i++){
                $info = M('coupons_records')->add($data);
            }
            if($info){
                $count = intval($msg['num']) - intval($num);
                $mes = M("coupon")->where(array('id'=>$coupon_id))->save(array('num'=>$count));
                if($mes){
                    $arr['code'] = 1;     //成功
                    echo json_encode($arr);
                    exit();
                }else{
                    $arr['code'] = 2;       //库存修改失败！
                    echo json_encode($arr);
                    exit();
                }
            }else{
                $arr['code'] = 3;      //加入失败
                echo json_encode($arr);
                exit();
            }
        }
    }
}