<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/23
 * Time: 17:38
 */

namespace Admin\Controller;

use Common\Controller\AdminbaseController;

require('./application/Common/common/JPushSF.php');

class SmalltableController extends AdminbaseController
{

    public function _initialize()
    {
        parent::_initialize();
        $this->AppKey = C('NetEaseConf.APPID');
        $this->AppSecret = C('NetEaseConf.APPSECRET');
    }

    public function index()
    {
        if ($this->role == 'agent') {
            $where['st.agent_id'] = $this->uid;
        }
        if (I('keyword')) {
            $where['st.address|st.s_name|st.s_phone|st.c_name'] = ['like', '%' . I('keyword') . '%'];
        }
        if (I('s_type')) {
            $where['st.s_type'] = I('s_type');
        }
        if (I('s_status')) {
            $where['st.s_status'] = I('s_status');
        }
        if (I('is_home')) {
            $where['st.is_home'] = I('is_home');
        }
        $count = M('small_table')->alias('st')->where($where)->count();
        $page = $this->page($count, 10);
        $list = M('small_table')
            ->alias('st')
            ->join('LEFT JOIN fzm_level l ON l.id=st.level_id')
            ->join('LEFT JOIN fzm_small_table st2 ON st.agent_parent_id=st2.id')
            ->where($where)
            ->order(['st.sort' => 'asc', 'st.s_time' => 'desc'])
            ->limit($page->firstRow, $page->listRows)
            ->field('st.*, st2.id as parent_id, st2.s_name as parent_name, l.name as level_name')
            ->select();
        foreach ($list as $key => $value) {
            $list[$key]['parent_name'] = $value['parent_name'] == null ? '平台' : $value['parent_name'];
            switch ($value['s_type']) {
                case 1:
                    $list[$key]['s_type'] = "托管机构";
                    break;
                case 2:
                    $list[$key]['s_type'] = "教育机构";
                    break;
                case 3:
                    $list[$key]['s_type'] = "合作代理商";
                    break;
                case 4:
                    $list[$key]['s_type'] = "直属代理商";
                    break;
            }
        }
        $this->assign("list", $list);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }

    public function add()
    {
        if (IS_POST) {
            if (empty($_POST['smeta']['thumb'])) {
                $this->error("请上传图片！");
            }
            if ($_POST['s_type'] == 0) {
                $this->error("请选择代理商类型！");
            }
            if (M('small_table')->where(['s_phone' => $_POST['s_phone']])->find()) {
                $this->error("手机号已存在！");
            }
            if ($_POST['s_type'] == 4) {
                if (M('parents')->where(['phone'=>$_POST['s_phone']])->find()) {
                    $this->error("手机号不可用，该手机号已注册成为家长！");
                }
            }
            if ($_POST['cate_id']){
                $_POST['cate_id'] = ','.implode(',',$_POST['cate_id']).',';
            }
            $_POST['s_img'] = strpos($_POST['smeta']['thumb'], '/') == 0 ? $_POST['smeta']['thumb'] : '/' . $_POST['smeta']['thumb'];
            $_POST['s_password'] = md5(substr($_POST['s_phone'], -6));
            $_POST['s_status'] = 2;//后台添加的都审核通过
            $_POST['s_time'] = date('Y-m-d H:i:s');
            if ($_POST['agent_parent_id'] > 0) {
                $parent_ids = self::getParentIs($_POST['agent_parent_id']);
                $_POST['agent_path'] = '0_' . implode('_', $parent_ids) . "_";
            } else {//顶级代理商
                $_POST['agent_parent_id'] = 0;
                $_POST['agent_path'] = '0_';
            }
            if ($this->role = 'agent') {
                $_POST['agent_id'] = $this->uid;
            }
            M()->startTrans();
            $add = M('small_table')->add($_POST);
            if ($add) {
                if ($_POST['s_type'] == 4) {
                    $re = M('parents')->add([
                        'phone'        => $_POST["s_phone"],
                        'password'     => $_POST['s_password'],
                        'p_time'       => date('Y-m-d H:i:s'),
                        'p_img'        => '/public/images/headicon.png',
                        'child_name'   => "学生".substr($_POST["s_phone"], -4),
                        'parent_name'  => "家长".substr($_POST["s_phone"], -4),
                        'child_school' => "暂无",
                        'child_grade'  => "暂无",
                        'relationship' => "父亲",
                        'st_id'        => $add, //关联id
                    ]);
                    if (!$re) {
                        M()->rollback();
                        $this->error("保存数据出现错误！1");
                    }
                }
                M()->commit();
                $this->success("添加成功！", U("Smalltable/index"));
            }
            M()->rollback();
            $this->error("保存数据出现错误！");
        } else {

            //代理商树型结构
            $tree = new \Tree();
            $result = M('small_table')->field('id, s_name, IFNULL(agent_parent_id, 0) as parentid')
                ->where(['s_status' => 2, 'status' => 1])->order(array("id" => "DESC"))->select();
            foreach ($result as $r) {
                $r['selected'] = '';
                $array[] = $r;
            }
            $str = "<option value='\$id' \$selected>\$spacer \$s_name</option>";
            $tree->init($array);
            $tree_agent = $tree->get_tree(0, $str);
            $cate = M('smalltable_cate')->select();
            $this->assign("cate", $cate);
            $this->assign("tree_agent", $tree_agent);

            $this->display();
        }
    }

