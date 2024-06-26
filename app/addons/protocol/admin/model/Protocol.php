<?php
namespace app\addons\protocol\admin\model;
use app\model\protocol\Protocol as ProtocolModel;

class Protocol extends ProtocolModel
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
        $list = $query->page($page, $limit)->select()->toArray();

        return compact('count', 'list', 'sql');
    }

    /**
     * 添加接口
     *
     * @param array $data
     * @return void
     */
    public function addProtocol($data = [])
    {
        if ($this->where('uri', '=', $data['uri'])->field('id')->find()) {
            throw new \Exception("接口记录已存在!");
        }
        $data['param'] = json_encode($data['param']);
        return self::create($data);
    }
}