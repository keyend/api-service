<?php
namespace mashroom\provider;
/**
 * PDO数据查询类
 */
use think\Collection;
use think\facade\Cache;

class Query extends \think\db\Query
{
    /**
     * 是否引用关联
     *
     * @var boolean
     */
    private $_related = false;

    /**
     * 获取数据缓存配置
     *
     * @return void
     */
    private function getCacheConfiguration()
    {
        static $data_cache = 0;
        if ($this->_related) return false;
        if ($data_cache === 0) {
            $data_cache = $this->connection->getConfig('data_cache');
        }
        return $data_cache;
    }

    /**
     * 关联预载入 In方式
     * @access public
     * @param array|string $with 关联方法名称
     * @return $this
     */
    public function with($with)
    {
        $this->_related = true;
        return parent::with($with);
    }

    /**
     * 全局设置缓存
     *
     * @param string $method
     * @return void
     */
    private function setCacheFallback($method = '')
    {
        $key = $this->connection->getCacheKeyString($this, $method);
        $tag = $this->getTable();

        if (!empty($method)) {
            $this->cache($key, 86400, $tag);
        } else {
            $this->cache($key, null, $tag);
        }
    }

    /**
     * 查找记录
     * @access public
     * @param mixed $data 数据
     * @return Collection|array|static[]
     * @throws Exception
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function select($data = null): Collection
    {
        if ($this->getCacheConfiguration()) {
            $this->setCacheFallback(__FUNCTION__);
        }
        return parent::select($data);
    }

    /**
     * 查找单条记录
     * @access public
     * @param mixed $data 查询数据
     * @return array|Model|null|static|mixed
     * @throws Exception
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function find($data = null)
    {
        if ($this->getCacheConfiguration()) {
            $this->setCacheFallback(__FUNCTION__);
        }
        return parent::find($data);
    }

    /**
     * 更新记录
     * @access public
     * @param mixed $data 数据
     * @return integer
     * @throws Exception
     */
    public function update(array $data = []): int
    {
        if ($this->getCacheConfiguration()) {
            $this->setCacheFallback();
        }
        return parent::update($data);
    }

    /**
     * 保存记录 自动判断insert或者update
     * @access public
     * @param array $data        数据
     * @param bool  $forceInsert 是否强制insert
     * @return integer
     */
    public function save(array $data = [], bool $forceInsert = false)
    {
        if ($this->getCacheConfiguration()) {
            $this->setCacheFallback();
        }
        return parent::save($data, $forceInsert);
    }

    /**
     * 删除记录
     * @access public
     * @param mixed $data 表达式 true 表示强制删除
     * @return int
     * @throws Exception
     */
    public function delete($data = null): int
    {
        if ($this->getCacheConfiguration()) {
            $this->setCacheFallback();
        }
        return parent::delete($data);
    }

    /**
     * 插入记录
     * @access public
     * @param array   $data         数据
     * @param boolean $getLastInsID 返回自增主键
     * @return integer|string
     */
    public function insert(array $data = [], bool $getLastInsID = false)
    {
        if ($this->getCacheConfiguration()) {
            $this->setCacheFallback();
        }
        return parent::insert($data, $getLastInsID);
    }

    /**
     * 批量插入记录
     * @access public
     * @param array   $dataSet 数据集
     * @param integer $limit   每次写入数据限制
     * @return integer
     */
    public function insertAll(array $dataSet = [], int $limit = 0): int
    {
        if ($this->getCacheConfiguration()) {
            $this->setCacheFallback();
        }
        return parent::insertAll($dataSet, $limit);
    }

    /**
     * 通过Select方式插入记录
     * @access public
     * @param array  $fields 要插入的数据表字段名
     * @param string $table  要插入的数据表名
     * @return integer
     */
    public function selectInsert(array $fields, string $table): int
    {
        if ($this->getCacheConfiguration()) {
            $this->setCacheFallback();
        }
        return parent::selectInsert($fields, $table);
    }
}