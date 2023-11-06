<?php
namespace app\model\system;
/*
 * 用户工作组
 * @package app.common.model
 * @Author: k.
 * @Date: 2021-05-10 20:19:31
 */
use app\Model;

class UserGroup extends Model
{
    protected $name = 'sys_group';
    protected $pk = 'group_id';

    /**
     * 组应用角色列表
     * @collection relation.model
     */
    public function roles() {
        return $this->hasMany(UserGroupRole::class, 'group_id', 'group_id');
    }

    /**
     * 获取当前用户所属用户组相应的权限
     * @param int group_id
     * @return Array
     */
    public function getRules($group_id)
    {
        if (isSuperUser()) {
            return app()->make(Rule::class)->getRules();
        }

        $group = self::with(['access.role' => function($query) {
            $query->with(['rules']);
        }])->where('group_id', $group_id)->find();

        $access = $group->access->toArray();
        $ruleIds = array_values(array_column($access, 'rule_id'));
        $rules = [];

        foreach(Rule::getRules() as $rule)
            if(in_array($rule['rule_id'], $ruleIds))
                $rules[] = $rule;

        return $rules;
    }

    /**
     * 返回列表
     *
     * @return void
     */
    public function getList(int $page = 1, int $limit = 20, $filter = [])
    {
        $condition = [];
        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $condition[] = ['group|group_remark', 'LIKE', "%{$filter['keyword']}%"];
        }
        $query = $this->where($condition)->order('group_id DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return compact('count', 'list');
    }

    /**
     * 组负责范围列表
     * @return Array
     */
    protected function getRangeList()
    {
        $ranges = app()->make(UserRange::class)->field('group_range as `value`,range_name as `label`')->select()->toArray();

        return $ranges;
    }
}
