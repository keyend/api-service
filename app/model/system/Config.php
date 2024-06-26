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
    public function getParamsAttr($value, $data)
    {
        return json_decode($value, true);
    }

    /**
     * 设置参数
     *
     * @param [type] $value
     * @return void
     */
    public function setParamsAttr($value)
    {
        return json_encode($value);
    }

    /**
     * 自动序列化
     *
     * @param [type] $value
     * @param [type] $data
     * @return void
     */
    public function getValueAttr($value, $data)
    {
        return json_decode($value, true);
    }

    /**
     * 设置参数
     *
     * @param [type] $value
     * @return void
     */
    public function setValueAttr($value)
    {
        return json_encode($value);
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

    /**
     * 返回插件列表
     *
     * @return void
     */
    public function getAddons()
    {
        $addons = Cache::get("system_addons") ?? [];
        if (empty($addons)) {
            foreach(glob(app()->getRootPath() ."app/addons/*", GLOB_ONLYDIR) as $path) {
                $addons[] = basename($path);
            }
            Cache::tag("config")->set("system_addons", $addons);
        }
        return $addons;
    }

    /**
     * 设置参数
     *
     * @param [type] $name
     * @param array $values
     * @return void
     */
    public function setConfig($name, $data = [])
    {
        $condition = [ [ "name", "=", $name ] ];
        $config = self::where($condition)->find();
        if (empty($config)) {
            $flag = self::create($data);
        } else {
            $data["update_time"] = TIMESTAMP;
            $data["settings_id"] = $config['settings_id'];
            $flag = $config->save($data);
        }
        if (false === $flag) {
            throw new \Exception("保存失败!");
        }
    }

    /**
     * 获取参数
     *
     * @param string $name
     * @return void
     */
    public function getConfig($name = '')
    {
        $config = self::where([ [ 'name', '=', $name ], [ 'user_id', '=', '0' ] ])->findOrEmpty()->toArray();
        $config["name"] = $config["name"] ?? $name;
        $config["module"] = $config["module"] ?? MODULE;
        $config["addon"] = $config["addon"] ?? ADDON;
        $config["params"] = $config["params"] ?? [];

        return $config;
    }
}