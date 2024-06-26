<?php
namespace app\addons\member\admin\controller;
/**
 * 会员账户管理
 * 
 * @version 1.0.0
 */
use app\controller\admin\Controller;
use app\addons\member\admin\model\MemberAccount;

class Account extends Controller
{
    /**
     * 会员账户明细列表
     *
     * @param MemberAccount $account_model
     * @return void
     */
    public function lists(MemberAccount $account_model)
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->params, [ ['keyword', ''] ]);
            $condition = [];
            $condition[] = ['member_id', '=', $this->params['member_id'] ?? 0];
            if (!empty($filter['keyword'])) {
                $condition[] = [ 'username|mobile|nickname|address|account_title|account_name', 'like', "%{$filter['keyword']}%" ];
            }
            [$page, $limit] = $this->getPaginator();
            $data = $account_model->getList($page, $limit, $condition);
            return $this->success($data);
        } else {
            return $this->fetch('member/account_list');
        }
    }
}