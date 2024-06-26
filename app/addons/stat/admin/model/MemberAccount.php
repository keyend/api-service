<?php
namespace app\addons\stat\admin\model;
use app\Model;
/**
 * 会员账户
 * 
 * @version 1.0.0
 */
class MemberAccount extends \app\model\member\Account
{
    /**
     * 财务类型
     *
     * @var array
     */
    protected $account_type_list = [
        [ 'value' => 'direct', 'title' => '系统赠送' ],
        [ 'value' => 'balance_money', 'title' => '账户余额' ],
    ];

    /**
     * 返回财务类型列表
     *
     * @return void
     */
    public function getAccountTypeList()
    {
        return $this->account_type_list;
    }

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
        $query = $this->order('id DESC');
        $query->where($condition);
        $count = $query->field($field)->count();
        $list = $query->page($page, $limit)->select();

        return compact('count', 'list', 'sql');
    }
}