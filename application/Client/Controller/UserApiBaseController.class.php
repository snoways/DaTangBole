<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/2/4
 * Time: 11:38
 */

namespace Client\Controller;

class UserApiBaseController extends MapiBaseController
{
    protected function _initialize(){
        header("Access-Control-Allow-Origin: *");
        $this->data = $_REQUEST;
        $this->writeLog("收到用户:".json_encode($this->data));
        if(is_null($this->data)){
            $this->ApiReturn(20000,'参数格式错误，除文件上传外所有参数格式为JSON格式');
        }
        $data = $this->data;
        $token = $data['token'];
        // dump($_SESSION);
        // echo(S($token));
        if(!S($token)){
            $this->ApiReturn(20003,'token无效或已过期','');
        }
    }


    /**
     * 增加一条访客记录
     * sunfan
     * 2018.5.7
     * user_id 访问者
     * owner_id 被访问者
     * type 1.老师的访客 2.托管/教育机构的访客
     * @return 无
     */
    public function addVisitor($user_id, $owner_id, $type)
    {
        $v = M('Visitor')->where([
            'user_id'   =>  $user_id,
            'owner_id'  =>  $owner_id,
            'type'      =>  $type
        ])->find();
        if ($v){
            M('Visitor')->where([
                'user_id'   =>  $user_id,
                'owner_id'  =>  $owner_id,
                'type'      =>  $type
            ])->save(['visit_time'=>date('Y-m-d H:i:s')]);
        }else{
            M('Visitor')->add([
                'user_id'   =>  $user_id,
                'owner_id'  =>  $owner_id,
                'visit_time'=>  date('Y-m-d H:i:s'),
                'type'      =>  $type
            ]);
        }
    }

}