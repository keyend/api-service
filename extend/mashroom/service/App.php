<?php
namespace mashroom\service;
/**
 * mashroom.service
 * @version 1.0.0
 */
class App
{
    /**
     * 获取属性
     *
     * @param string $name
     * @return void
     */
    public function __get($name)
    {
        if ($this->service_exists($name)) {
            return $this->__call($name, []);
        }

        return $this->$name;
    }

    /**
     * 验证服务是否存在
     *
     * @param string $name
     * @return bool
     */
    public function service_exists($name)
    {
        $name = ucfirst($name);
        $classname = "\\" . __NAMESPACE__ . "\\{$name}Service";
        return class_exists($classname);
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
        if (!$this->service_exists($name)) {
            throw new \Exception("访问错误：服务[{$name}]不存在!");
        }
        $classname = "\\" . __NAMESPACE__ . "\\{$name}Service";
        return self::instance($classname, $arguments);
    }

    /**
     * 实例化服务
     *
     * @param string $name
     * @param mixed $arguments
     * @return void
     */
    public static function instance($name, $arguments = null)
    {
        if ($arguments !== null) {
            if (!is_array($arguments)) {
                $arguments = [$arguments];
            }
        } else {
            $arguments = [];
        }

        try {
            return new $name(...$arguments);
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}