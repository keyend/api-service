<?php
namespace app\model\system;
/**
 * 支付
 *
 * @package app.common.model
 * @author: k.
 * @date: 2021-05-10 20:19:31
 */
use think\facade\Log;
use think\facade\Db;
use app\Model;
use app\model\member\Member;
use app\model\order\Order;

class Pay extends Model
{
    protected $name = 'pay';

    /**
     * 关联会员
     *
     * @return void
     */
    public function memberInfo()
    {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }

    /**
     * 关联订单
     *
     * @return void
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'id', 'pay_id');
    }

    /**
     * 支付附加参数
     *
     * @param mixed $value
     * @return void
     */
    public function getParamsAttr($value)
    {
        return json_decode($value, true);
    }

    /**
     * 支付参数
     *
     * @param mixed $value
     * @return void
     */
    public function getPayParamAttr($value)
    {
        return json_decode($value, true);
    }

    /**
     * 绑定流水号
     *
     * @param array $param
     * @return void
     */
    public function bind($param = [])
    {
        $out_trade_no = $param['out_trade_no'] ?? '';

        $this->out_trade_no = $out_trade_no;
        $this->save();

        if (!empty($out_trade_no)) {
            $this->query_refresh();
        }
    }

    /**
     * 查询刷新订单状态
     *
     * @return void
     */
    public function query_refresh()
    {
        if ($this->pay_status != 0 || $this->event_status != 0) {
            return true;
        }
 
        $times = 30;
        $this->query_times = $times;
        $this->save();
        $this->listener($this->getData());
    }

    /**
     * 监听订单状态
     *
     * @param array $param
     * @return void
     */
    public function listener($param = [])
    {
        //TODO
    }

    /**
     * 取消支付
     *
     * @return void
     */
    public function cancel($out_trade_no = '', $param = [])
    {
        if (!empty($out_trade_no)) {
            $this->out_trade_no = $out_trade_no;
        }
        $this->pay_status = 0;
        $this->pay_response = json_encode($param);
        $this->event_status = 1;
        $this->pay_time = time();
        $this->update_time = time();
        $this->save();
        event($this->event, $this->params);
        return $this;
    }

    /**
     * 支付完成
     *
     * @param array $param
     * @return void
     */
    public function complete($param = [])
    {
        if ($this->pay_status != 0 || $this->event_status != 0) {
            return null;
        }

        Log::warning("complete -> " . json_encode($param));
        $response = $param['response'] ?? [];
        if (empty($response)) {
            throw new \Exception(lang('public.invalid param'));
        }

        $payStatus = $param['status'] ?? false;
        $out_trade_no = $param['out_trade_no'] ?? ($response['out_trade_no'] ?? '');
        if (empty($out_trade_no)) {
            $payStatus = false;
        }

        Db::startTrans();
        try {
            $dispatch = $payStatus != false ? 'success' : 'cancel';
            $result = $this->$dispatch($out_trade_no, $data);
            Db::commit();
        } catch(\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * 支付成功
     *
     * @param string $out_trade_no
     * @param array $param
     * @return void
     */
    public function success($out_trade_no = '', $param = [])
    {
        if (!empty($out_trade_no)) {
            $this->out_trade_no = $out_trade_no;
        }
        $this->pay_response = json_encode($param);
        $this->pay_status = 1;
        $this->pay_time = time();
        $this->event_status = 1;
        $this->update_time = time();
        $this->save();
        async([\app\event\OrderPay::class, 'handle'], [ $this->params ], 1);
        return $this;
    }
}