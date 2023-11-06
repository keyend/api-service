<?php
namespace app\model\system;
/**
 * 日志模板
 *
 * @package app.common.model
 * @author: k.
 * @date: 2021-05-10 20:19:31
 */
use app\Model;

class LogsTemplate extends Model
{
    protected $name = 'sys_logs_template';
    protected $pk = 'id';

    /**
     * 对应的日志记录
     * @collection relation.model
     */
    public function logs()
    {
        return $this->hasMany(Logs::class, 'content', 'name')->order("id ASC");
    }

    /**
     * 列表
     *
     * @param int page
     * @param int limit 页码
     * @param array 筛选条件
     * @return array
     */
    public function getList($page, $limit, $filter = [])
    {
        $query = $this->withJoin(["logs"], "left");
        if (isset($filter['keyword']) && !empty($filter['keyword']) ) {
            $query->where("title|name", 'LIKE', "%{$filter['keyword']}%");
        }

        $result = $this->maps(function($query, $page, $limit) {
            $cursor = $query->order("id DESC")->cursor();
            $sql = $query->getLastSql();
            $list = [];
            foreach($cursor as $row) {
                $list[] = $this->mapsItem(function($row, $item) {
                    return $row;
                }, $row);
            }
            return compact('list', 'sql');
        }, [
            "query"  => $query,
            "filter" => $filter,
            "page"   => $page,
            "limit"  => $limit
        ]);

        return $result;
    }
}