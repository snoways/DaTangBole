<?php
/**
 * Created by PhpStorm.
 * User: sunfan
 * Date: 2017/11/1
 * Time: 14:59
 */

namespace Parents\Controller;


use Client\Controller\MapiBaseController;
use Think\Exception;

class HomeController extends MapiBaseController
{

    public function home()
    {
        $data = $this->data;
//        $id = S($data['token']);
        //banner
        $rs['banner'] = M('Banner')->where(['position'=>1])->field('url, type, item_id')->select();

        //线下活动
        $Activity = D('Activity');
        $where = ['status'=>1,'state'=>1,'is_hot'=>1,'end'=>['egt',date('Y-m-d H:i:s')]];
        $rs['activity_hot_list'] = $Activity
            ->where($where)
            ->field('id,title,img,age_max,age_min ,target,money,now_num')
            ->limit(4)
            ->select();
        unset($where['is_hot']);
        $where['recommended'] = 1;
        $rs['activity_recommended_list'] = $Activity
            ->alias('a')
            ->join('LEFT JOIN __ACTIVITY_CATE__ ac ON a.cate_id = ac.id')
            ->where($where)
            ->field('a.id,a.title,a.extra_title,a.img,a.age_max,a.age_min ,a.target,a.money,a.spell_refund,a.spell,a.now_num,ac.name cate_name')
            ->limit(4)
            ->select();
        $activity_order = M('activity_order');
        foreach ($rs as $key => &$activity) {
            if (in_array($key,['activity_recommended_list','activity_hot_list'])) {
                if (!empty($activity)) {
                    foreach ($activity as &$value) {
                        if ($value['max'] == 0) {
                            $value['age_title'] = $value['age_min'].'岁以上';
                        } else {
                            $value['age_title'] = $value['age_min'].'-'.$value['age_max'].'岁';
                        }
                        $aod_count = $activity_order
                            ->alias('ao')
                            ->join('LEFT JOIN __ACT_ORDER_DETAIL__ aod ON ao.id = aod.order_id')
                            ->where(['ao.status'=>['in',[2,3]],'ao.activity_id'=>$value['id']])
                            ->field("count('aod.*') aod_count")
                            ->find();
                        $value['sign_up_num'] = $aod_count['aod_count'] + $value['now_num'];
                        unset($value['now_num']);
                        unset($value['age_min']);
                        unset($value['age_max']);
                        unset($aod_count);
                    }
                }
            }
        }
        //线上课堂
        $rs['online_class'] = M('OnlineClass')
            ->where(['status'=>1,'is_popular'=>1,'hot'=>1])
            ->field('id as oc_id, title,u_type,tid, img_url,price_type')
            ->order(['sort'=>'asc', 'oc_time'=>'desc'])
            ->limit(4)
            ->select();
        foreach ($rs['online_class'] as &$item){

            if ($item['u_type']==2){    //用户类型  2.商户 3.后台
                //商户
                $item['t_name'] = M('small_table')->where(['id'=>$item['tid']])->getField('s_name');
            }else{
                //平台
                $item['t_name'] = M('Config')->getField('class_name');
            }
            unset($item['u_type']);
            unset($item['tid']);
        }
        $rs['travels_list'] = M('travels')
            ->where(['status'=>1,'is_home'=>1])
            ->field('id,img_url,title,author,viewnum,add_date')
            ->limit(2)
            ->order("is_home desc, sort asc, add_time DESC")
            ->select();

        $this->ApiReturn(1, 'success', $rs);
    }

    /**
     * 获取课堂分类
     * 纪忠宝
     * 2018.11.27
     */
    public function subjects()
    {
        try{
            $rs = M('Subject')
                ->where(['parentid'=>0])
                ->order('sort asc')
                ->field('id, s_name')
                ->select();
            foreach ($rs as &$v){
                $v['son'] = M('Subject')
                    ->where(['parentid'=>$v['id']])
                    ->field('id, s_name')
                    ->order('sort asc')
                    ->select();
                foreach ($v['son'] as &$value){
                    $value['son'] = M('Subject')
                        ->where(['parentid'=>$value['id']])
                        ->field('id, s_name')
                        ->order('sort asc')
                        ->select();
                }
            }
            $code = 1;
            $msg = 'success';
        } catch (Exception $e) {
            $code = -1;
            $msg = $e->getMessage();
        }

        $this->ApiReturn($code, $msg, $rs);
    }

