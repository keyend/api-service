<?php
namespace app\model\protocol;
use app\Model;
use app\model\order\OrderCombo;
use app\model\member\Member;

class Logs extends Model
{
    protected $name = 'protocol_log';

    /**
     * 接口
     * 
     * @collection relation.model
     */
    public function protocolInfo()
    {
        return $this->hasOne(Protocol::class, 'id', 'protocol_id');
    }

    /**
     * 套餐订单
     * @collection relation.model
     *
     * @return void
     */
    public function comboOrder()
    {
        return $this->hasOne(OrderCombo::class, 'order_id', 'order_id');
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
}