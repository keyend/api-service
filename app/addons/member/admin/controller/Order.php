<?php
namespace app\addons\member\admin\controller;
/**
 * 会员订单管理
 * 
 * @version 1.0.0
 */
use app\controller\admin\Controller;
use app\addons\member\admin\model\MemberOrder;

class Order extends Controller
{
    /**
     * 会员订单列表
     *
     * @param MemberOrder $order_model
     * @return void
     */
    public function lists(MemberOrder $order_model)
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->params, [ ['keyword', ''] ]);
            $condition = [];
            $condition[] = ['member_id', '=', $this->params['member_id'] ?? 0];
            if (!empty($filter['keyword'])) {
                $condition[] = [ 'nickname|mobile|order_id|out_trade_no|trade_no|refund_no', 'like', "%{$filter['keyword']}%" ];
            }
            [$page, $limit] = $this->getPaginator();
            $data = $order_model->getList($page, $limit, $condition);
            return $this->success($data);
        } else {
            return $this->fetch('member/order_list');
        }
    }
}