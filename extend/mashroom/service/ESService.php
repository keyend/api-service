<?php
namespace mashroom\service;
/**
 * @package mashroom.service.EsService
 * @version 1.0.0
 */
use Elasticsearch\ClientBuilder;
use ArrayAccess;
use Closure;
use IteratorAggregate;
use ArrayIterator;
use Countable;
use JsonSerializable;

class ESService extends App
{
    /**
     * 设置主键
     *
     * @var string
     */
    protected $_pk = 'id';

    /**
     * 索引名称
     *
     * @var string
     */
    protected $name;

    /**
     * ClientBuilder
     *
     * @var stdClass
     */
    protected $client;

    /**
     * ESIndex
     *
     * @var stdClass
     */
    protected $indices;

    /**
     * 过滤条件
     *
     * @var array
     */
    private $filter = [];

    /**
     * 页码
     *
     * @var integer
     */
    private $size = 10;

    /**
     * 当前页位置
     *
     * @var integer
     */
    private $pageIndex = 1;

    /**
     * 起始位置
     *
     * @var integer
     */
    private $startLimit = 0;

    /**
     * 排序
     *
     * @var array
     */
    private $orders = [];

    /**
     * 返回字段
     *
     * @var array
     */
    private $fields = [];

    /**
     * 构造函数
     *
     * @param string $name
     * @param string $host
     */
    public function __construct($name, $host = 'http://127.0.0.1:9200')
    {
        $this->name = $name;
        $this->client = ClientBuilder::create()->setHosts([$host])->build();
        $this->indices = ESIndex::instance($this->client->indices());
        $this->index();
    }

    /**
     * 索引
     *
     * @param array $data
     * @return mixed
     */
    public function index($data = [])
    {
        $result = $this->indices->getName($this->name);
        if (!$result) {
            $params = [
                'index' => $this->name,
                'body' => [
                    'mappings' => [
                        $this->name => [
                            'properties' => $data
                        ]
                    ]
                ]
            ];

            $result = $this->indices->create($params);
        }

        return $result;
    }

    /**
     * 添加条件
     *
     * @param string $at
     * @param array $pattern
     * @return void
     */
    private function addCondition($at, $pattern)
    {
        if (!isset($this->filter[$at])) {
            $this->filter[$at] = [];
        }

        $this->filter[$at] = $pattern;
    }

    /**
     * 返回表达式解析
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    private function parseCondition($key, $value)
    {
        $keys = explode('|', $key);
        $length = count($keys);
        if ($length > 1) {
            $values = [];
            foreach($keys as $key) {
                $values[] = ["match" => [$key => $value] ];
            }
            return [ "bool" => [ "should" => $values ] ];
        }

        return [ "term" => [ $key => $value ] ];
    }

    /**
     * 查询条件
     * ->where([
     *  ['a', '=', 'b'],
     *  ['b','LIKE','c']
     * ])->where(['a', 'LIKE', 'b'])
     * @param array $filters
     * @return ESService
     */
    public function where($filters = [], $exp = 'AND')
    {
        $isArray = false;
        $at = $exp == 'AND' ? 'must' : 'should';
        $condition = [];

        foreach($filters as $key => $value) {
            if (is_array($value)) {
                if ($isArray === false) {
                    $isArray = true;
                }

                if (!isset($condition[$at])) {
                    $condition[$at] = [];
                }

                if (in_array($value[1], ['=','==','EQ'])) {
                    $condition[$at][] = $this->parseCondition($value[0], $value[2]);
                } elseif (in_array($value[1], ['!=','<>','NEQ'])) {
                    $condition["must_not"][] = $this->parseCondition($value[0], $value[2]);
                } elseif ($value[1] == 'LIKE') {
                    $condition[$at][] = $this->parseCondition($value[0], $value[2]);
                } elseif (in_array($value[1], ['<','lt'])) {
                    $condition[$at][] = [ "range" => [ $value[0] => [ "lt" => $value[2] ] ] ];
                } elseif(in_array($value[1], ['>','gt'])) {
                    $condition[$at][] = [ "range" => [ $value[0] => [ "gt" => $value[2] ] ] ];
                } elseif(in_array($value[1], ['>=','gte'])) {
                    $condition[$at][] = [ "range" => [ $value[0] => [ "gte" => $value[2] ] ] ];
                } elseif(in_array($filters[1], ['<=','lte'])) {
                    $condition[$at][] = [ "range" => [ $value[0] => [ "lte" => $value[2] ] ] ];
                }
            }
        }

        if (!$isArray) {
            if (in_array($filters[1], ['=','==','EQ'])) {
                $this->addCondition($at, $this->parseCondition($filters[0], $filters[2]));
            } elseif(in_array($filters[1], ['!=','<>','NEQ'])) {
                $this->addCondition("must_not", $this->parseCondition($filters[0], $filters[2]));
            } elseif($filters[1] == 'LIKE') {
                $this->addCondition($at, $this->parseCondition($filters[0], $filters[2]), true);
            } elseif(in_array($filters[1], ['<','lt'])) {
                $this->addCondition($at, [ "range" => [ $filters[0] => [ "lt" => $filters[2] ] ] ]);
            } elseif(in_array($filters[1], ['>','gt'])) {
                $this->addCondition($at, [ "range" => [ $filters[0] => [ "gt" => $filters[2] ] ] ]);
            } elseif(in_array($filters[1], ['>=','gte'])) {
                $this->addCondition($at, [ "range" => [ $filters[0] => [ "gte" => $filters[2] ] ] ]);
            } elseif(in_array($filters[1], ['<=','lte'])) {
                $this->addCondition($at, [ "range" => [ $filters[0] => [ "lte" => $filters[2] ] ] ]);
            }
        } else {
            $this->addCondition("must", [ "bool" => $condition]);
        }

        return $this;
    }

