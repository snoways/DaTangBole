<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/12/8
 * Time: 16:24
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;
require('./application/Common/common/ServerAPI.php');

class ChatController extends AdminbaseController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->AppKey = C('NetEaseConf.APPID');
        $this->AppSecret = C('NetEaseConf.APPSECRET');
    }


    public function index()
    {
        if (IS_POST)
        {
            if (!$_POST['t_phone'] || !$_POST['p_phone'] || !$_POST['start_time'] || !$_POST['end_time'])
            {
                $this->error('条件不能为空');
            }
            if (I('type')==1){
                $teacher = M('Teacher')->where(['phone'=>$_POST['t_phone']])->find();
                $tid = "t".$teacher['id'];
            }else{
                $SmallTable = M('SmallTable')->where(['s_phone'=>$_POST['t_phone']])->find();
                $tid = "s".$SmallTable['id'];
            }

            $parents = M('Parents')->where(['phone'=>$_POST['p_phone']])->find();
            $pid = "p".$parents['id'];

            $from=$tid;$to=$pid;
            if ($_POST['type']==2)
            {
                $from=$pid;$to=$tid;
            }

            $p = new \ServerAPI($this->AppKey,$this->AppSecret,'curl');		//php curl库
            $result_json = $p->querySessionMsg($from, $to, strtotime($_POST['start_time'])*1000, strtotime($_POST['end_time'])*1000, 100, 2);
            if ($result_json['code']==200)
            {
                $this->assign('list', $result_json['msgs']);
            }

        }
        $this->display();
    }
}