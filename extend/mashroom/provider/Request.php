<?php
namespace mashroom\provider;
/**
 * 应用请求对象类
 * @package mashroom.provider
 */
use think\App;

class Request extends \think\Request
{
    /**
     * 用户登录信息存储
     *
     * @var array
     */
    public $user = null;

    /**
     * 当前模块
     * @var string
     */
    protected $module = '';

    /**
     * 当前插件
     *
     * @var string
     */
    protected $addon = '';

    /**
     * 应用类
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * 验证是否存在登录信息
     * @return Boolean
     */
    public function isLogin() {
        if (empty($this->user) || !is_array($this->user)) {
            return false;
        } elseif(is_array($this->user) && !isset($this->user['user_id'])) {
            return false;
        } elseif($this->user['user_id'] == 0) {
            return false;
        }

        return true;
    }

    /**
     * 当前是否JSON请求
     * @access public
     * @return bool
     */
    public function isJson(): bool
    {
        if (config('app.response_data_type') == 'json') {
            return true;
        }

        return parent::isJson();
    }

    /**
     * 设置为登录用户
     * @return mixed
     */
    public function login($user = [], $expireTime = 43200)
    {
        if ($this->user === null) {
            $token = defined('S0') ? S0 : cookie('token');
            $this->user = redis()->get("usr.{$token}");
            if (!isset($this->user['SESSION_ID'])) {
                throw new \Exception('INVALID PARAM');
            }
        }

        $this->user = array_merge($this->user, $user);
        redis()->tag("login")->set("usr.{$this->user['SESSION_ID']}", $this->user, $expireTime);

        return [
            'ign' => TIMESTAMP,
            'token' => isset($user['token']) ? $user['token'] : ""
        ];
    }

    /**
     * 更新登录缓存
     *
     * @param array $params
     * @return void
     */
    public function merge($params = [])
    {
        if (!empty($this->user)) {
            $this->user = array_merge($this->user, $params);
            redis()->tag("login")->set("usr.{$this->user['SESSION_ID']}", $this->user, 3600);
        }
    }

    /**
     * 设置当前的模块名
     * @access public
     * @param  string $module 控制器名
     * @return $this
     */
    public function setModule(string $module)
    {
        $this->module = $module;
        return $this;
    }

    /**
     * 获取当前的模块名
     * @access public
     * @param  bool $convert 转换为小写
     * @return string
     */
    public function module(bool $convert = false): string
    {
        $name = $this->module ?: '';
        return $convert ? strtolower($name) : $name;
    }

    /**
     * 设置当前的插件名
     * @access public
     * @param  string $module 插件名
     * @return $this
     */
    public function setAddon(string $addon)
    {
        $this->addon = $addon;
        return $this;
    }

    /**
     * 获取当前的插件名
     * @access public
     * @param  bool $convert 转换为小写
     * @return string
     */
    public function addon(bool $convert = false): string
    {
        $name = $this->addon ?: '';
        return $convert ? strtolower($name) : $name;
    }

    /**
     * 设置当前的应用类
     * @access public
     * @param  string $module 插件名
     * @return $this
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * 获取当前的应用类
     * @access public
     * @return string
     */
    public function prefix(): string
    {
        return $this->prefix ?: '';
    }
}