    /**
     * 设置页码
     *
     * @param integer $size
     * @return void
     */
    public function page($index = 1, $size = 9999)
    {
        $this->pageIndex = $index;
        $this->size = $size;
        $this->startLimit = ($index - 1) * $size;
        return $this;
    }

    /**
     * 分页显示
     *
     * @param integer $startLimit
     * @param integer $size
     * @return void
     */
    public function limit($startLimit, $size = 9999)
    {
        $this->startLimit = $startLimit;
        if ($this->size === 0 || $size !== 9999) {
            $this->size = $size;
        }
        $this->pageIndex = ceil($this->startLimit / $this->size);
        return $this;
    }

    /**
     * 排序
     *
     * @param string $ord
     * @return void
     */
    public function order($ord = '')
    {
        $ords = explode(',', $ord);
        foreach($ords as $ord) {
            $ord = explode(' ', $ord);
            $ord[1] = strtolower($ord[1]);
            if (!isset($this->orders[$ord[1]])) {
                $this->orders[$ord[1]] = [];
            }
            $this->orders[$ord[1]][] = $ord[0];
        }
        return $this;
    }

    /**
     * 返回的字段
     *
     * @param mixed $fields
     * @return void
     */
    public function field($fields)
    {
        if (!is_array($fields)) {
            $fields = explode(",", $fields);
        }

        $_fields = array_merge($this->fields, $fields);
        $keys = array_unique(array_keys($fields));
        $fields = [];
        foreach($keys as $key) {
            $fields[$key] = $_fields[$key];
        }
        $this->fields = $fields;
        return $this;
    }

    /**
     * 返回结果
     *
     * @return void
     */
    public function select()
    {
        $params = [
            'index' => $this->name,
            'from'  => $this->startLimit,
            'size'  => $this->size,
        ];

        if (!empty($this->fields)) {
            $params['_source11'] = $this->fields;
        }

        if (!empty($this->filter)) {
            $params['body'] = [
                'query' => ["bool" => $this->filter]
            ];

            if (!empty($this->orders)) {
                $params['body']['sort'] = [];
                foreach($this->orders as $dir => $fields) {
                    $dir = $dir == 'desc' ? 'desc' : 'asc';
                    foreach($fields as $field) {
                        $params['body']['sort'][] = [$field => $dir];
                    }
                }
            }
        }


        try {
            $result = $this->client->search($params);
        } catch(\Exception $e) {
            \think\facade\Log::error($e->getMessage());
            $result = [];
        }

        return ESList::instance([$this, $result]);
    }

