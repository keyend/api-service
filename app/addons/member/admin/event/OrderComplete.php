<?php
namespace app\addons\member\admin\event;
/**
 * 订单支付成功且完成
 * 
 * @version 1.0.0
 */
use app\addons\member\admin\model\Member;
use app\addons\member\admin\model\MemberOrder;
use think\facade\Log;

class OrderComplete
{
    public function handle($order = [])
    {
        if ($order['order_type'] == 3) {
            //TODO
        }
    }
}