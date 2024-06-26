<?php
namespace app\addons\stat\admin\controller;
use app\controller\admin\Controller;
use app\addons\stat\admin\model\Order;
use app\addons\stat\admin\model\MemberAccount;
use app\addons\stat\admin\model\ProtocolLogs;
/**
 * 数据管理
 * @version 1.0.0
 */
class Stat extends Controller
{
    /**
     * 订单管理
     *
     * @param Order $order_model
     * @return void
     */
    public function order_list(Order $order_model) 
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->params, [
                ['order_type', ''],
                ['order_status', ''],
                ['pay_type', ''],
                ['create_time', ''],
                ['keyword', '']
            ]);
            $condition = [];
            if (!empty($filter['keyword'])) {
                $condition[] = [ 'nickname|mobile|order_id|out_trade_no|trade_no|refund_no', 'like', "%{$filter['keyword']}%" ];
            }
            if (!empty($filter['create_time'])) {
                range_date($filter['create_time'], function($date) use(&$condition) {
                    if (!empty($date[0]) && !empty($date[1])) {
                        $condition[] = [ 'create_time', 'BETWEEN', $date ];
                    } elseif(!empty($date[0])) {
                        $condition[] = [ 'create_time', '>' , $date[0] ];
                    } elseif(!empty($date[1])) {
                        $condition[] = [ 'create_time', '<' , $date[1] ];
                    }
                });
            }
            if (!empty($filter['order_type'])) {
                $condition[] = ['order_type', '=', $filter['order_type']];
            }
            if (!empty($filter['pay_type'])) {
                $condition[] = ['pay_type', '=', $filter['pay_type']];
            }
            if ($filter['order_status'] !== '') {
                $condition[] = ['order_status', '=', (int)$filter['order_status']];
            }
            [$page, $limit] = $this->getPaginator();
            $data = $order_model->getList($page, $limit, $condition);
            return $this->success($data);
        } else {
            $order_type_list = $order_model->getOrderTypeList();
            $order_status_list = $order_model->getOrderStatusList();
            $pay_type_list = $order_model->getPayTypeList();
            $this->assign('order_type_list', $order_type_list);
            $this->assign('order_status_list', $order_status_list);
            $this->assign('pay_type_list', $pay_type_list);
            return $this->fetch();
        }
    }

    /**
     * 订单详情
     *
     * @param Order $order_model
     * @return void
     */
    public function order_detail(Order $order_model)
    {
        $order_id = input('order_id');
        $order_detail = $order_model->getDetail($order_id);
        $this->assign('item', $order_detail);

        return $this->fetch();
    }

    /**
     * 订单更新
     *
     * @param Order $order_model
     * @return void
     */
    public function order_update(Order $order_model)
    {
        if ($this->request->isAjax()) {
            $order_id = (int)$this->params['order_id'] ?? 0;
            $item = $order_model->where("order_id", $order_id)->find();
            if ($item) {
                $data = [];
                $data[$this->params['name']] = $this->params['value'];
                $data = array_keys_filter($data, [ 
                    'remark'
                ]);
    
                if (!empty($data)) {
                    $item->save($data);
                }
            }

            return $this->success();
        }
    }

    /**
     * 账目列表
     *
     * @param MemberAccount $account_list
     * @return void
     */
    public function account_list(MemberAccount $account_list)
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->params, [
                ['create_time', ''],
                ['keyword', '']
            ]);
            $condition = [];
            if (!empty($filter['keyword'])) {
                $condition[] = [ 'member_id|nickname|mobile|address|trade_no', 'like', "%{$filter['keyword']}%" ];
            }
            if (!empty($filter['create_time'])) {
                range_date($filter['create_time'], function($date) use(&$condition) {
                    if (!empty($date[0]) && !empty($date[1])) {
                        $condition[] = [ 'create_time', 'BETWEEN', $date ];
                    } elseif(!empty($date[0])) {
                        $condition[] = [ 'create_time', '>' , $date[0] ];
                    } elseif(!empty($date[1])) {
                        $condition[] = [ 'create_time', '<' , $date[1] ];
                    }
                });
            }
            [$page, $limit] = $this->getPaginator();
            $data = $account_list->getList($page, $limit, $condition);
            return $this->success($data);
        } else {
            $account_type_list = $account_list->getAccountTypeList();
            $this->assign('account_type_list', $account_type_list);
            return $this->fetch();
        }
    }

    /**
     * 接口调用记录
     *
     * @param ProtocolLogs $logs_model
     * @return void
     */
    public function protocol(ProtocolLogs $logs_model)
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->params, [
                ['create_time', ''],
                ['protocol_id', ''],
                ['keyword', '']
            ]);
            $condition = [];
            if (!empty($filter['keyword'])) {
                $condition[] = [ 'member_id|nickname|mobile|pathname', 'like', "%{$filter['keyword']}%" ];
            }
            if (!empty($filter['protocol_id'])) {
                $condition[] = [ 'protocol_id', '=', $filter['protocol_id'] ];
            }
            if (!empty($filter['create_time'])) {
                range_date($filter['create_time'], function($date) use(&$condition) {
                    if (!empty($date[0]) && !empty($date[1])) {
                        $condition[] = [ 'create_time', 'BETWEEN', $date ];
                    } elseif(!empty($date[0])) {
                        $condition[] = [ 'create_time', '>' , $date[0] ];
                    } elseif(!empty($date[1])) {
                        $condition[] = [ 'create_time', '<' , $date[1] ];
                    }
                });
            }
            [$page, $limit] = $this->getPaginator();
            $data = $logs_model->getList($page, $limit, $condition);
            return $this->success($data);
        } else {
            $protocol_list = $logs_model->getProtocolList();
            $this->assign('protocol_list', $protocol_list);
            return $this->fetch();
        }
    }

    /**
     * 调用明细
     *
     * @param ProtocolLogs $logs_model
     * @return void
     */
    public function protocol_detail(ProtocolLogs $logs_model)
    {
        $id = input('id');
        $item = $logs_model->getDetail($id);
        $this->assign('item', $item);

        return $this->fetch();
    }
}