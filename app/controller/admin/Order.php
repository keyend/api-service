<?php
namespace app\controller\admin;
use app\model\system\Pay;
class Order extends Controller
{
    /**
     * 订单完成
     *
     * @return void
     */
    public function complete()
    {
        if (IS_POST) {
            $pay_id = $this->params['id'];
            $pay = Pay::find($pay_id);
            if (empty($pay)) {
                return $this->error('提交失败');
            }
            $order = $pay->order;
            if ($order->operational != 'admin') {
                return $this->error('订单不支持此操作');
            } elseif (!checkAccess("sysOrderComplete")) {
                return $this->error('无此权限');
            }

            $res = $pay->complete($this->params);
            return $this->success("SUCCESS", $res);
        }
    }
}