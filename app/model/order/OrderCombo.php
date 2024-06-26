<?php
namespace app\model\order;
/**
 * 套餐订单
 * @version 1.0.0
 */
use think\facade\Db;
use think\facade\Log;
use app\Model;
use app\model\member\Member;
use app\model\member\Account;

class OrderCombo extends Model
{
    protected $name = 'order_combo';

    /**
     * 订单类型
     * @var integer
     */
    const ORDER_TYPE = 3;

    /**
     * 订单类型
     * @var string
     */
    const ORDER_TYPE_NAME = '套餐订单';

    /**
     * 类型名称
     *
     * @return void
     */
    public function typeName()
    {
        return self::ORDER_TYPE_NAME;
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
     * 关联订单信息
     *
     * @return void
     */
    public function orderInfo()
    {
        return $this->hasOne(Order::class, 'order_id', 'order_id');
    }

    /**
     * 关联套餐
     *
     * @return void
     */
    public function combo()
    {
        return $this->hasOne(MemberCombo::class, 'id', 'combo_id');
    }

    /**
     * 获得会员对应的到期时间
     *
     * @param integer $member_id
     * @return void
     */
    public function getLastExpireTime($member_id = 0)
    {
        $expire_time = (int) self::whereRaw("combo_method != :times AND expire_time > UNIX_TIMESTAMP()", ['times' => 'times'])->order('id DESC')->value('expire_time');
        return $expire_time;
    }

    /**
     * 返回当前激活的订单
     *
     * @param array $condition
     * @return void
     */
    public function getActiveOrder($condition = [])
    {
        $condition[] = ['status', '=', 1];
        $order = self::where($condition)->whereRaw("(`combo_method` != :times AND `expire_time` > UNIX_TIMESTAMP()) OR (`combo_method` = :times AND `times` > 0)")->order('id ASC')->find();
        return $order;
    }

    /**
     * 创建新订单
     *
     * @param integer $num
     * @param MemberCombo $combo
     * @param Member $member
     * @param string $pay_type
     * @return void
     */
    public function create_order($num = 0, $combo = null, $member = null, $pay_type = 'BALANCE')
    {
        $combo_money = floatval($combo['combo_money']);
        $goods_money = $combo_money * $num;
        $order_money = $goods_money;
        $pay_money = $order_money;

        Db::startTrans();
        try {
            $order = Order::create([
                'order_type' => self::ORDER_TYPE,
                'member_id' => $member->member_id,
                'nickname' => $member->nickname,
                'mobile' => $member->mobile,
                'goods_num' => $num,
                'goods_money' => $goods_money,
                'order_money' => $order_money,
                'pay_money' => $pay_money,
                'pay_type' => $pay_type,
                'delivery_type' => 0,
                'buyer_ip' => request()->ip(),
                'create_time' => time()
            ]);

            $orderData = [
                'order_id' => $order->order_id,
                'member_id' => $member->member_id,
                'combo' => $combo->combo,
                'combo_money' => $combo->combo_money,
                'combo_info' => json_encode($combo),
                'combo_method' => $combo->bill_method,
                'times' => $combo->times,
                'num' => $num
            ];
            if ($combo->bill_method != 'times') {
                $orderData['expire_time'] = $combo->getExpireTime($num, $this->getLastExpireTime($member->member_id));
            }
            $combo_order = self::create($orderData);
            $order->addCreateLog();
            $order = $order->onlinePay();
            Db::commit();
        } catch(\Exception $e) {
            Db::rollback();
            Log::error("{$e->getFile()}:{$e->getLine()}");
            Log::error("购买套餐失败： {$e->getMessage()}");
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $order->pay;
    }

    /**
     * 提交支付
     *
     * @param array $order
     * @return void
     */
    public function pay($order = null)
    {
        if ($order->order_status == 1) {
            $pay = $order->pay;
            if ($pay->pay_type == 'WXPAY') {
                //微信支付API
            } elseif($pay->pay_type == 'BALANCE') {
                //冻结余额
                $member = $this->memberInfo;
                $member->lock_balance_money = Db::raw("lock_balance_money + $order->pay_money");
                $member->balance_money = Db::raw("balance_money - $order->pay_money");
                $member->save();
            }
        }
    }

    /**
     * 订单支付结束
     *
     * @param mixed $order
     * @return void
     */
    public function complete($order)
    {
        if ($order->order_status == 2) {
            return $this->success($order);
        } else {
            return $this->cancel($order);
        }
    }

    /**
     * 订单支付失败
     *
     * @param mixed $order
     * @return void
     */
    private function cancel($order = null)
    {
        if ($order->order_status == 3) {
            $member = $this->memberInfo;
            $member->lock_balance_money = Db::raw("lock_balance_money - $order->pay_money");
            $member->balance_money = Db::raw("balance_money + $order->pay_money");
            $member->save();
            $order->addLogger("订单购买失败");
            Log::info("订单 {$order->member_id}_{$order->order_id} 购买失败");
        }
    }

    /**
     * 订单支付成功
     *
     * @param mixed $order
     * @return void
     */
    public function success($order = null)
    {
        if ($order->order_status == 2) {
            $order->addLogger("订单支付成功");
            $timestamp = time();
            $member = $this->memberInfo;
            $member->update_time = $timestamp;
            $pay = $order->pay;
            if ($pay->pay_type == 'BALANCE') {
                $member->lock_balance_money = Db::raw("lock_balance_money - $order->pay_money");
            }
            $member->save();

            $account_data = [
                'account_title' => '购买 ' . self::ORDER_TYPE_NAME,
                'value' => 0 - $pay->pay_money,
                'relate_id' => $order->order_id
            ];
            if ($pay->pay_type != 'BALANCE') {
                $account_data['account_type'] = strtolower($pay->pay_type);
            }

            $account_model = new Account();
            $account_model->addAccount($account_data, $member, false);

            $this->status = 1;
            $this->save();
        }
    }

    /**
     * 返回列表备注
     *
     * @return void
     */
    public function getRemarkField()
    {
        $res = "购买【{$this->combo}】共{$this->num}份";
        if ($this->combo_method != 'times') {
            $res.= "到期时间 " . date('Y-m-d', $this->expire_time);
        } else {
            $combo_info = json_decode($this->combo_info, true);
            $res.= "使用次数 " . $combo_info['times'];
        }
        return $res;
    }

    /**
     * 返回订单购买商品列表
     *
     * @return void
     */
    public function getGoodsList()
    {
        $combo_info = json_decode($this->combo_info, true);
        $goods_money = floatval($combo_info['combo_money'] ?? 0);
        $goods_price = $goods_money * $this->num;
        $goods_subtitle = $this->combo_method == 'times' ? "可调用{$combo_info['times']}次" : "到期时间" . date('Y-m-d', $this->expire_time);
        $order_goods = [];
        $order_goods[] = [
            'num' => $this->num,
            'unitprice' => $goods_money,
            'goods_price' => $goods_price,
            'goods_title' => $this->combo,
            'goods_subtitle' => $goods_subtitle
        ];

        return $order_goods;
    }
}