<?php
namespace app\model\system;
/*
 * 用户角色权限
 * @package app.common.model
 * @Author: k.
 * @Date: 2021-05-10 20:19:31
 */
use app\Model;

class UserRule extends Model
{
    protected $name = 'sys_role_rule';
    protected $pk = 'role_rule_id';

    /**
     * 角色权限明细
     * @collection relation.model
     */
    public function rule() {
        return $this->hasOne(Rule::class, 'rule_id', 'rule_id');
    }

    /**
     * 角色
     * @collection relation.model
     */
    public function role() {
        return $this->hasOne(UserRole::class, 'role_id', 'role_id');
    }

    /**
     * 向上遍历父级
     * @return Array
     */
    protected function eachPRI($ids)
    {
        $rules = app()->make(Rule::class)->where('rule_id', 'in', $ids)->where('parent_id', '<>', 0)->field('parent_id')->distinct(true)->select()->toArray();
        $rules = array_values(array_column($rules, 'parent_id'));

        if ($rules) {
            $parent_rules = $this->eachPRI($rules);
            if ($parent_rules) {
                $rules = array_merge($rules, $parent_rules);
            }
        }

        return $rules;
    }

    /**
     * 指量插入权限
     * @access public
     */
    public function insertAll(array $dataSet = [], int $limit = 0): int
    {
        $rule_ids = array_values(array_column($dataSet, 'rule_id'));
        $parent_rules = $this->eachPRI($rule_ids);
        if ($dataSet) {
            $role_id = $dataSet[0]['role_id'];
            foreach($parent_rules as $rule_id) 
                $dataSet[] = [
                    'rule_id' => $rule_id,
                    'role_id' => $role_id,
                    'tree_in' => 0
                ];
        }

        return parent::insertAll($dataSet);
    }
}
