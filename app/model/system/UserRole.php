<?php
namespace app\model\system;
/*
 * 用户角色
 * @package app.common.model
 * @Author: k.
 * @Date: 2021-05-10 20:19:31
 */
use app\Model;

class UserRole extends Model
{
    protected $name = 'sys_role';
    protected $pk = 'role_id';

    /**
     * 对应工作组列表
     * @collection relation.model
     */
    public function groups() {
        return $this->hasMany(UserGroupRole::class, 'role_id', 'role_id')->field('group_id,role_id');
    }

    /**
     * 应用权限列表
     * @collection relation.model
     */
    public function rules() {
        return $this->hasManyThrough(Rule::class, UserRule::class, 'role_id', 'rule_id', 'role_id', 'rule_id');
    }

    /**
     * 分配的权限列表
     * @collection relation.model
     */
    public function access()
    {
        return $this->hasMany(UserRule::class, 'role_id', 'role_id');
    }

    /**
     * 返回角色列表
     * @return array
     */
    public function getList(int $page = 1, int $limit = 20, $filter = [])
    {
        $condition = [];
        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $condition[] = ['role', 'LIKE', "%{$filter['keyword']}%"];
        }
        if (isset($filter['internal'])) {
            $condition[] = ['internal', '=', (int)$filter['internal']];
        }
        $query = $this->where($condition)->order('role_id DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return compact('count', 'list');
    }

    /**
     * 返回权限树
     *
     * @param integer $id
     * @return void
     */
    public function getRole($id = 0)
    {
        /**
         * 树形默认所拥有的权限列表
         * 从父ID取出组权限
         */
        $user = app()->make(User::class)->find(S3);
        $rules = array_values(array_column($user->getAccess(), 'name'));
        if ($id !== 0) {
            $data = $this->with(['access'])->find($id);
            if (!$data) {
                throw new ResponseException('角色不存在');
            }
            $data = $data->toArray();
            if ($data['internal'] === 1 && $data['role'] === 'EVERYONE') {
                $data['roles'] = explode(",", Rule::where('rule_id', '>', '0')->value('GROUP_CONCAT(`rule_id`)'));
                foreach($data['roles'] as &$role_id) {
                    $role_id = (int)$role_id;
                }
            } else {
                // 过滤自动加入的父级
                $data['roles'] = [];
                foreach($data['access'] as $i => $access) {
                    if ($access['tree_in'] != 1) {
                        unset($data['access'][$i]);
                    }
                }
                $data['roles'] = array_values(array_column($data['access'], 'rule_id'));
            }
        } else {
            $data = [];
            $data['roles'] = [];
        }

        $rules = Rule::where('rule_id', '>', '0')->when(!super(), function($query) use($rules) {
            // 非超管从主账户取出继承权限
            $query->where('rule_id', 'in', array_values(array_column($rules, 'rule_id')));
        })->field('`rule_id` as `id`,`title`,`parent_id`,`name`')->select()->toArray();
        // 转换成树形输出
        $roles = parseTree($rules, 'id', 'parent_id', 'children');
        // 取当前项目根
        foreach ($roles as $item) {
            if ($item['name'] === MODULE) {
                $roles = $item['children'];
                break;
            }
        }
        $data['rules'] = $roles;

        return $data;
    }
}