    public function edit()
    {
        if (IS_POST) {
            if (empty($_POST['smeta']['thumb'])) {
                $this->error("请上传图片！");
            }
            if ($_POST['s_type'] == 0) {
                $this->error("请选择代理商类型！");
            }
            if (M('SmallTable')->where(['id' => ['neq', $_GET['id']], 's_phone' => $_POST['s_phone']])->find()) {
                $this->error("手机号已存在！");
            }
            if ($_POST['cate_id']){
                $_POST['cate_id'] = ','.implode(',',$_POST['cate_id']).',';
            }
            $_POST['s_img'] = strpos($_POST['smeta']['thumb'], '/') == 0 ? $_POST['smeta']['thumb'] : '/' . $_POST['smeta']['thumb'];
            $_POST['s_time'] = date('Y-m-d H:i:s');
            if (!empty($_POST['pwd'])) {
                $_POST['s_password'] = md5($_POST['pwd']);
            }
            if ($_POST['agent_parent_id'] > 0) {
                $parent_ids = self::getParentIs($_POST['agent_parent_id']);
                $_POST['agent_path'] = '0_' . implode('_', $parent_ids) . "_";
            } else {//顶级代理商
                $_POST['agent_parent_id'] = 0;
                $_POST['agent_path'] = '0_';
            }
            $result = M('small_table')->where(array('id' => $_GET['id']))->save($_POST);
            if ($result) {
                $this->success("保存成功！", U("Smalltable/index"));
            }
            $this->error("保存数据出现错误！");
        } else {
            $info = M('small_table')->where(array('id' => $_GET['id']))->find();
            if ($info['cate_id']){
                $info['cate_id'] = explode(',',trim($info['cate_id'],','));
            }
            $this->assign("info", $info);
            $cate = M('smalltable_cate')->select();
            $this->assign("cate", $cate);
            //代理商树型结构
            $tree = new \Tree();
            $result = M('small_table')->field('id, s_name, IFNULL(agent_parent_id, 0) as parentid')
                ->where(['s_status' => 2, 'status' => 1])->order(array("id" => "DESC"))->select();
            foreach ($result as $r) {
                if ($r['id'] == $info['id']) {
                    continue;
                }
                $r['selected'] = $r['id'] == $info['agent_parent_id'] ? 'selected' : '';
                $array[] = $r;
            }
            $str = "<option value='\$id' \$selected>\$spacer \$s_name</option>";
            $tree->init($array);
            $tree_agent = $tree->get_tree(0, $str);
            $this->assign("tree_agent", $tree_agent);

            $this->display();
        }
    }

    public function delete()
    {
        $del = M('small_table')->where(array('id' => $_GET['id']))->delete();
        $this->success("成功");
    }

    public function img_add()
    {
        $id = I('get.id', 0, 'intval');
        $list = M('small_images')->where("st_id='{$id}'")->select();
        if ($list[0]['st_id']) {
            $image = $list;
        } else {
            $image = '';
        }
        $this->assign('image', $image);
        $this->display();
    }

