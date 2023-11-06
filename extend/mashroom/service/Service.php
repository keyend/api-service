<?php
namespace mashroom\service;
/**
 * mashroom.service.Service
 * @version 1.0.0
 */
class Service
{
    /**
     * 实例化服务
     *
     * @param string $name
     * @param mixed $arguments
     * @return void
     */
    public static function instance($arguments)
    {
        if ($arguments !== null) {
            if (!is_array($arguments)) {
                $arguments = [$arguments];
            }
        } else {
            $arguments = [];
        }

        try {
            return new static(...$arguments);
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}