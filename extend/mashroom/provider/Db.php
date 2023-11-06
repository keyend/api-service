<?php
namespace mashroom\provider;
use think\db\ConnectionInterface;

class Db extends \think\Db
{
    /**
     * 创建连接
     * @param $name
     * @return ConnectionInterface
     */
    protected function createConnection(string $name): ConnectionInterface
    {
        $config = $this->getConnectionConfig($name);

        $type = !empty($config['type']) ? $config['type'] : 'mysql';

        if (false !== strpos($type, '\\')) {
            $class = $type;
        } else {
            $class = '\\mashroom\\provider\\connector\\' . ucfirst($type);
            if (!class_exists($class)) {
                $class = '\\think\\db\\connector\\' . ucfirst($type);
            }
        }

        /** @var ConnectionInterface $connection */
        $connection = new $class($config);
        $connection->setDb($this);

        if ($this->cache) {
            $connection->setCache($this->cache);
        }

        return $connection;
    }
}