    public function sort()
    {
        if ($_POST) {
            foreach ($_POST['imgid'] as $k => $item) {
                M('small_images')->where(['id' => $item])->save(['sort' => $_POST['sort'][$k]]);
            }
//            $this->success('保存成功！', U('Smalltable/sort', array('id'=>I('id',0,'intval'))));
            $this->success('保存成功！');
            exit();
        }
        $id = I('id', 0, 'intval');
        $list = M('small_images')->where("st_id='{$id}'")->order('sort asc')->select();
        if ($list[0]['st_id']) {
            $image = $list;
        } else {
            $image = '';
        }
        $this->assign('image', $image);
        $this->display();
    }

    public function insert()
    {
        $id = $_POST['id'];
        if (!$_FILES['url']['error'][0]) {
            //实例化上传文件的类
            $upload = new \Think\Upload();
            //选择上传文件的大小
            $upload->maxSize = 3145728;
            //选择上传文件的类型
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            //将上传的图片存放在指定文件夹下
            $upload->rootPath = "./data/upload/admin/";
            $fileName = $_FILES['url']['name'];//这样就能够取得上传的文件名
            $fileExtensions = strrchr($fileName, '.');//通过对$fileName的处理，就能够取得上传的文件的后缀名
            $serverFileName = basename($fileName, $fileExtensions) . "_" . uniqid() . $fileExtensions;
            $upload->saveRule = $serverFileName;
            //调用上传文件类中的upload方法
            $info = $upload->upload();
            //判断调用是否成功
            $coun = count($_FILES['url']['name']);
            for ($i = 0; $i < $coun; $i++) {
                //得到要存入数据库中的地址
                $pic = $info[$i]["savepath"] . $info[$i]['savename'];
                $data['url'] = "/data/upload/admin/" . $pic;
                $data['st_id'] = $id;
                $data['url'] = image($data['url']);
                $rees = M('small_images')->add($data);
            }
            if ($rees !== false) {
                $this->success("添加成功", U('smalltable/index'));
            } else {
                $this->error("添加失败");
            }
        } else {
            $this->error('没有图片被上传！');
        }
    }

    public function modlunbo()
    {
        $id = $_POST['u_id'];
        $st_id = $_POST['id'];
        if (!$_FILES['url']['error']) {
            //实例化上传文件的类
            $upload = new \Think\Upload();
            //选择上传文件的大小
            $upload->maxSize = 3145728;
            //选择上传文件的类型
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            //将上传的图片存放在指定文件夹下
            $upload->rootPath = "./data/upload/admin/";
            $info = $upload->upload();
            //得到要存入数据库中的地址
            if (!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            } else {// 上传成功 获取上传文件信息
                $data['url'] = "/data/upload/admin/" . $info['url']['savepath'] . $info['url']['savename'];
            }
        }
        $rees = M('small_images')->where(array('id' => $id))->save($data);
//        echo M('small_images')->getLastSql();die;
        if ($rees !== false) {
            $this->success("保存成功", U('smalltable/img_add', array('id' => $st_id)));
        } else {
            $this->success("保存成功");
        }
    }

    public function delurl()
    {
        $pro = M('small_images');
        $id = $_GET['id'];
        $tu1 = $pro->field('url')->find($id);
        $rootpath1 = './Date/Upload/admin/';
        //删除文件夹中的图片
        unlink('.' . $tu1['url']);
        $del = $pro->delete($id);
        if ($del !== false) {
            $this->success('删除成功');
        } else {
            $this->error("删除失败");
        }
    }


    public function map()
    {
        $this->display();
    }

    /**
     * 图集管理
     */
    public function imglist()
    {
        $id = I('get.id', 0, 'intval');
        $list = M('small_images')->where("st_id='{$id}'")->order('sort')->select();
        if ($list[0]['st_id']) {
            $image = $list;
        } else {
            $image = '';
        }
        $this->assign('image', $image);
        $this->display();
    }

    /**
     * 批量删除图集
     */
    public function del_batch_url()
    {
        $ids = I('ids');
        $pro = M('small_images');
        foreach ($ids as $id) {
            $tu1 = $pro->field('url')->find($id);
            $rootpath1 = './Date/Upload/admin/';
            //删除文件夹中的图片
            unlink('.' . $tu1['url']);
            $del = $pro->delete($id);
        }
        $this->success('删除成功');
    }

