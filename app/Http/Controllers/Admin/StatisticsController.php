<?php
/**
 * Copyright (c) 2019. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Models\Order;
use App\Http\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatisticsController extends CommonController
{
    public function index()
    {
        return view('Admin.Statistics.index');
    }

    private function getStartAndEndDate($type, $sub = 0)
    {
        switch ($type) {
            case 'day':
                if ($sub) {
                    $start_date = Carbon::today()->subDay($sub)->toDateTimeString();
                    $end_date = Carbon::now()->endOfDay()->subDay($sub)->toDateTimeString();
                } else {
                    $start_date = Carbon::today()->toDateTimeString();
                    $end_date = Carbon::now()->toDateTimeString();
                }
                break;
            case 'month':
                if ($sub) {
                    $start_date = Carbon::now()->subMonth($sub)->firstOfMonth()->toDateTimeString();
                    $end_date = Carbon::now()->subMonth($sub)->endOfMonth()->toDateTimeString();
                } else {
                    $start_date = Carbon::now()->firstOfMonth()->toDateTimeString();
                    $end_date = Carbon::now()->endOfMonth()->toDateTimeString();
                }
                break;
            case 'year':
                if ($sub) {
                    $start_date = Carbon::now()->subYear($sub)->firstOfYear()->toDateTimeString();
                    $end_date = Carbon::now()->subYear($sub)->endOfYear()->toDateTimeString();
                } else {
                    $start_date = Carbon::now()->firstOfYear()->toDateTimeString();
                    $end_date = Carbon::now()->endOfYear()->toDateTimeString();
                }
                break;
            default:
                if ($sub) {
                    $start_date = Carbon::today()->subDay($sub)->toDateTimeString();
                    $end_date = Carbon::now()->endOfDay()->subDay($sub)->toDateTimeString();
                } else {
                    $start_date = Carbon::today()->toDateTimeString();
                    $end_date = Carbon::now()->toDateTimeString();
                }
                break;
        }
        return [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];
    }

    public function recharge(Request $request, Order $order)
    {
        $type = trim($request->type);
        $date = $this->getStartAndEndDate($type);
        $where[] = ['status', '=', 5];
        $where[] = ['created_at', '>', $date['start_date']];
        $where[] = ['created_at', '<=', $date['end_date']];
        $amount = $order->where($where)->sum('amount');
        $sub_date = $this->getStartAndEndDate($type, 1);
        $sub_where[] = ['status', '=', 5];
        $sub_where[] = ['created_at', '>', $sub_date['start_date']];
        $sub_where[] = ['created_at', '<=', $sub_date['end_date']];
        $sub_amount = $order->where($sub_where)->sum('amount');
        $ratio = "-";
        if ($sub_amount != 0) {
            $ratio = round(($amount / $sub_amount - 1) * 100, 2);
            $ratio .= "%";
        }
        return json_encode([
            "status_code" => 200,
            "message" => "SUCCESS",
            "data" => [
                'total_recharge' => $amount,
                'recharge_ratio' => $ratio,
                'type' => $type,
            ],
        ]);
    }

    public function order(Request $request, Order $order)
    {
        $type = trim($request->type);
        $date = $this->getStartAndEndDate($type);
        $where[] = ['created_at', '>', $date['start_date']];
        $where[] = ['created_at', '<=', $date['end_date']];
        $count = $order->where($where)->OrderType('point_shopping')->count();
        $sub_date = $this->getStartAndEndDate($type, 1);
        $sub_where[] = ['created_at', '>', $sub_date['start_date']];
        $sub_where[] = ['created_at', '<=', $sub_date['end_date']];
        $sub_count = $order->where($sub_where)->OrderType('point_shopping')->count();
        $ratio = "-";
        if ($sub_count != 0) {
            $ratio = round(($count / $sub_count - 1) * 100, 2);
            $ratio .= "%";
        }
        return json_encode([
            "status_code" => 200,
            "message" => "SUCCESS",
            "data" => [
                'count_order' => $count,
                'order_ratio' => $ratio,
                'type' => $type,
            ],
        ]);
    }

    public function order_chart(Request $request, Order $order)
    {
        $type = trim($request->type);
        if (!in_array($type, ['day', 'month', 'year'])) {
            $type = 'day';
        }
        $year = date('Y', time());
        $month = date('n', time());
        $day = date('j', time());
        $hour = date('G', time());
        $y_axis_left_max = $y_axis_right_max = 0;
        $y_axis_left_min = $y_axis_right_min = 0;
        switch ($type) {
            case 'day':
                for ($i = 0; $i <= 23; $i++) {
                    $x_axis[] = "{$i}点";
                    if ($i <= $hour) {
                        $this_month_order_amount = $order->whereBetween('created_at', ["{$year}-{$month}-{$day} {$i}:00:00", "{$year}-{$month}-{$day} {$i}:59:59"])
                            ->where('status', '>', 1)
                            ->sum('amount');
                        $this_month_order_num = $order->whereBetween('created_at', ["{$year}-{$month}-{$day} {$i}:00:00", "{$year}-{$month}-{$day} {$i}:59:59"])
                            ->where('status', '>', 1)
                            ->count();
                        $recharge_order_amount[] = floatval($this_month_order_amount);
                        $recharge_order_num[] = $this_month_order_num;
                        $y_axis_left_max = max($y_axis_left_max, $this_month_order_amount);
                        $y_axis_left_min = min($y_axis_left_min, $this_month_order_amount);
                        $y_axis_right_max = max($y_axis_right_max, $this_month_order_num);
                        $y_axis_right_min = min($y_axis_right_min, $this_month_order_num);
                    }
                }
                $current = '今日';
                $last = '昨日';
                break;
            case 'month':
                $month_max_day = intval(date('n', Carbon::now()->endOfMonth()->timestamp));
                for ($i = 1; $i <= $month_max_day; $i++) {
                    $x_axis[] = "{$i}日";
                    if ($i <= $day) {
                        $this_month_order_amount = $order->whereYear('created_at', $year)
                            ->whereMonth('created_at', $month)
                            ->whereDay('created_at', $i)
                            ->where('status', '>', 1)
                            ->sum('amount');
                        $this_month_order_num = $order->whereYear('created_at', $year)
                            ->whereMonth('created_at', $month)
                            ->whereDay('created_at', $i)
                            ->where('status', '>', 1)
                            ->count();
                        $recharge_order_amount[] = $this_month_order_amount;
                        $recharge_order_num[] = $this_month_order_num;
                        $y_axis_left_max = max($y_axis_left_max, $this_month_order_amount);
                        $y_axis_left_min = min($y_axis_left_min, $this_month_order_amount);
                        $y_axis_right_max = max($y_axis_right_max, $this_month_order_num);
                        $y_axis_right_min = min($y_axis_right_min, $this_month_order_num);
                    }
                }
                $current = '本月';
                $last = '上月';
                break;
            case 'year':
                for ($i = 1; $i <= 12; $i++) {
                    $x_axis[] = "{$i}月";
                    if ($i <= $month) {
                        $this_month_order_amount = $order->whereYear('created_at', $year)
                            ->whereMonth('created_at', $i)
                            ->where('status', '>', 1)
                            ->sum('amount');
                        $this_month_order_num = $order->whereYear('created_at', $year)
                            ->whereMonth('created_at', $i)
                            ->where('status', '>', 1)
                            ->count();
                        $recharge_order_amount[] = $this_month_order_amount;
                        $recharge_order_num[] = $this_month_order_num;
                        $y_axis_left_max = max($y_axis_left_max, $this_month_order_amount);
                        $y_axis_left_min = min($y_axis_left_min, $this_month_order_amount);
                        $y_axis_right_max = max($y_axis_right_max, $this_month_order_num);
                        $y_axis_right_min = min($y_axis_right_min, $this_month_order_num);
                    }
                }
                $current = '今年';
                $last = '去年';
                break;
            default:
                break;
        }
        if ($y_axis_left_max == 0) $y_axis_left_max = 10;
        if ($y_axis_right_max == 0) $y_axis_right_max = 10;
        $y_axis_left_max = getMinimumWholeDecimal($y_axis_left_max, true);
        $y_axis_right_max = getMinimumWholeDecimal($y_axis_right_max, true);
        $y_axis_left_min = getMinimumWholeDecimal($y_axis_left_min, false);
        $y_axis_right_min = getMinimumWholeDecimal($y_axis_right_min, false);
        $y_axis_left_interval = ($y_axis_left_max - $y_axis_left_min) / 5;
        $y_axis_right_interval = ($y_axis_right_max - $y_axis_right_min) / 5;
        $date = $this->getStartAndEndDate($type);
        $where[] = ['status', '>', 1];
        $where[] = ['created_at', '>', $date['start_date']];
        $where[] = ['created_at', '<=', $date['end_date']];
        $current_order_amount = $order->where($where)->sum('amount');
        $current_order_count = $order->where($where)->count();
        $sub_date = $this->getStartAndEndDate($type, 1);
        $sub_where[] = ['status', '>', 1];
        $sub_where[] = ['created_at', '>', $sub_date['start_date']];
        $sub_where[] = ['created_at', '<=', $sub_date['end_date']];
        $last_order_amount = $order->where($sub_where)->sum('amount');
        $last_order_count = $order->where($sub_where)->count();
        return json_encode([
            "status_code" => 200,
            "message" => "SUCCESS",
            "data" => [
                'text' => [
                    'current' => $current,
                    'last' => $last,
                ],
                'type' => $type,
                'chart_option' => [
                    'x_axis' => $x_axis,
                    'y_axis' => [
                        'left' => [
                            'min' => $y_axis_left_min,
                            'max' => $y_axis_left_max,
                            'interval' => $y_axis_left_interval,
                        ],
                        'right' => [
                            'min' => $y_axis_right_min,
                            'max' => $y_axis_right_max,
                            'interval' => $y_axis_right_interval,
                        ],
                    ],
                    'series' => [
                        'recharge_order_amount' => $recharge_order_amount,
                        'recharge_order_num' => $recharge_order_num,
                    ],
                    'detail' => [
                        'current_order_count' => $current_order_count,
                        'last_order_count' => $last_order_count,
                        'current_order_amount' => $current_order_amount,
                        'last_order_amount' => $last_order_amount,
                    ]
                ],
            ],
        ]);
    }

    public function user(Request $request, User $user)
    {
        $type = trim($request->type);
        $scope = trim($request->scope);
        if ($scope == 'new') {
            $field = 'created_at';
        } else {
            $field = 'updated_at';
        }
        $date = $this->getStartAndEndDate($type);
        $where[] = [$field, '>', $date['start_date']];
        $where[] = [$field, '<=', $date['end_date']];
        $count = $user->where($where)->count();
        $sub_date = $this->getStartAndEndDate($type, 1);
        $sub_where[] = [$field, '>', $sub_date['start_date']];
        $sub_where[] = [$field, '<=', $sub_date['end_date']];
        $sub_count = $user->where($sub_where)->count();
        $ratio = "-";
        if ($sub_count != 0) {
            $ratio = round(($count / $sub_count - 1) * 100, 2);
            $ratio .= "%";
        }
        return json_encode([
            "status_code" => 200,
            "message" => "SUCCESS",
            "data" => [
                'count_user' => $count,
                'user_ratio' => $ratio,
                'type' => $type,
            ],
        ]);
    }
}
