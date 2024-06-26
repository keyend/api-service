<?php
namespace app\addons\member\admin\model;
use app\Model;
/**
 * 会员标签
 * 
 * @version 1.0.0
 */
class MemberLabel extends Model
{
    protected $name = 'member_label';

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
        $list = $query->page($page, $limit)->select()->toArray();

        return compact('count', 'list', 'sql');
    }

    /**
     * 添加标签
     *
     * @param array $data
     * @return void
     */
    public function addLabel($data = [])
    {
        if ($this->where('labelname', '=', $data['labelname'])->field('id')->find()) {
            throw new \Exception("标签记录已存在!");
        }
        $data['id'] = $this->insertGetId($data);
        return $data;
    }

    /**
     * 编辑标签
     *
     * @param [type] $data
     * @return void
     */
    public function editLabel($data) 
    {
        if ($this->where([ ['labelname', '=', $data['labelname']], ['id', '<>', $this->getAttr('id')] ])->field('id')->find()) {
            throw new \Exception("标签记录已存在!");
        }

        return $this->save($data);
    }
}