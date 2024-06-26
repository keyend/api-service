<?php
namespace app\addons\member\admin\model;
use app\Model;
/**
 * 会员账户
 * 
 * @version 1.0.0
 */
class MemberAccount extends \app\model\member\Account
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
        $query = $this->order('id DESC');
        $query->where($condition);
        $count = $query->field($field)->count();
        $list = $query->page($page, $limit)->select();

        return compact('count', 'list', 'sql');
    }
}