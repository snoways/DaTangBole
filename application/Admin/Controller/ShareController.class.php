<?php
/**
 * Created by PhpStorm.
 * User: jizhongbao
 * Date: 2018/11/22
 * Time: 15:10
 */

namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class ShareController extends AdminbaseController
{
    private $re_url = [
        1=>'OnlineClass/index',
        2=>'Activity/index',
    ];
    //设置分享
    public function Setting()
    {
        if (IS_POST) {
            $id = I('post.id/d');
            $object_id = I('post.object_id/d');
            $s_type = I('post.type/d');
            $form_request = I('post.form_request');
            if ($id || ($object_id && $s_type)) {
                $share_db = M('share');
                $jumpUrl = U("{$this->re_url[$s_type]}",json_decode(htmlspecialchars_decode($form_request),true));
//                print_r($jumpUrl);die;
                $data = ['title'=>I('post.title',''),'description'=>I('post.description',''),'image_path'=>I('post.image_path')];
                if ($id) {
                    if (!$data['image_path']) $this->error('请选择一张图片图片');
                    if ($data['description']) {
                        preg_match_all('/./us', $data['description'], $match);
                        if (count($match[0])>30) $this->error('描述不能超过30个字符');
                    }
                    $data['update_time'] = date('Y-m-d H:i:s');
                    if ($share_db->create($data)) {
                        if ($share_db->where(['id'=>$id])->setField($data)) {
                            $this->success('成功',$jumpUrl);
                        } else {
                            $this->error('没有任何修改');
                        }
                    } else {
                        $this->error($share_db->getError());
                    }
                } else {
                    $data['object_id'] = $object_id;
                    $data['s_type'] = $s_type;
                    if ($share_db->create($data)) {
                        if ($share_db->add($data)) {
                            $this->success('成功',$jumpUrl);
                        } else {
                            $this->error('失败');
                        }
                    } else {
                        $this->error($share_db->getError());
                    }
                }
            }
        } else {
            $id = I('id/d'  , 0 , 'intval');
            $type = I('type/d' , 0 , 'intval');
            $form_request = I('form_request');
            $ob_title = I('ob_title');
            if ($id && $type && $ob_title) {
                $data = [
                    'object_id' => $id,
                    's_type'    => $type
                ];
                $re = M('share')->where($data)->field('id , title , image_path , description')->find();
                if ($re !== false) {
                    $this->assign('re',$re);
                }
                $this->assign('data',$data);
                $this->assign('ob_title',$ob_title);
                $this->assign('form_request',$form_request??'');
                $this->display();
            }
        }
    }
}