    /**
     * 批量添加
     */
    public function imgadd()
    {
        if (IS_POST) {
            $id = $_POST['id'];
            $data['st_id'] = $id;
            foreach ($_POST['photos_url'] as $img) {
                $data['url'] = image('/' . $img);
                $rees = M('small_images')->add($data);
            }
            $this->success('添加成功', U("Smalltable/index", ['id' => $id]));
        }
        $rs = M('SmallTable')->where(['id' => I('id')])->find();
        $this->assign('rs', $rs);
        $this->display();
    }

    /**
     * 审核
     * 二期
     * 2018.3.21
     */
    public function review()
    {

        $info = M('small_table')->where(array('id' => $_GET['id']))->find();

        $examine = D("examine_table");
        $examine_list = $examine->where(['uid' => $info['id']])->select();
        $this->assign('examine', $examine_list);
        $this->assign("info", $info);
        $this->display();
    }

    /**
     * 审核通过、不通过
     * 二期
     * 2018.0.21
     */
    public function review_status()
    {
//        if(I('status') == 2){
//            $count = D("examine_table")->where(['uid'=>I("id"),'status'=>['in',[0,2]]])->count();
//            if($count > 0 ){
//                $this->error('当前有资料未审核通过，不可进行审核通过操作！');
//            }
//        }

        M('small_table')->where(array('id' => I('id')))->save(['s_status' => I('status'), 'fail_reason' => I('reason')]);
        $alias = 'smalltable' . I('id');
        //给商户推送
        $push = new \JPushSF(C('JPush.SAPPID'), C('JPush.SAPPSECRET'));
        $receive = array('alias' => [$alias]);//别名
        $phone = M('small_table')->where(['id' => I('id')])->getField('s_phone');

        if (I('status') == 3) {
            $push->push($receive, 10);
            //给商户发短信
            send_message($phone, 'SMS_126356286');
            echo json_encode(['code' => 1, 'msg' => "操作成功"]);
            exit();
        } else {
            $push->push($receive, 11);
            //给商户发短信
            send_message($phone, 'SMS_139237300');
            $this->success('审核成功！', U('Educational/index'));
            exit();
        }
        $this->error('操作失败');
    }

    /**
     * 评价
     * 二期
     * 2018.3.23
     */
    public function star()
    {
        if (IS_POST) {
            $_POST['star'] = round(($_POST['star1'] + $_POST['star2'] + $_POST['star3'] + $_POST['star4'] + $_POST['star5']) / 5);
            M('small_table')->where(array('id' => $_POST['id']))->save($_POST);
            M('small_table_sort')->where(array('sid' => $_POST['id']))->save(['star' => $_POST['star']]);
            $this->success("评价成功！", U("Smalltable/index"));
            exit();
        }
        $info = M('small_table')->where(array('id' => $_GET['id']))->find();
        $this->assign("info", $info);
        $this->display();
    }

    /**
     * 推荐
     * sunfan
     * 2018.7.11
     */
    public function recommend()
    {
        M('small_table')->where(array('id' => I('id')))->save(['is_home' => I('is_home')]);
        M('small_table_sort')->where(array('sid' => I('id')))->save(['is_home' => I('is_home')]);
        $this->success("操作成功！", U("Smalltable/index"));
    }

    /*
 *  排序
 */

    public function sortSt()
    {
        $ids = I('post.id', array());
        $sort = I('post.sort', array());
        try {
            foreach ($ids as $k => $id) {
                D('SmallTable')->where(array('id' => $id))->save(array('sort' => $sort[$k]));
                D('SmallTableSort')->where(array('sid' => $id))->save(array('sort' => $sort[$k]));
            }
            $this->success('排序成功', U('Smalltable/index'));
        } catch (\Exception $e) {
            $this->error('排序失败');
        }
    }

    //冻结  解冻
    public function ban()
    {
        $id = I("get.id", 0, 'intval');
        if ($id) {
            $status = I("get.status", 0, 'intval');
            if ($status) {
                $info = M("small_table")->where(['id' => $id])->save(['status' => $status]);
                if ($info) {
                    if (I("type")) {
                        $this->success('操作成功！', U('Educational/index'));
                    }
                    $this->success('操作成功', U('Smalltable/index'));
                } else {
                    $this->error('操作失败');
                }
            } else {
                $this->error('网络错误');
            }
        } else {
            $this->error('传输信息有误');
        }
    }


