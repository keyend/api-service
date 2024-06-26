<?php
namespace app\model\order;
/**
 * 套餐订单
 * @version 1.0.0
 */
use think\facade\Db;
use app\Model;

class OrderLog extends Model
{
    protected $name = 'order_log';

    /**
     * 获取日志内容
     *
     * @param array $data
     * @return void
     */
    public function getLoggerData($data = [])
    {
        if (defined('S1')) {
            $uid = S1;
            $username = S2;
        }

        $data['uid'] = $uid ?? 0;
        $data['username'] = $username ?? '';
        $data['create_time'] = time();

        return $data;
    }
}