<?php
namespace app\model\order;
/**
 * 订单
 * @version 1.0.0
 */
use think\facade\Db;
use think\facade\Log;
use app\Model;
use app\model\member\Member;
use app\model\system\Pay;

class Order extends Model
{
    protected $name = 'order';
    protected $pk = 'order_id';
    protected $orderType = [
        [ 'order_type' => 3, 'order_type_name' => '套餐订单' ]
    ];
    protected $orderStatusList = [
        [ 'status' => 0, 'status_name' => '未支付' ],
        [ 'status' => 1, 'status_name' => '支付中' ],
        [ 'status' => 2, 'status_name' => '已支付' ],
        [ 'status' => 3, 'status_name' => '交易失败' ]
    ];

    const TIMEOUT = 900;
    const STATUS_PENDING = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAILURE = 3;
    const PAY_STATUS_SUCCESS = 1;

    /**
     * 关联套餐
     *
     * @return void
     */
    public function combo()
    {
        return $this->hasOne(OrderCombo::class);
    }

    /**
     * 关联会员
     *
     * @return void
     */
    public function memberInfo()
    {
        return $this->hasOne(Member::class, 'member_id', 'member_id');
    }

    /**
     * 关联支付
     *
     * @return void
     */
    public function pay()
    {
        return $this->hasOne(Pay::class, 'id', 'pay_id');
    }

    /**
     * 返回子级
     *
     * @return void
     */
    public function branch()
    {
        $orderType = (int) $this->order_type;
        if ($orderType == 3) {
            return $this->combo;
        }
    }

    /**
     * 唤起订单支付
     *
     * @return void
     */
    public function onlinePay()
    {
        if (empty($this->out_trade_no)) {
            $trade_no = make_trade_no();
            $this->out_trade_no = $trade_no;
        }
        $operational = 'member';
        $operator_id = $member->member_id;
        if (defined('S1')) {
            $operational = 'admin';
            $operator_id = S1;
        }
        $this->operational = $operational;
        $this->uid = $operator_id;

        $pay = Pay::create([
            'member_id' => $this->member_id,
            'pay_type' => $this->pay_type,
            'pay_money' => $this->pay_money,
            'event' => "OrderPay",
            'params' => json_encode($this->getData()),
            'create_time' => time()
        ]);

        $this->pay_id = $pay->id;
        $this->update_time = time();
        $this->order_status = self::STATUS_PENDING;
        $this->save();
        $this->branch()->pay($this);
        $this->addLogger("提交支付");

        async([\app\event\OrderPay::class, 'timeout'], [ $pay->id ], self::TIMEOUT);
        return $this;
    }

    /**
     * 支付完成
     *
     * @return void
     */
    public function payCompleted() 
    {
        if ($this->order_status != self::STATUS_PENDING) {
            return false;
        }

        $pay = Pay::find($this->pay_id);
        Log::info("订单 {$this->member_id}_{$this->order_id} 支付完成 -> " . json_encode($pay));
        $this->order_status = $pay->pay_status == self::PAY_STATUS_SUCCESS ? self::STATUS_SUCCESS : self::STATUS_FAILURE;
        $this->out_trade_no = $pay->out_trade_no;
        $this->save();
        $this->pay = $pay;
        $this->branch()->complete($this);

        event("OrderComplete", $this->getData());
    }

    /**
     * 删除订单
     *
     * @return void
     */
    public function drop_order()
    {
        $this->branch()->delete();
        $this->delete();
    }

    /**
     * 记录创建日志
     *
     * @return void
     */
    public function addCreateLog()
    {
        $this->addLogger('订单生成');
    }

    /**
     * 记录操作日志
     *
     * @param string $msg
     * @return void
     */
    public function addLogger($msg)
    {
        $data = (new OrderLog)->getLoggerData(['content' => $msg]);
        async([__CLASS__, 'addLoggerAsync'], [$this->order_id, $data]);
    }

    /**
     * 记录操作日志
     *
     * @param integer $order_id
     * @param array $data
     * @return void
     */
    public function addLoggerAsync($order_id, $data = [])
    {
        $order = self::where('order_id', $order_id)->find();
        if (!empty($order)) {
            $data['order_id'] = $order->order_id;
            $data['order_status'] = $order->order_status ?? 0;
            return OrderLog::create($data);
        }
    }

    /**
     * 返回订单操作记录
     *
     * @return void
     */
    public function getLogsList()
    {
        return OrderLog::where('order_id', $this->order_id)->order('id DESC')->select();
    }
}