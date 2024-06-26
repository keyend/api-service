<?php
namespace app\addons\agent\admin\model;
use app\Model;
use app\model\member\Member;

class Agent extends Model
{
    protected $name = 'agent';
    protected $pk = 'agent_id';

    /**
     * 关联会员
     *
     * @return void
     */
    public function memberInfo()
    {
        return $this->hasOne(Member::class, 'member_id', 'member_id');
    }

    /**
     * 关联代理等级
     *
     * @return void
     */
    public function level()
    {
        return $this->hasOne(AgentLevel::class, 'level_id', 'level_id');
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
        $query = self::with(['memberInfo', 'level'])->order("{$this->pk} DESC");
        $query->where($condition);
        $count = $query->field($field)->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return compact('count', 'list', 'sql');
    }

    /**
     * 创建代理商
     *
     * @param array $data
     * @return void
     */
    public function addAgent($data = [])
    {
        $member_id = $data['member_id'];
        $memberInfo = Member::find($member_id);
        if (empty($memberInfo)) {
            throw new \Exception("会员信息不存在");
        }

        $level_id = $data['level_id'];
        $level = AgentLevel::where([['level', '=', $level_id]])->find();
        if (empty($level)) {
            throw new \Exception("代理等级不存在");
        }

        return self::create([
            'member_id' => $memberInfo['member_id'],
            'agent_name' => $memberInfo['username'],
            'level_id' => $level['level'],
            'parent_id' => $memberInfo['parent_id'],
            'status' => 1,
            'create_time' => time()
        ]);
    }
}