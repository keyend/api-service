<?php
namespace app\model\system;
/*
 * 用户权限
 * @package app.common.model
 * @Author: k.
 * @Date: 2021-05-10 20:19:31
 */
use app\Model;

class Rule extends Model
{
    protected $name = 'sys_rule';
    protected $pk = 'rule_id';

    /**
     * 权限属性
     * @collection relation.model
     */
    public function extends() {
        return $this->hasMany(RuleExtend::class, 'rule_id', 'rule_id')->field('rule_id,attr,value');
    }

    /**
     * 返回缓存的权限列表
     * @param Boolean force 是否强制更新
     * @return Array
     */
    public function getRules($force = false)
    {
        $rules = redis("sys.rules");
        if (!$rules || $force) {
            $lst = self::field('rule_id,parent_id,name')->select();
            if (!$lst) {
                $rules = [];
            } else {
                $rules = $lst->toArray();
            }

            redis("sys.rules", $rules, 604800);
        }

        return $rules;
    }

    public function getAllow($root = '')
    {
        $list = self::with(['extends'])->field('rule_id,name,parent_id')->order('sort DESC')->select()->toArray();
        $tree = parseTree(array_column_bind($list, 'extends', 'attr', 'value'), 'rule_id');
        $root = explode('.', $root);

        foreach($root as $node) {
            foreach($tree as $item) {
                if ($item['name'] == $node) {
                    $tree = $item['children'];
                    break;
                }
            }
        }

        return extractTree($tree, 'rule_id');
    }

    /**
     * 获取权限
     *
     * @param array $condition
     * @return void
     */
    public function getRule($condition = [])
    {
        return self::where($condition)->find();
    }

    /**
     * 权限缓存
     * @return Array
     */
    public function getCache($force = false)
    {
        static $data = null;
        if (is_null($data)) {
            $data = redis()->get('sys.rule');
            if (!$data || $force === true) {
                $data = self::column('rule_id', 'name');
                redis()->set('sys.rule', $data, 1306800);
            }
        }

        return $data;
    }

    /**
     * 获取权限ID
     * @return Integer
     */
    public function getId($name)
    {
        $list = $this->getCache();

        if (isset($list[$name])) {
            return $list[$name];
        }

        return 0;
    }

    /**
     * 权限验证
     *
     * @param 权限名 $name
     * @param 用户信息 $user
     * @return void
     */
    public function check($name, $user)
    {
        if (super()) return true;
        return in_array($this->getId($name), $user['access']);
    }

    /**
     * 获取默认根ID
     * @param String module 当前所在模块
     * @return Int
     */
    public function getParentId($module = MODULE)
    {
        $id = self::where('name', $module)->value('rule_id');
        return $id;
    }

    /**
     * 返回树形结构
     * @param int page  当前所在页面
     * @param int limie 页码大小
     * @param String module 当前所在模块
     * @return JSON
     */
    public function getTreeList(int $page, int $limit, $condition = [], $field = '*')
    {
        $data = $this->getList($page, $limit, $condition, $field);
        $list = parseTree($data["list"], 'id', 'parent_id', 'children');
        $count = $data["count"];
        return compact('count', 'list');
    }

    /**
     * 返回权限列表
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
        ->order('sort DESC')
        ->column($field, "rule_id");
        $count = count($list);
        return compact('count', 'list');
    }
}
