<?php
namespace app\model\system;
/**
 * 日志
 *
 * @package app.common.model
 * @author: k.
 * @date: 2021-05-10 20:19:31
 */
use app\Model;

class Logs extends Model
{
    protected $name = 'sys_logs';
    protected $pk = 'id';

    /**
     * 对应的日志模板
     * @collection relation.model
     */
    public function template()
    {
        return $this->hasOne(LogsTemplate::class, 'name', 'content');
    }

    /**
     * 对应的日志操作员
     * @collection relation.model
     */
    public function user() 
    {
        return $this->hasOne(User::class, 'user_id', 'user_id');
    }

    /**
     * 添加操作日志
     * @param string type 动作类型
     * @param Array  params 参数
     * @param string message 事件内容
     * @access public
     */
    public function info($message, ...$args)
    {
        foreach($args as $arg) {
            if(gettype($arg) === 'number' || gettype($arg) === 'int' || (is_string($arg) && is_numeric($arg))) {
                $user_id = $arg;
            } elseif (is_string($arg)) {
                $type = $arg;
            } elseif(is_array($arg)) {
                $params = $arg;
            } elseif($arg instanceof \think\Model) {
                $params = $arg->toArray();
            }
        }

        if (!isset($params)) $params = [];
        if (!isset($type)) $type = 'DOSOME';

        if (defined('S2') && !isset($params['username'])) {
            $params['username'] = S2;
            $params['realname'] = S7;
            $params['ip'] = SA;
        }

        if (defined('SA') && !isset($params['ip'])) {
            $params['ip'] = SA;
        }

        if (isset($params['lastlogin_ip'])) {
            $ip = $params['lastlogin_ip'];
        } elseif(defined('SA')) {
            $ip = SA;
        } else {
            $ip = request()->ip();
        }

        if (!isset($user_id)) {
            if (defined('S1'))
                $user_id = S1;
            elseif(isset($params['user_id']))
                $user_id = $params['user_id'];
            else
                $user_id = 0;
        }

        parent::create([
            'label' => $type,
            'ip' => $ip,
            'user_id' => $user_id,
            'content' => $message,
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'create_time' => time()
        ]);
    }

    /**
     * 日志专内容解析
     *
     * @param array $params
     * @return void
     */
    public function parse($content, $params = [], $prefix = "")
    {
        $prefix .= $prefix!==""?".":"";
        foreach($params as $key => $value) {
            if (is_array($value)) {
                $content = $this->parse($content, $value, $prefix.$key);
            } elseif(is_string($value)) {
                $content = str_replace("{" . $prefix . $key . "}", $value, $content);
            } else {
                $content = str_replace("{" . $prefix . $key . "}", (string)$value, $content);
            }
        }
        return $content;
    }

    /**
     * 获取事件类型
     * @param String label  类型标识
     * @return String
     */
    protected function getLabelType($label)
    {
        return lang("logs.type." . strtolower($label));
    }

    /**
     * 获取器
     *
     * @param [type] $value
     * @param [type] $data
     * @return void
     */
    public function getParamsAttr($value, $data)
    {
        return json_decode($value, true);
    }

    /**
     * 返回列表
     * @param int page
     * @param int limit 页码
     * @param Array 筛选条件
     * @return array
     */
    public function getList(int $page, int $limit, Array $filter = [])
    {
        $query = self::withJoin(["template", "user"], "left");
        if ($filter['date'] !== '') {
            $query->where('create_time', 'between', getTime($filter['date']));
        }
        if (isset($filter['keyword']) && $filter['keyword'] !== '') {
            $query->where('logs.params|template.title', 'like', '%' . $filter['keywords'] . '%');
        }
        if (isset($filter['labels']) && !empty($filter['labels'])) {
            $query->where('label', 'IN', $filter['labels']);
        }
        $fields = implode(",", [
            "logs.label",
            "logs.user_id",
            "logs.params",
            "logs.create_time",
            "user.username",
            "template.title",
            "logs.content as name",
            "template.content as template"
        ]);
        $result = $this->maps(function($query, $page, $limit) {
            $cursor = $query->order("logs.id DESC")->cursor();
            $sql = $query->getLastSql();
            $list = [];
            foreach($cursor as $row) {
                $list[] = $this->mapsItem(function($row, $item) {
                    $row["content"] = !empty($row["template"]) ? $this->parse($row["template"], $item->params) : "[NO_TEMPLATE]";
                    $row["event"] = $this->getLabelType($item->label);
                    $row["name"] = $item->getAttr("name");
                    // unset($row["params"], $row["template"]);
                    return $row;
                }, $row);
            }
            return compact('list', 'sql', 'discount_money', 'pay_money');
        }, [
            "query"  => $query,
            "filter" => $filter,
            "fields" => $fields,
            "page"   => $page,
            "limit"  => $limit,
            "headers"=> [
                ["title" => "事件", "field" => "title", "width" => 24],
                ["title" => "操作", "field" => "event", "width" => 16],
                ["title" => "操作员", "field" => "username", "width" => 16],
                ["title" => "时间", "field" => "mobile", "width" => 18],
                ["title" => "事件明细", "field" => "content", "width" => 96]
            ],
            'title' => '日志明细_' . ($filter['keyword'] ?? ""),
        ]);

        return $result;
    }

    /**
     * 清空记录
     *
     * @return void
     */
    public function clear() 
    {
        \think\facade\Db::query("TRUNCATE TABLE {$this->getTable()}");
    }
}