    //审核各项信息
    public function examine()
    {
        $examine = D("examine_table");
        $where['uid'] = I("post.id", 0);
        $where['option_key'] = I("post.str");
        $save['status'] = I("post.status", 1);
        $info = $examine->where($where)->save($save);
        if ($info) {
            echo 1;
        } else {
            echo -1;
        }
        exit();
    }


    /**
     * 根据子代理商获取其父级代理商
     */
    public static function getParentIs($id, $data = array())
    {
        $data[] = $id;
        $parentId = M('small_table')->where(['id' => $id])->getField('agent_parent_id');
        if ($parentId == 0) {
            krsort($data);
            return $data;
        } else {
            return self::getParentIs($parentId, $data);
        }
    }


    public function ratiolist()
    {
        $where['st.s_status'] = 2;
        $where['st.status'] = 1;
        if (I('agent_id')) {
            $where['st.id'] = I('agent_id');
        }
        if (I('s_type')) {
            $where['st.s_type'] = I('s_type');
        }
        if (I('keyword')) {
            $where['st.address|st.s_name|st.s_phone|st.c_name'] = ['like', '%' . I('keyword') . '%'];
        }
        $count = M('smalltable_ratio')->count();
        $page = $this->page($count, 10);
        $data_list = M('small_table')
            ->alias('st')
            ->join('INNER JOIN fzm_smalltable_ratio r ON r.agent_id=st.id')
            ->join('LEFT JOIN fzm_small_table st2 ON st.agent_parent_id=st2.id')
            ->where($where)
            ->order(['st.sort' => 'asc', 'st.s_time' => 'desc'])
            ->limit($page->firstRow, $page->listRows)
            ->field('r.*, st.s_name as agent_name, st.s_type, st2.id as parent_id, st2.s_name as parent_name')
            ->select();
        foreach ($data_list as $key => $value) {
            $data_list[$key]['parent_name'] = $value['parent_name'] == null ? '平台' : $value['parent_name'];
            switch ($value['s_type']) {
                case 1:
                    $data_list[$key]['s_type'] = "托管机构";
                    break;
                case 2:
                    $data_list[$key]['s_type'] = "教育机构";
                    break;
                case 3:
                    $data_list[$key]['s_type'] = "合作代理商";
                    break;
                case 4:
                    $data_list[$key]['s_type'] = "直属代理商";
                    break;
            }
        }

        $agent_list = M('small_table')->alias('st')
            ->field('id, s_name')->where(['s_status' => 2, 'status' => 1])->order(['st.sort' => 'asc', 'st.s_time' => 'desc'])->select();

        $this->assign("list", $data_list);
        $this->assign("agent_list", $agent_list);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }


    public function ratioedit()
    {
        if (IS_POST) {
            if ($_POST['agent_id'] == 0) {
                $this->error("请选择代理商！");
            }
            if (empty($_POST['kecheng_ratio'])) {
                $this->error("请填写课程分佣比例！");
            }
            if (empty($_POST['tuoguan_ratio'])) {
                $this->error("请填写托管分佣比例！");
            }
            if (empty($_POST['huodong_ratio'])) {
                $this->error("请填写活动分佣比例！");
            }
            $agent_ratio = M('smalltable_ratio')->where(['agent_id'=>$_POST['agent_id']])->find();
            $id = $agent_ratio != null ? $agent_ratio['id'] : I('post.id',0,'intval');
            $_POST['r_type'] = 1;
            $_POST['r_time'] = date('Y-m-d H:i:s');
            if($id == 0){
                unset($_POST['id']);
                $result = M('smalltable_ratio')->add($_POST);
            }
            else{
                $result = M('smalltable_ratio')->where(array('id' => $id))->save($_POST);
            }
            if ($result) {
                $this->success("保存成功！", U("Smalltable/ratiolist"));
            }
            $this->error("保存数据出现错误！");
        } else {
            $id = I('get.id',0,'intval');
            $info = M('smalltable_ratio')->where(['id'=>$id])->find();
            $this->assign("info", $info);

            //代理商列表
            $agent_list = M('small_table')->alias('st')->field('st.id, st.s_name')->where(['st.s_status' => 2, 'st.status' => 1])
                ->order(['st.sort' => 'asc', 'st.s_time' => 'desc'])->select();
            $this->assign("agent_list", $agent_list);

            $this->display();
        }
    }

