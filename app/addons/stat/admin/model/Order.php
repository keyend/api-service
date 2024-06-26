<?php
namespace app\addons\stat\admin\model;
use app\Model;
use app\model\member\Member;
/**
 * 订单管理
 * 
 * @version 1.0.0
 */
class Order extends \app\model\order\Order
{
    /**
     * 支付方式
     *
     * @var array
     */
    protected $paymentTypeList = [
        [ 'value' => 'BALANCE', 'title' => '余额支付' ],
        [ 'value' => 'DIRECT', 'title' => '系统赠送' ],
    ];

    /**
     * 返回列表
     *
     * @param integer $page
     * @param integer $limit
     * @param array $condition
     * @param string $field
     * @param array $join
     * @return void
     */
    public function getList(int $page, int $limit, Array $condition = [])
    {
        $query = $this->order('order_id DESC');
        $query->where($condition);
        $count = $query->field($field)->count();
        $list = $query->page($page, $limit)->select();
        if (!empty($list)) {
            foreach($list as &$row) {
                $branch = $row->branch();
                $row->order_type_name = $branch->typeName();
                $row->order_remark = $branch->getRemarkField();
                $row->goods_list = $branch->getGoodsList();
                $row = $row->toArray();
                $branch = $branch->toArray();
                $row = array_merge($row, $branch);
            }
        }

        return compact('count', 'list', 'sql');
    }

    /**
     * 返回订单详情
     *
     * @param integer $order_id
     * @return void
     */
    public function getDetail($order_id = 0)
    {
        $order = self::find($order_id);
        $order_branch = $order->branch();
        $order->order_type_name = $order_branch->typeName();
        $order->order_remark = $order_branch->getRemarkField();
        $order->goods_list = $order_branch->getGoodsList();
        $order->logs = $order->getLogsList();
        $order->pay = $order->pay;

        return $order;
    }

    /**
     * 返回仪表盘数据
     *
     * @return void
     */
    public function getDashboardData() 
    {
        $beginTime = mktime(0,0,0);
        $ret = [];
        $query = (new self())->where([['create_time', '>', $beginTime], ['order_status', '>', 0]]);
        $today = [
            'count' => $query->count(),
            'money' => $query->sum('order_money'),
        ];
        $query = Member::where([['member_id', '>', 0]]);
        $today['member_total'] = $query->count();
        $today['member_register'] = $query->where('create_time', '>', $beginTime)->count();
        $beginTime = strtotime(date('Y-m-01'));
        $query = (new self())->where([['create_time', '>', $beginTime], ['order_status', '>', 0]]);
        $monthly = [
            'count' => $query->count(),
            'money' => $query->sum('order_money')
        ];
        $query = (new self())->where([['order_status', '>', 0]]);
        $total = [
            'count' => $query->count(),
            'money' => $query->sum('order_money')
        ];

        return compact('total', 'monthly', 'today');
    }

    /**
     * 订单类型列表
     *
     * @return void
     */
    public function getOrderTypeList()
    {
        return $this->orderType;
    }

    /**
     * 订单状态列表
     *
     * @return void
     */
    public function getOrderStatusList()
    {
        return $this->orderStatusList;
    }

    /**
     * 订单支付方式列表
     *
     * @return void
     */
    public function getPayTypeList()
    {
        return $this->paymentTypeList;
    }

    /**
     * 返回时间线统计数据
     *
     * @return void
     */
    public function getOrderLineData()
    {
        $names = [
            'memberRegister' => '新增会员', 
            'orderMoney' => '订单金额',
            'orderCount' => '订单数量'
        ];
        $current = time();
        $beginTime = mktime(0,0,0);
        $timestamp = strtotime('-1 month', $beginTime);
        $dates = [];
        $i = 0;
        while($timestamp < $current) {
            $dates[$timestamp] = date('Y-m-d', $timestamp);
            $timestamp += 86400;
        }

        $template = [
            "type" => "line",
            "markPoint" => [
                "data" => [
                    [
                        "type" => "max",
                        "name" => "最大值"
                    ],
                    [
                        "type" => "min",
                        "name" => "最小值"
                    ]
                ]
            ],
            "itemStyle" => [
                "normal" => [
                    "color" => null
                ]
            ]
        ];
        $list = [];
        foreach($names as $name => $title) {
            $item = $template;
            $method = "getOrderLineData" . ucfirst($name);
            $item['name'] = $title;
            $item['data'] = $this->$method($dates);

            $list[] = $item;
        }
        $dates = array_values($dates);
        $names = array_values($names);

        return compact('names', 'dates', 'list');
    }

    /**
     * 新注册会员变动
     *
     * @param array $dates
     * @return void
     */
    private function getOrderLineDataMemberRegister($dates = [])
    {
        $result = [];
        foreach($dates as $date) {
            $result[$date] = 0;
        }
        $covage = Member::fieldRaw("DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d') date1,COUNT(*) as usage1")->group("DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d')")->select();
        if (!empty($covage)) {
            foreach($covage as $item) {
                $result[$item['date1']] = $item['usage1'];
            }
        }

        return array_values($result);
    }

    /**
     * 消息订单金额
     *
     * @param array $dates
     * @return void
     */
    private function getOrderLineDataOrderMoney($dates = [])
    {
        $result = [];
        foreach($dates as $date) {
            $result[$date] = 0;
        }
        $covage = self::where([['order_status', '=', 2]])
            ->fieldRaw("DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d') date1,SUM(order_money) as usage1")
            ->group("DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d')")
            ->select();

        if (!empty($covage)) {
            foreach($covage as $item) {
                $result[$item['date1']] = $item['usage1'];
            }
        }

        return array_values($result);
    }

    /**
     * 消息订单金额
     *
     * @param array $dates
     * @return void
     */
    private function getOrderLineDataOrderCount($dates = [])
    {
        $result = [];
        foreach($dates as $date) {
            $result[$date] = 0;
        }
        $covage = self::where([['order_status', '=', 2]])
            ->fieldRaw("DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d') date1,COUNT(*) as usage1")
            ->group("DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d')")
            ->select();

        if (!empty($covage)) {
            foreach($covage as $item) {
                $result[$item['date1']] = $item['usage1'];
            }
        }

        return array_values($result);
    }
}