    /**
     * 查询单条记录
     *
     * @param integer $ind
     * @return void
     */
    public function find($ind = 0)
    {
        if ($ind != 0) {
            $this->where([$this->_pk, '=', $ind]);
        }

        $result = $this->select();
        if (count($result) > 0) {
            return $result[0];
        }

        return null;
    }

    /**
     * 删除记录
     *
     * @return void
     */
    public function delete()
    {
        $result = $this->select();
        foreach($result as $row) {
            $row->delete();
        }
    }

    /**
     * 返回记录数
     *
     * @return void
     */
    public function count()
    {
        $result = $this->select();
        return $result->total;
    }

    /**
     * 插入记录
     *
     * @param array $item
     * @return ESCollection
     */
    public function insert($item = [])
    {
        if (isset($item[$this->_pk])) {
            $exist = $this->find($item[$this->_pk]);
            if (!empty($exist)) {
                return false;
            }
        }

        $ds = ESCollection::instance([$this]);
        if ($ds->save($item)) {
            return $ds;
        }

        return false;
    }

    /**
     * 插入多条记录
     *
     * @param array $items
     * @return void
     */
    public function insertAll($items = [])
    {
        foreach($items as $item) {
            $this->insert($item);
        }
    }

    /**
     * 插入记录并返回ID
     *
     * @param array $item
     * @return void
     */
    public function create($item = [])
    {
        $ind = $this->insert($item);
        if (false !== $ind) {
            return $ind[$this->_pk];
        }

        return false;
    }
}

class ESIndex extends Service
{
    /**
     * IndicesNamespace
     *
     * @var stdClass
     */
    private $indices;

    /**
     * 索引明细
     *
     * @var string
     */
    private $index;

    public function __construct($indices)
    {
        $this->indices = $indices;
    }

    /**
     * 魔术访问
     *
     * @param string $name
     * @param array $arguments
     * @return void
     */
    public function __call($name, $arguments = [])
    {
        return call_user_func_array($this->indices, $arguments);
    }

    /**
     * 创建索引
     *
     * @param array $data
     * @return void
     */
    public function create($data)
    {
        $response = $this->indices->create($data);
        $response->getStatusCode();
        if ($create['result'] != 'created') {
            throw new \Exception("创建索引失败");
        }
        return $this;
    }

    /**
     * 返回索引
     *
     * @param string $name
     * @return void
     */
    public function getName($name)
    {
        if ($this->indices->exists(['index' => $name])) {
            $this->index = $name;
            return $this;
        }
    }
}

class ESList extends Service implements JsonSerializable, ArrayAccess, Countable, IteratorAggregate
{
    /**
     * DATA
     *
     * @var array
     */
    private $items = [];

    /**
     * 查询结果
     *
     * @var array
     */
    private $data = [];

    /**
     * 当前应用
     *
     * @var ESService
     */
    private $app;

    /**
     * 总记录数
     *
     * @var integer
     */
    private $total = 0;

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function count()
    {
        return count($this->items);
    }

