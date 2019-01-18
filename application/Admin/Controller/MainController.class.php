<?php

namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MainController extends AdminbaseController
{

    public function index()
    {
        //今日数据统计
        $jiazhang_count = M("parents")->where(['p_time' => ['like', date('Y-m-d') . '%']])->count();
        $jiaoyujigou_count = M("small_table")->where(['s_type' => 2, 's_status' => ['LT', 2], 's_time' => ['like', date('Y-m-d') . '%']])->count();
        $tuoguanjigou_count = M("small_table")->where(['s_type' => 1, 's_status' => ['LT', 2], 's_time' => ['like', date('Y-m-d') . '%']])->count();
        $kecheng_count = M("educational_course")->where(['add_time' => ['like', date('Y-m-d') . '%']])->count();
        $huodong_count = M("activity")->where(['status' => 1, 'add_time' => ['like', date('Y-m-d') . '%']])->count();
        $dailishangtixian_count = M("application")->where(['u_type' => 3, 'type' => 2, 'status' => 1, 'a_time' => ['like', date('Y-m-d') . '%']])->count();
        $huodong_total_amount = M("ActivityOrder")->where(['status' => 2, 'sign_time' => ['like', date('Y-m-d') . '%']])->sum('pay_money');
        $huodong_fenrun_amount = M("ActivityOrder")->where(['status' => 2, 'sign_time' => ['like', date('Y-m-d') . '%']])->sum('platform_money');

        $jinri_statistic = [
            jiazhang_count => $jiazhang_count,
            jiaoyujigou_count => $jiaoyujigou_count,
            tuoguanjigou_count => $tuoguanjigou_count,
            kecheng_count => $kecheng_count,
            huodong_count => $huodong_count,
            dailishangtixian_count => $dailishangtixian_count,
            huodong_total_amount => $huodong_total_amount ?? 0,
            huodong_fenrun_amount => $huodong_fenrun_amount ?? 0
        ];
        $this->assign('jinri_statistic', $jinri_statistic);

        $this->display();
    }

    /**
     * 亲子活动订单状态统计
     * */
    public function getHuoDongPieData()
    {
        $startDate = I('post.startDate');
        $endDate = I('post.endDate');
        if ($this->diffBetweenTwoDays($startDate, $endDate) > 60) {
            echo "-1";
            exit();
        }
        $where = [
            'status' => ['in', [1, 2, 3, 4, 5]],
            'sign_time' => ['between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]
        ];
        $pie_data = [];
        $list_data = M("activity_order")->where($where)
            ->field('status, count(1) as total')
            ->group('status')->select();
        $order_status = [1 => '待支付', 2 => '报名成功', 3 => '申请退款', 4 => '已退款', 5 => '已取消'];
        foreach ($list_data as $key => $value) {
            $pie_data[$key]['value'] = $list_data[$key]['total'];
            $pie_data[$key]['name'] = $order_status[$list_data[$key]['status']];
        }
        echo json_encode($pie_data);
    }

    /**
     * 在线课程订单状态统计
     * */
    public function getKeChengPieData()
    {
        $startDate = I('post.startDate');
        $endDate = I('post.endDate');
        if ($this->diffBetweenTwoDays($startDate, $endDate) > 60) {
            echo "-1";
            exit();
        }
        $where = [
            'status' => ['in', [1, 2, 3, 4, 5]],
            'o_time' => ['between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]
        ];
        $pie_data = [];
        $list_data = M("educational_order")->where($where)
            ->field('status, count(1) as total')
            ->group('status')->select();
        $order_status = [1 => '待支付', 2 => '进行中', 3 => '已完成', 4 => '已取消', 5 => '已退款'];
        foreach ($list_data as $key => $value) {
            $pie_data[$key]['value'] = $list_data[$key]['total'];
            $pie_data[$key]['name'] = $order_status[$list_data[$key]['status']];
        }
        echo json_encode($pie_data);
    }

    /**
     * 托管订单状态统计
     * */
    public function getTuoGuanPieData()
    {
        $startDate = I('post.startDate');
        $endDate = I('post.endDate');
        if ($this->diffBetweenTwoDays($startDate, $endDate) > 60) {
            echo "-1";
            exit();
        }
        $where = [
            'status' => ['in', [1, 2, 3, 4, 5]],
            'o_time' => ['between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]
        ];
        $pie_data = [];
        $list_data = M("hosting_order")->where($where)
            ->field('status, count(1) as total')
            ->group('status')->select();
        $order_status = [1 => '待支付', 2 => '进行中', 3 => '已完成', 4 => '已取消', 5 => '已退款'];
        foreach ($list_data as $key => $value) {
            $pie_data[$key]['value'] = $list_data[$key]['total'];
            $pie_data[$key]['name'] = $order_status[$list_data[$key]['status']];
        }
        echo json_encode($pie_data);
    }

    /**
     * 各个订单金额统计
     * */
    public function getOrderAmountData()
    {
        $startDate = I('post.startDate');
        $endDate = I('post.endDate');
        if ($this->diffBetweenTwoDays($startDate, $endDate) > 60) {
            echo "-1";
            exit();
        }

        $huodong_amount_data = M("activity_order")->where(['status' => 2, 'sign_time' => ['between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]])
            ->field("date_format(sign_time,'%Y-%m-%d') as date, sum(pay_money) as amount")
            ->group("date_format(sign_time,'%Y-%m-%d')")->select();

        $kecheng_amount_data = M("educational_order")->where(['status' => 2, 'o_time' => ['between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]])
            ->field("date_format(o_time,'%Y-%m-%d') as date, sum(total_money) as amount")
            ->group("date_format(o_time,'%Y-%m-%d')")->select();

        $tuoguan_amount_data = M("hosting_order")->where(['status' => 2, 'o_time' => ['between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]])
            ->field("date_format(o_time,'%Y-%m-%d') as date, sum(total_money) as amount")
            ->group("date_format(o_time,'%Y-%m-%d')")->select();

        $line_data = [];
        $start_time = strtotime($startDate);
        $end_time = strtotime($endDate);
        for ($day = $start_time; $day <= $end_time; $day += 24 * 3600) {
            $date = date('Y-m-d', $day);
            $line_data['huodong'][] = [
                'date' => $date,
                'amount' => $this->getDataFromArray($huodong_amount_data, $date) / 10000
            ];
            $line_data['kecheng'][] = [
                'date' => $date,
                'amount' => $this->getDataFromArray($kecheng_amount_data, $date) / 10000
            ];
            $line_data['tuoguan'][] = [
                'date' => $date,
                'amount' => $this->getDataFromArray($tuoguan_amount_data, $date) / 10000
            ];
        }
//        echo json_encode(M("educational_order")->getLastSql());
        echo json_encode($line_data);
    }


    private function getDataFromArray($array, $date)
    {
        foreach ($array as $key => $value) {
            if ($date == $value['date']) {
                return $value['amount'];
            }
        }
        return 0;
    }

    /**
     * 求两个日期之间相差的天数
     * (针对1970年1月1日之后，求之前可以采用泰勒公式)
     * @param string $day1
     * @param string $day2
     * @return number
     */
    function diffBetweenTwoDays($day1, $day2)
    {
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);

        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        return ($second1 - $second2) / 86400;
    }
}