    public function ratiodelete()
    {
        $id = I('get.id',0,'intval');
        $del_result = M('smalltable_ratio')->where(array('id' => $id))->delete();
        if($del_result){
            $this->success("操作成功！");
        }
        $this->error("删除数据出现错误！");
    }

    public function direct_agent_ratio()
    {
        $where = [];
        if (I('agent_id')) {
            $where['dar.agent_id'] = I('agent_id');
        }
        if (I('keyword')) {
            $where['st.address|st.s_name|st.s_phone|st.c_name'] = ['like', '%' . I('keyword') . '%'];
        }
        $direct_agent_ratio = M('direct_agent_ratio');
        $count = $direct_agent_ratio->count();
        $page = $this->page($count, 10);
        $data_list = $direct_agent_ratio
            ->alias('dar')
            ->join('LEFT JOIN fzm_small_table st ON st.id=dar.agent_id')
            ->where($where)
            ->field('dar.*, st.s_name as agent_name, st.s_type')
            ->order(['st.sort' => 'asc', 'st.s_time' => 'desc'])
            ->limit($page->firstRow, $page->listRows)
            ->select();

        $agent_list = M('small_table')
            ->field('id, s_name')
            ->where(['s_status' => 2,'s_type' => 4, 'status' => 1])
            ->order(['sort' => 'asc', 's_time' => 'desc'])
            ->select();

        $this->assign("list", $data_list);
        $this->assign("agent_list", $agent_list);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }
    public function direct_agent_ratio_edit()
    {
        if (IS_POST) {
            $direct_agent_ratio = M('direct_agent_ratio');
            if ($_POST['agent_id'] == 0) {
                $this->error("请选择代理商！");
            }
            if (empty($_POST['kecheng_ratio'])) {
                $this->error("请填写课程分佣比例！");
            }
            if (empty($_POST['huodong_ratio'])) {
                $this->error("请填写活动分佣比例！");
            }
            $agent_ratio = $direct_agent_ratio->where(['agent_id'=>$_POST['agent_id']])->find();
            $id = $agent_ratio != null ? $agent_ratio['id'] : I('post.id',0,'intval');
            $_POST['r_type'] = 1;
            $_POST['r_time'] = date('Y-m-d H:i:s');
            if ($id == 0) {
                unset($_POST['id']);
                $result = $direct_agent_ratio->add($_POST);
            } else {
                $result = $direct_agent_ratio->where(array('id' => $id))->save($_POST);
            }
            if ($result) {
                $this->success("保存成功！", U("Smalltable/direct_agent_ratio"));
            }
            $this->error("保存数据出现错误！");
        } else {
            $direct_agent_ratio = M('direct_agent_ratio');
            $id = I('get.id',0,'intval');
            if ($id) {
                $info = $direct_agent_ratio->where(['id'=>$id])->find();
                $this->assign("info", $info);
            } else {
                $where = ['s_status' => 2,'s_type' => 4, 'status' => 1];
                $agent_ids = $direct_agent_ratio->getField('agent_id',true);
                if (!empty($agent_ids)) {
                    $where['id'] = ['not in',$agent_ids];
                }
                //代理商列表
                $agent_list = M('small_table')
                    ->field('id, s_name')
                    ->where($where)
                    ->order(['sort' => 'asc', 's_time' => 'desc'])
                    ->select();
                if (empty($agent_list)) {
                    $this->error('没有直属代理商可设置了');
                }
                $this->assign("agent_list", $agent_list);
            }
            $this->display();
        }
    }

    public function  direct_agent_ratio_delete()
    {
        $id = I('get.id',0,'intval');
        $del_result = M('direct_agent_ratio')->where(array('id' => $id))->delete();
        if($del_result){
            $this->success("操作成功！");
        }
        $this->error("删除数据出现错误！");
    }
}