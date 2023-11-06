<?php
namespace mashroom\provider\connector;
use think\db\BaseQuery;
use mashroom\provider\Query;

class Mysql extends \think\db\connector\Mysql
{
    /**
     * 分析缓存Key
     * @access protected
     * @param BaseQuery $query 查询对象
     * @param string    $method 查询方法
     * @return string
     */
    protected function getCacheKey(BaseQuery $query, string $method = ''): string
    {
        $key = 'think_' . $this->getConfig('database') . '.' . $query->getTable() . '|' . json_encode($query->getOptions());
        return $key;
    }

    /**
     * 获取缓存Key
     *
     * @param BaseQuery $query
     * @param string $method
     * @return string
     */
    public function getCacheKeyString(BaseQuery $query, string $method = ''): string
    {
        return $this->getCacheKey($query, $method);
    }

    /**
     * 获取当前连接器类对应的Query类
     * @access public
     * @return string
     */
    public function getQueryClass(): string
    {
        return $this->getConfig('query') ?: Query::class;
    }
}