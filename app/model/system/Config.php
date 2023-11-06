<?php
namespace app\model\system;
/**
 * 配置
 *
 * @package app.common.model
 * @date: 2021-05-10 20:19:31
 */
use app\Model;
use think\facade\Cache;

class Config extends Model
{
    protected $name = 'sys_config';
    protected $pk = 'id';

    /**
     * 自动序列化
     *
     * @param [type] $value
     * @param [type] $data
     * @return void
     */
    public function getAttrAttr($value, $data)
    {
        return json_decode($value, true);
    }

    /**
     * 返回系统模块列表
     *
     * @return void
     */
    public function getModules()
    {
        $paths = Cache::get("system_modules") ?? [];
        if (empty($paths)) {
            foreach(glob(app()->getAppPath() ."controller/*", GLOB_ONLYDIR) as $path) {
                $paths[] = basename($path);
            }
            Cache::tag("config")->set("system_modules", $paths);
        }
        return $paths;
    }
}