    /**
     * 新闻资讯
     * sunfan
     * 2017.11.10
     */
    public function newsList()
    {
        $data = $this->data;
        $page = $data['page']??1;
        $where = ['cate_id'=>1];
        if (!empty($data['keyword'])){
            $where['news_title'] = ['like', "%".$data['keyword']."%"];
        }
        $list = M('News')->where($where)
            ->page($page)->order('news_time desc')
            ->field('id as news_id, news_title, news_time')->select();
        if (!$list)
        {
            $this->ApiReturn(0, '暂无数据');
        }
        $this->ApiReturn(1, 'success', $list);
    }

    /**
     * 活动公告
     * sunfan
     * 2017.11.10
     */
    public function activityList()
    {
        $data = $this->data;
        $page = $data['page']??1;
        $where = ['cate_id'=>2];
        if (!empty($data['keyword'])){
            $where['news_title'] = ['like', "%".$data['keyword']."%"];
        }
        $list = M('News')->where($where)
            ->page($page)->order('news_time desc')
            ->field('id as news_id, news_title, news_time')->select();
        if (!$list)
        {
            $this->ApiReturn(0, '暂无数据');
        }
        $this->ApiReturn(1, 'success', $list);
    }

    /**
     * 新闻/活动 详情
     * sunfan
     * 2017.11.10
     */
    public function newsDetail()
    {
        $data = $this->data;
        $id = $data['news_id']??$this->ApiReturn(-1, '文章id不能为空');
        $rs = M('News')->where(['id'=>$id])->field('id as news_id, news_title, news_detail, news_time')->find();
        if (!$rs)
        {
            $this->ApiReturn(-1, '文章不存在');
        }

        $last = M('News')->where(['id'=>['lt', $id]])->field('id as news_id, news_title')->order('id desc')->find();
        $next = M('News')->where(['id'=>['gt', $id]])->field('id as news_id, news_title')->order('id asc')->find();

        $rs['last'] = $last;
        $rs['next'] = $next;
        $this->ApiReturn(1, 'success', $rs);
    }

    /**
     * 二期修改--增加返回banner
     * 视频管理
     * sunfan
     * 2017.11.10
     */
    public function videoList()
    {
        $data = $this->data;
        $page = $data['page']??1;

        $where['status'] = 1;
        //二期增加关键字搜索
        if (!empty($data['keyword']))
        {
            $where['title'] = ['like', "%".$data['keyword']."%"];
        }

        if (!empty($data['cate_id'])){
            $where['category_id'] = $data['cate_id'];
        }

        $list = M('Video')
            ->where($where)
            ->page($page)
            ->order('add_time desc')
            ->field('id as video_id, title, subtitle as `desc`, img_url, view_num')
            ->select();

        foreach ($list as &$item){
            $item['desc'] = utf8Substr($item['desc'], 0, 20);
        }
        //banner--二期增加
        $banner = M('Banner')->field('url, type, item_id')->select();

        $re['banner'] = $banner;
        $re['list'] = $list;
        $this->ApiReturn(1, 'success', $re);
    }


    /**
     * 二期增加--分类
     * 视频分类管理
     * sunfan
     * 2018.7.12
     */
    public function categoryList()
    {
        $db = M('VideoCate');
        $list = $db->field('id as cate_id, name')->select();
        $this->ApiReturn(1, 'success', $list);
    }

    /**
     * 二期修改--增加返回 热门视频、浏览量
     * 视频详情
     * sunfan
     * 2017.11.10
     */
    public function videoDetail()
    {
        $data = $this->data;
        $video_id = $data['video_id'];
        $rs = M('Video')
            ->where(['id'=>$video_id])
            ->field('id as video_id, title,subtitle, desc, img_url, video_url, add_time, view_num')
            ->find();

        $rs['list'] = M('Video')
            ->limit(4)
            ->order('add_time desc')
            ->field('id as video_id, title, subtitle as `desc`, img_url, view_num')
            ->order('view_num desc')
            ->select();

        M('Video')->where(['id'=>$video_id])->setInc('view_num');

        $this->ApiReturn(1, 'success', $rs);
    }