    /**
     * 返回数据结果
     *
     * @return void
     */
    public function toArray()
    {
        $result = [];
        foreach($this->items as $i => $row) {
            if ($row instanceof ESCollection) {
                $result[$i] = $row->toArray();
            } elseif(is_array($row)) {
                $result[$i] = $row;
            } else {
                $result[$i] = (array)$row;
            }
        }

        return $result;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * 构造函数
     *
     * @param array $data
     * @param ESService $app
     * @param integer $size
     */
    public function __construct(ESService $app = null, $data = [])
    {
        $this->app = $app;
        $this->data = $data;
        if (!isset($data['hits'])) {
            return;
        }
        $ds = $data['hits'];
        $this->total = (int)$ds['total']['value'];
        if ($this->total > 0) {
            $this->items = [];
            foreach($ds['hits'] as $row) {
                $this->items[] = ESCollection::instance([$app, $row]);
            }
        }
    }


    public function offsetSet($name, $value)
    {
        $this->setAttr($name, $value);
    }

    public function offsetExists($name): bool
    {
        return $this->__isset($name);
    }

    public function offsetUnset($name)
    {
        $this->__unset($name);
    }

    public function offsetGet($name)
    {
        return $this->getAttr($name);
    }

    /**
     * 修改器 设置数据对象的值
     * @access public
     * @param string $name  名称
     * @param mixed  $value 值
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->setAttr($name, $value);
    }

    /**
     * 获取器 获取数据对象的值
     * @access public
     * @param string $name 名称
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->getAttr($name);
    }

    /**
     * 检测数据对象的值
     * @access public
     * @param string $name 名称
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return !is_null($this->getAttr($name));
    }

    /**
     * 销毁数据对象的值
     * @access public
     * @param string $name 名称
     * @return void
     */
    public function __unset(string $name): void
    {
        unset($this->data['_source'][$name]);
    }

    /**
     * 通过修改器 设置数据对象值
     * @access public
     * @param  string $name  属性名
     * @param  mixed  $value 属性值
     * @return void
     */
    public function setAttr(string $name, $value): void
    {
        if (!property_exists($this, $name)) {
            var_dump("###");
            die;
        }
        var_dump($name);
        die;
    }

    /**
     * 获取器 获取数据对象的值
     * @access public
     * @param  string $name 名称
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getAttr(string $name)
    {
        if (is_numeric($name)) {
            $name = (int)$name;
            if (isset($this->items[$name])) {
                return $this->items[$name];
            }
        }

        return $this->$name;
    }
}

class ESCollection extends Service implements JsonSerializable, ArrayAccess
{
    /**
     * 主键
     *
     * @var string
     */
    private $_pk = 'id';

    /**
     * 总记录数
     *
     * @var integer
     */
    private $total = 0;

    /**
     * ESService
     *
     * @var stdClass
     */
    private $app;

    /**
     * 查询结果
     *
     * @var array
     */
    private $data = [];

    /**
     * 当前记录是否存在
     *
     * @var boolean
     */
    private $exists = false;

    /**
     * 字段自动类型转换
     * @var array
     */
    protected $type = [];

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * 返回数据结果
     *
     * @return void
     */
    public function toArray()
    {
        return $this->getData();
    }

    /**
     * 构造函数
     *
     * @param array $data
     * @param ESService $app
     * @param integer $size
     */
    public function __construct(ESService $app = null, $data = [])
    {
        $this->app = $app;
        $this->data = $data;

        if (!empty($data)) {
            $this->exists = true;
        }
    }

    public function offsetSet($name, $value)
    {
        $this->setAttr($name, $value);
    }

    public function offsetExists($name): bool
    {
        return $this->__isset($name);
    }

    public function offsetUnset($name)
    {
        $this->__unset($name);
    }

    public function offsetGet($name)
    {
        return $this->getAttr($name);
    }

    /**
     * 修改器 设置数据对象的值
     * @access public
     * @param string $name  名称
     * @param mixed  $value 值
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->setAttr($name, $value);
    }

    /**
     * 获取器 获取数据对象的值
     * @access public
     * @param string $name 名称
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->getAttr($name);
    }

    /**
     * 检测数据对象的值
     * @access public
     * @param string $name 名称
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return !is_null($this->getAttr($name));
    }

    /**
     * 销毁数据对象的值
     * @access public
     * @param string $name 名称
     * @return void
     */
    public function __unset(string $name): void
    {
        unset($this->data['_source'][$name]);
    }

    /**
     * 获取当前对象数据 如果不存在指定字段返回false
     * @access public
     * @param  string $name 字段名 留空获取全部
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getData(string $name = null)
    {
        if (is_null($name)) {
            return $this->data['_source'];
        }

        if (isset($this->data['_source'][$name])) {
            return $this->data['_source'][$name];
        }

        throw new InvalidArgumentException('property not exists:' . static::class . '->' . $name);
    }

    /**
     * 获取器 获取数据对象的值
     * @access public
     * @param  string $name 名称
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getAttr(string $name)
    {
        return $this->getData($name);
    }

    /**
     * 数据写入 类型转换
     * @access protected
     * @param  mixed        $value 值
     * @param  string|array $type  要转换的类型
     * @return mixed
     */
    protected function writeTransform($value, $type)
    {
        if (is_null($value)) {
            return;
        }

        if ($value instanceof Raw) {
            return $value;
        }

        if (is_array($type)) {
            [$type, $param] = $type;
        } elseif (strpos($type, ':')) {
            [$type, $param] = explode(':', $type, 2);
        }

        switch ($type) {
            case 'integer':
                $value = (int) $value;
                break;
            case 'float':
                if (empty($param)) {
                    $value = (float) $value;
                } else {
                    $value = (float) number_format($value, (int) $param, '.', '');
                }
                break;
            case 'boolean':
                $value = (bool) $value;
                break;
            case 'timestamp':
                if (!is_numeric($value)) {
                    $value = strtotime($value);
                }
                break;
            case 'datetime':
                $value = is_numeric($value) ? $value : strtotime($value);
                $value = $this->formatDateTime('Y-m-d H:i:s.u', $value, true);
                break;
            case 'object':
                if (is_object($value)) {
                    $value = json_encode($value, JSON_FORCE_OBJECT);
                }
                break;
            case 'array':
                $value = (array) $value;
                // no break
            case 'json':
                $option = !empty($param) ? (int) $param : JSON_UNESCAPED_UNICODE;
                $value  = json_encode($value, $option);
                break;
            case 'serialize':
                $value = serialize($value);
                break;
            default:
                if (is_object($value) && false !== strpos($type, '\\') && method_exists($value, '__toString')) {
                    // 对象类型
                    $value = $value->__toString();
                }
        }

        return $value;
    }

    /**
     * 通过修改器 设置数据对象值
     * @access public
     * @param  string $name  属性名
     * @param  mixed  $value 属性值
     * @param  array  $data  数据
     * @return void
     */
    public function setAttr(string $name, $value, array $data = []): void
    {
        // 检测修改器
        $method = 'set' . ucfirst($name) . 'Attr';
        if (method_exists($this, $method)) {
            $array = $this->data;
            $value = $this->$method($value, array_merge($this->data['_source'], $data));
            if (is_null($value) && $array !== $this->data) {
                return;
            }
        } elseif (isset($this->type[$name])) {
            // 类型转换
            $value = $this->writeTransform($value, $this->type[$name]);
        } elseif (is_object($value) && method_exists($value, '__toString')) {
            // 对象类型
            $value = $value->__toString();
        }

        if (!isset($this->data['_source'])) {
            $this->data['_source'] = [];
        }

        // 设置数据对象属性
        $this->data['_source'][$name] = $value;
    }

    /**
     * 通过修改器 批量设置数据对象值
     * @access public
     * @param  array $data  数据
     * @return void
     */
    private function setAttrs(array $data): void
    {
        // 进行数据处理
        foreach ($data as $key => $value) {
            $this->setAttr($key, $value, $data);
        }
    }

    /**
     * 要提交的数据
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

    /**
     * 保存当前数据对象
     * @access public
     * @param array  $data     数据
     * @return bool
     */
    public function save(array $data = [])
    {
        // 数据对象赋值
        $this->setAttrs($data);
        
        if ($this->isEmpty()) {
            return false;
        }

        $result = $this->exists ? $this->updateData() : $this->insertData();

        if (false === $result) {
            return false;
        }

        return true;
    }
    
    /**
     * 删除记录
     * @return bool
     */
    public function delete()
    {
        $data = $this->data['_source'];
        $result = $this->app->client->delete([
            'index' => $this->app->name,
            'id' => $this->data['_id']
        ]);

        if (is_array($result)) {
            if ($result['result'] == 'deleted') {
                $this->data = $result;
                $this->data['_source'] = $data;

                return $this;
            }
        }

        return false;
    }

    /**
     * 新增写入数据
     * @access protected
     * @return bool
     */
    protected function insertData()
    {
        $data = $this->data['_source'];
        $result = $this->app->client->create([
            'index' => $this->app->name,
            'id' => uniqid(),
            'body' => $data
        ]);
        
        $this->data = $result;
        $this->data['_source'] = $data;

        if (is_array($result)) {
            if (!isset($result['error'])) {
                $this->data = $result;
                $this->data['_source'] = $data;

                return $this;
            }
        }

        return false;
    }

    /**
     * 更新数据
     * @access protected
     * @return void
     */
    protected function updateData()
    {
        $data = $this->data['_source'];
        $result = $this->app->client->update([
            'index' => $this->app->name,
            'id' => $this->data['_id'],
            'body' => [
                'doc' => $data
            ]
        ]);

        if (is_array($result)) {
            if (!isset($result['error'])) {
                $this->data = $result;
                $this->data['_source'] = $data;

                return $this;
            }
        }

        return false;
    }
}