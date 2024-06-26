<?php
namespace app\addons\member\admin\model;
use app\Model;
/**
 * 会员订单
 * 
 * @version 1.0.0
 */
class MemberOrder extends \app\model\order\Order
{
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
                $row = $row->toArray();
                $branch = $branch->toArray();
                $row = array_merge($row, $branch);
            }
        }

        return compact('count', 'list', 'sql');
    }
}