    /**
     * 举报视频
     * 二期
     * sunfan
     * 2018.6.13
     */
    public function reportClass()
    {
        $data = $this->data;
        $where['id'] = $data['video_id']??$this->ApiReturn(-1, 'id不能为空');
        $model = M("Video");
        $info = $model->where($where)->setInc('report_num');
        $this->ApiReturn(1, '举报成功');
    }


    /**
     * 发坐标
     * sunfan
     * 2017.12.5
     */
//    public function sendxy()
//    {
//        set_time_limit(3000);
//        $data = $this->data;
//        if (!S($data['token'])){
//            $this->ApiReturn(20003,'token无效或已过期','');
//        }
//        $id = S($data['token']);
//        $xy = $data['xy']??$this->ApiReturn(-1, '坐标不能为空');
//        M('Parents')->where(['id'=>$id])->save(['p_xy'=>$xy]);
//
//        //把老师按距离排序，排好放数据库，等待调用
//
//        /*********老师数据*****start***************/
//        M('TeacherSort')->where(['pid'=>$id])->delete();
//
//        $where['status'] = 1;       //1正常  2冻结
//        $where['state'] = 2;        //0 注册成功1.审核中 2.审核通过 3.驳回
//        $list = M('Teacher')->where($where)->select();
//        $start = $xy;
//        if($list)foreach ($list as $k=>$item)
//        {
//            if (!$item['position']){
//                $list[$k]['distance']="未知";
//                $list[$k]['distance1']=99999999999999;
//            }else{
//                $dis = getDistance($start, $item['position']);
//                $list[$k]['distance']=$dis['length'];
//                $list[$k]['distance1']=$dis['length1'];
//            }
//        }
//
//        if ($list){
//            $list=array_values($list);
//            $sort = [
//                'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
//                'field'     => 'distance1',       //排序字段
//            ];
//
//            $arrSort = [];
//            foreach($list AS $uniqid => $row){
//                foreach($row AS $key=>$value){
//                    $arrSort[$key][$uniqid] = $value;
//                }
//            }
//            if($sort['direction']){
//                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $list);
//            }
//
//            //把排好序的放数据库里
//            foreach ($list as $item){
//                M('TeacherSort')->add([
//                   'pid'  => $id,
//                    'tid' => $item['id'],
//                    'name'=> $item['name'],
//                    'sex' => $item['sex'],
//                    'province'=>$item['province'],
//                    'city'=> $item['city'],
//                    'area'=>$item['area'],
//                    'areaId'=>$item['areaId'],
//                    't_img' =>  $item['t_img'],
//                    't_type'=>$item['t_type'],
//                    't_grade'=>$item['t_grade'],
//                    't_sub1'=>$item['t_sub1'],
//                    't_sub2'=>$item['t_sub2'],
//                    'price'=>$item['price'],
//                    'status'=>$item['status'],
//                    'state'=>$item['state'],
//                    'position'=>$item['position'],
//                    'view_num'=>$item['view_num'],
//                    'level_id'=>$item['level_id'],
//                    'distance'=>$item['distance'],
//                    'distance1'=>$item['distance1'],
//                    'score'=>$item['score'],
//                    'age'=>$item['age'],
//                    'sort'=>$item['sort'],
//                ]);
//            }
//        }
//        /*********老师数据*****end***************/
//
//        /*********托管数据*****start***************/
//        $where=[];$list=[];
//        M('SmallTableSort')->where(['pid'=>$id,'s_type'=>1])->delete();
//
//        $where['s_type'] = 1;
//        $where['s_status'] = 2;
//        $where['status'] = 1;
//        $list = M('SmallTable')->where($where)->select();
//        if($list)foreach ($list as $k=>$item){
//            if (!$item['s_xy']){
//                $list[$k]['distance']="未知";
//                $list[$k]['distance1']=99999999999999;
//            }else{
//                $dis = getDistance($start, $item['s_xy']);
//                $list[$k]['distance']=$dis['length'];
//                $list[$k]['distance1']=$dis['length1'];
//            }
//        }
//        if ($list){
//            $list=array_values($list);
//            $sort = [
//                'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
//                'field'     => 'distance1',       //排序字段
//            ];
//
//            $arrSort = [];
//            foreach($list AS $uniqid => $row){
//                foreach($row AS $key=>$value){
//                    $arrSort[$key][$uniqid] = $value;
//                }
//            }
//            if($sort['direction']){
//                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $list);
//            }
//
//            //把排好序的放数据库里
//            foreach ($list as $item){
//                M('SmallTableSort')->add([
//                    'pid'  => $id,
//                    'sid' => $item['id'],
//                    's_img'=> $item['s_img'],
//                    'address' => $item['address'],
//                    's_name'=>$item['s_name'],
//                    's_phone'=> $item['s_phone'],
//                    's_content'=>$item['s_content'],
//                    'c_name'=>$item['c_name'],
//                    's_type' =>  $item['s_type'],
//                    'level_id'=>$item['level_id'],
//                    'agent_id'=>$item['agent_id'],
//                    'view_num'=>$item['view_num'],
//                    'distance'=>$item['distance'],
//                    'distance1'=>$item['distance1'],
//                    'star'=>$item['star'],
//                ]);
//            }
//        }
//        /*********托管数据*****end***************/
//
//        /*********教育机构数据*****start***************/
//        $where1=[];$list=[];
//        M('SmallTableSort')->where(['pid'=>$id,'s_type'=>2])->delete();
//
//        $where1['s_type'] = 2;
//        $where1['s_status'] = 2;
//        $where1['status'] = 1;
//        $list = M('SmallTable')->where($where1)->select();
//        if($list)foreach ($list as $k=>$item){
//            if (!$item['s_xy']){
//                $list[$k]['distance']="未知";
//                $list[$k]['distance1']=99999999999999;
//            }else{
//                $dis = getDistance($start, $item['s_xy']);
//                $list[$k]['distance']=$dis['length'];
//                $list[$k]['distance1']=$dis['length1'];
//            }
//        }
//        if ($list){
//            $list=array_values($list);
//            $sort = [
//                'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
//                'field'     => 'distance1',       //排序字段
//            ];
//
//            $arrSort = [];
//            foreach($list AS $uniqid => $row){
//                foreach($row AS $key=>$value){
//                    $arrSort[$key][$uniqid] = $value;
//                }
//            }
//            if($sort['direction']){
//                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $list);
//            }
//
//            //把排好序的放数据库里
//            foreach ($list as $item){
//                M('SmallTableSort')->add([
//                    'pid'  => $id,
//                    'sid' => $item['id'],
//                    's_img'=> $item['s_img'],
//                    'address' => $item['address'],
//                    's_name'=>$item['s_name'],
//                    's_phone'=> $item['s_phone'],
//                    's_content'=>$item['s_content'],
//                    'c_name'=>$item['c_name'],
//                    's_type' =>  $item['s_type'],
//                    'level_id'=>$item['level_id'],
//                    'agent_id'=>$item['agent_id'],
//                    'view_num'=>$item['view_num'],
//                    'distance'=>$item['distance'],
//                    'distance1'=>$item['distance1'],
//                    'star'=>$item['star'],
//                ]);
//            }
//        }
//        /*********教育机构数据*****end***************/
//
//        $this->ApiReturn(1, 'success');
//    }
    /**
     * 发坐标
     * sunfan
     * 2017.12.5
     */
    public function sendxy()
    {
        set_time_limit(3000);
        $data = $this->data;
        if (!S($data['token'])){
            $this->ApiReturn(20003,'token无效或已过期','');
        }
        $id = S($data['token']);
        $xy = $data['xy']??$this->ApiReturn(-1, '坐标不能为空');
        M('Parents')->where(['id'=>$id])->save(['p_xy'=>$xy]);

        //把老师按距离排序，排好放数据库，等待调用
        /*********老师数据*****start***************/
        M('TeacherSort')->where(['pid'=>$id])->delete();

        $where['status'] = 1;       //1正常  2冻结
        $where['state'] = 2;        //0 注册成功1.审核中 2.审核通过 3.驳回
        $list = M('Teacher')->where($where)->select();
        $start = $xy;
        if($list)foreach ($list as $k=>$item)
        {
            if (!$item['position']){
                $list[$k]['distance']="未知";
                $list[$k]['distance1']=99999999999999;
            }else{
                $dis = getDistance($start, $item['position']);
                $list[$k]['distance']=$dis['length'];
                $list[$k]['distance1']=$dis['length1'];
            }
        }

        if ($list){
            $list=array_values($list);
            /*$sort = [
                'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field'     => 'distance1',       //排序字段
            ];

            $arrSort = [];
            foreach($list AS $uniqid => $row){
                foreach($row AS $key=>$value){
                    $arrSort[$key][$uniqid] = $value;
                }
            }
            if($sort['direction']){
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $list);
            }*/

            //把排好序的放数据库里
            foreach ($list as $item){
                M('TeacherSort')->add([
                    'pid'  => $id,
                    'tid' => $item['id'],
                    'name'=> $item['name'],
                    'sex' => $item['sex'],
                    'province'=>$item['province'],
                    'city'=> $item['city'],
                    'area'=>$item['area'],
                    'areaId'=>$item['areaId'],
                    't_img' =>  $item['t_img'],
                    't_type'=>$item['t_type'],
                    't_grade'=>$item['t_grade'],
                    't_sub1'=>$item['t_sub1'],
                    't_sub2'=>$item['t_sub2'],
                    'price'=>$item['price'],
                    'status'=>$item['status'],
                    'state'=>$item['state'],
                    'position'=>$item['position'],
                    'view_num'=>$item['view_num'],
                    'level_id'=>$item['level_id'],
                    'distance'=>$item['distance'],
                    'distance1'=>$item['distance1'],
                    'score'=>$item['score'],
                    'age'=>$item['age'],
                    'sort'=>$item['sort'],
                ]);
            }
        }
        /*********老师数据*****end***************/

        /*********托管数据*****start***************/
        $where=[];$list=[];
        M('SmallTableSort')->where(['pid'=>$id,'s_type'=>1])->delete();

        $where1['s_type'] = 1;
        $where1['s_status'] = 2;
        $where1['status'] = 1;
        $list = M('SmallTable')->where($where1)->select();
        if($list)foreach ($list as $k=>$item){
            if (!$item['s_xy']){
                $list[$k]['distance']="未知";
                $list[$k]['distance1']=99999999999999;
            }else{
                $dis = getDistance($start, $item['s_xy']);
                $list[$k]['distance']=$dis['length'];
                $list[$k]['distance1']=$dis['length1'];
            }
        }

        if ($list){
            $list=array_values($list);
            /*$sort = [
                'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field'     => 'distance1',       //排序字段
            ];

            $arrSort = [];
            foreach($list AS $uniqid => $row){
                foreach($row AS $key=>$value){
                    $arrSort[$key][$uniqid] = $value;
                }
            }
            if($sort['direction']){
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $list);
            }*/


            //把排好序的放数据库里
            foreach ($list as $item){
                M('SmallTableSort')->add([
                    'pid'  => $id,
                    'sid' => $item['id'],
                    's_img'=> $item['s_img'],
                    'address' => $item['address'],
                    's_name'=>$item['s_name'],
                    's_phone'=> $item['s_phone'],
                    's_content'=>$item['s_content'],
                    'c_name'=>$item['c_name'],
                    's_type' =>  $item['s_type'],
                    'level_id'=>$item['level_id'],
                    'agent_id'=>$item['agent_id'],
                    'view_num'=>$item['view_num'],
                    'distance'=>$item['distance'],
                    'distance1'=>$item['distance1'],
                    'star'=>$item['star'],
                    'is_home'=>$item['is_home'],
                    'sort'=>$item['sort'],
                ]);
                M('SmallTableSort')->getLastSql();

            }
        }
        /*********托管数据*****end***************/

        /*********教育机构数据*****start***************/
        $where=[];$list=[];
        M('SmallTableSort')->where(['pid'=>$id,'s_type'=>2])->delete();

        $where2['s_type'] = 2;
        $where2['s_status'] = 2;
        $where['status'] = 1;
        $list = M('SmallTable')->where($where2)->select();
        if($list)foreach ($list as $k=>$item){
            if (!$item['s_xy']){
                $list[$k]['distance']="未知";
                $list[$k]['distance1']=99999999999999;
            }else{
                $dis = getDistance($start, $item['s_xy']);
                $list[$k]['distance']=$dis['length'];
                $list[$k]['distance1']=$dis['length1'];
            }
        }
        if ($list){
            $list=array_values($list);
            /*$sort = [
                'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field'     => 'distance1',       //排序字段
            ];

            $arrSort = [];
            foreach($list AS $uniqid => $row){
                foreach($row AS $key=>$value){
                    $arrSort[$key][$uniqid] = $value;
                }
            }
            if($sort['direction']){
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $list);
            }*/

            //把排好序的放数据库里
            foreach ($list as $item){
                M('SmallTableSort')->add([
                    'pid'  => $id,
                    'sid' => $item['id'],
                    's_img'=> $item['s_img'],
                    'address' => $item['address'],
                    's_name'=>$item['s_name'],
                    's_phone'=> $item['s_phone'],
                    's_content'=>$item['s_content'],
                    'c_name'=>$item['c_name'],
                    's_type' =>  $item['s_type'],
                    'level_id'=>$item['level_id'],
                    'agent_id'=>$item['agent_id'],
                    'view_num'=>$item['view_num'],
                    'distance'=>$item['distance'],
                    'distance1'=>$item['distance1'],
                    'star'=>$item['star'],
                    'is_home'=>$item['is_home'],
                    'sort'=>$item['sort'],
                ]);
            }
        }
        /*********教育机构数据*****end***************/

        $this->ApiReturn(1, 'success');
    }

