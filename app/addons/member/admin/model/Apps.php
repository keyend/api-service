<?php
namespace app\addons\member\admin\model;
use app\model\protocol\Apps as AppsModel;

class Apps extends AppsModel
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
     * 添加应用
     *
     * @param array $data
     * @return void
     */
    public function addApp($data = [])
    {
        if ($this->where([['app_name', '=', $data['app_name']], ['member_id', '=', $data['member_id']]])->field('id')->find()) {
            throw new \Exception("应用已存在!");
        }
        $data['app_key'] = md5(uniqid());
        $data['create_time'] = time();

        return self::create($data);
    }

    /**
     * 编辑应用
     *
     * @param array $data
     * @return void
     */
    public function editApp($data = [])
    {
        $this->save($data);

        return $this;
    }
}