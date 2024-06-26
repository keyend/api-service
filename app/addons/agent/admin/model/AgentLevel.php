<?php
namespace app\addons\agent\admin\model;
use app\Model;

class AgentLevel extends Model
{
    protected $name = 'agent_level';
    protected $pk = 'level_id';

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
        $query = $this->order("{$this->pk} DESC");
        $query->where($condition);
        $count = $query->field($field)->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return compact('count', 'list', 'sql');
    }

    /**
     * 添加级别
     *
     * @param array $data
     * @return void
     */
    public function addLevel($data = [])
    {
        if ($this->where('level', '=', $data['level'])->field($this->pk)->find()) {
            throw new \Exception("等级记录已存在!");
        }
        $data['id'] = $this->insertGetId($data);
        return $data;
    }

    /**
     * 编辑等级
     *
     * @param array $data
     * @return void
     */
    public function editLabel($data) 
    {
        if ($this->where([ ['level', '=', $data['level']], ['level_id', '<>', $this->level_id] ])->field('level_id')->find()) {
            throw new \Exception("等级记录已存在!");
        }

        return $this->save($data);
    }

    /**
     * 级别列表
     *
     * @param array $condition
     * @param [type] $field
     * @return void
     */
    public function getLevelList($condition = [], $field = null, $sort = 'level ASC')
    {
        $condition[] = ['status', '=', 1];
        return self::where($condition)->field($field)->order($sort)->select();
    }
}