<?php
namespace app\addons\stat\admin\model;
use app\Model;
use app\model\protocol\Protocol;
/**
 * 接口调用记录
 * 
 * @version 1.0.0
 */
class ProtocolLogs extends \app\model\protocol\Logs
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
        $list = $query->page($page, $limit)->select();

        return compact('count', 'list', 'sql');
    }

    /**
     * 返回接口列表
     *
     * @return void
     */
    public function getProtocolList()
    {
        return Protocol::where('status', 1)->field('id,protocol')->select();
    }

    /**
     * 返回明细
     *
     * @param integer $id
     * @return void
     */
    public function getDetail($id = 0)
    {
        $ret = self::find($id);

        return $ret;
    }
}