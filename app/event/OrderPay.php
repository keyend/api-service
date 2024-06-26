<?php
/*
 * @Author: bluefox
 * @Motto: Running fox looking for dreams
 * @Date: 2020-12-30 12:52:34
 * @LastEditTime: 2021-10-15 23:24:31
 */
namespace app\event;

use think\facade\Log;
use think\facade\Event;
use app\model\order\Order;
use app\model\system\Pay;

class OrderPay
{
    /**
     * 支付操作变更
     *
     * @param array $param
     * @return void
     */
    public function handle($param = [])
    {
        $order = Order::find($param['order_id']);
        if (!empty($order)) {
            $order->payCompleted();
        }
    }

    /**
     * 支付超时
     *
     * @param integer $pay_id
     * @return void
     */
    public function timeout($pay_id = 0)
    {
        $pay = Pay::find($pay_id);
        if (empty($pay)) {
            Log::error("Payment_{$pay_id} not found");
        } elseif ($pay->status != 0) {
            return false;
        } elseif ($pay->event_status != 0) {
            return false;
        }
        $pay->cancel();
    }
}