    /**
     * 名师推荐
     * sunfan
     * 2017.12.22
     */
    public function recTeacher()
    {
        //名师推荐
        $teacher = M('Teacher')
            ->where(['is_hot'=>1, 'state'=>2])
            ->field('id as teacher_id, t_img, name, sex, t_grade as grade, t_sub2 as subject, score,t_type')
            ->limit(8)
            ->order('rand()')
            ->select();
        foreach ($teacher as $k=>$item)
        {
            //如果有科目，把最后一个多余的逗号去掉
            if (!empty($item['subject']))
            {
                $teacher[$k]['subject'] = substr($item['subject'], 0, -1);
            }
        }

        $this->ApiReturn(1, 'success', $teacher);
    }

    //首页启动图
    public function startimg()
    {
        $find = M('Config')->find();
        $list['img'] = $find['start_img'] ;
        $this->ApiReturn(1, 'success',$list);
    }

    /**
     * 热搜词
     */
    public function hotWords()
    {
        $hot_sub = M('Subject')->where(['is_hot'=>1])->field('s_name')->select();
        $list=[];
        foreach ($hot_sub as $item){
            array_push($list, $item['s_name']);
        }
        $this->ApiReturn(1, 'success', $list);
    }

    /**
     * 推荐机构
     * sunfan
     * 2018.7.11
     */
    public function starManaged()
    {
        $data = $this->data;
        $page = $data['page']??1;
        $id = S($data['token'])??$this->ApiReturn(20003, '请登录');
        $where['pid'] = $id;
        $where['is_home'] = 2;
        if (!empty($data['keyword'])){
            $where['s_name'] = ['like', "%".$data['keyword']."%"];
        }
        $list = M('SmallTableSort')
            ->where($where)
            ->field('sid as st_id, s_name, s_img, address, c_name, s_content, s_phone, distance, star, view_num, s_type')
            ->page($page)
            ->order(['sort'=>"ASC", 'distance1'=>"DESC"])
            ->select();
        if (!$list){
            $this->ApiReturn(0, 'success');
        }else{
            $this->ApiReturn(1, 'success', $list);
        }
    }

    /**
     * 机构科目
     */
    public function getSmallTableCate()
    {
        $info = M('smalltable_cate')->order('sort')->field('sort',true)->select();
        if (!$info){
            $this->ApiReturn(0, 'success');
        }else{
            array_unshift($info,['id'=>0,'title'=>'全部']);
            $this->ApiReturn(1, 'success', $info);
        }
    }
}