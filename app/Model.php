<?php
namespace app;
// 应用模型对象类
class Model extends \think\Model
{
    /**
     * 分块输出大小
     *
     * @var integer
     */
    private $chunk = 100;

    /**
     * 输出明细
     *
     * @var array
     */
    private $export = null;

    /**
     * 导出对象
     *
     * @var phpExcel
     */
    private $output = null;

    /**
     * 缓冲数量
     *
     * @var array
     */
    private $_buffer = [];

    /**
     * 记录日志
     *
     * @param mixed $args
     * @return void
     */
    protected function logger(...$args)
    {
        $class = app()->make(\app\model\system\LogsModel::class);
        $params = [];
        $arguments = [];
        foreach($args as $i => $val) {
            if ($i > 1) {
                $params[] = $val;
            } else {
                $arguments[] = $val;
            }
        }
        $arguments[] = $params;
        call_user_func_array([$class, 'info'], $args);
    }

    /**
     * 返回统计行数
     *
     * @param [type] $query
     * @return void
     */
    private function getCount($query)
    {
        return (clone $query)->count();
    }

    /**
     * 聚合
     *
     * @param [type] $query
     * @param [type] $field
     * @return void
     */
    protected function getSum($query, $field)
    {
        return (clone $query)->sum($field);
    }

    /**
     * 记录输出
     *
     * @param callable $callable
     * @param array $args
     * @return void
     */
    protected function maps($callable, $argv = [])
    {
        $export = false;
        extract($argv);
        if (!isset($query)) throw new \Exception(get_called_class() . "::" . __FUNCTION__ . "() 参数错误");
        if (!isset($headers)) $headers = null;
        if (!isset($title)) $title = date("Y_m_d");
        if (!isset($fields)) $fields = "";
        if (!isset($page)) $page = 1;
        if (!isset($limit)) $limit = 10;
        if (!isset($filter)) $filter = [];
        if (isset($filter['export']) && $filter['export'] == 1) {
            $this->output = new \mashroom\Excel();
            $this->export = [
                "title" => $title,
                "headers" => $headers
            ];
            $export = true;
        } elseif (isset($filter['print']) && $filter['print'] == 1) {
            $count = $this->getCount($query);
            if (!empty($fields)) {
                $query->field($fields);
            }
        } else {
            $count = $this->getCount($query);
            $query->page($page, $limit);
            if (!empty($fields)) {
                $query->field($fields);
            }
        }

        $result = call_user_func_array($callable, [$query, $page, $limit]);
        if ($export) {
            if (!empty($this->_buffer))
                $this->output->excel($this->_buffer, $this->export);
            $this->output->excel(null, $this->export);
        }
        $result["count"] = $count;

        return $result;
    }

    /**
     * 计数器输出
     *
     * @param callable $callable
     * @param array $row
     * @return void
     */
    protected function mapsItem($callable, $item = [])
    {
        static $stage = 0;
        static $isExport = null;
        static $fields = null;
        static $cells = [];

        if ($isExport === null) {
            $isExport = !is_null($this->export);
            $fields = array_values(array_column($this->export["headers"], "field"));
            foreach($item->toArray() as $key => $value) {
                if (strpos($key, "__") === FALSE) {
                    $cells[] = $key;
                }
            }
        }

        $row = array_keys_filter($item, $cells);
        if (true === $isExport) {
            $stage ++;
            $row = call_user_func_array($callable, [$row, $item]);
            $this->_buffer[] = array_keys_filter($row, $fields);
            if ($stage > $this->chunk) {
                $this->output->excel($this->_buffer, $this->export);
                $this->_buffer = [];
                $stage = 0;
            }
        } else {
            $row = call_user_func_array($callable, [$row, $item]);
        }

        return $row;
    }
}
