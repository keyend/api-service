<?php
namespace app\model\system;
/**
 * 区域
 *
 * @package app.common.model
 * @date: 2021-05-10 20:19:31
 */
use app\Model;

class Area extends Model
{
    /**
     * 表名
     *
     * @var string
     */
    protected $name = 'sys_area';

    /**
     * 返回地区列表
     *
     * @param integer $page
     * @param integer $limit
     * @param array $condition
     * @param string $field
     * @return void
     */
    public function getList(int $page, int $limit, $condition = [], $field = '*')
    {
        $list = $this->where($condition)
        ->field($field)
        ->page($page, $limit)
        ->order('sort ASC')
        ->column($field, "id");
        $sql = $this->getLastSql();
        $count = count($list);
        return compact('count', 'list', 'sql');
    }
}