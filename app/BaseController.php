<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\exception\ValidateException;
use mashroom\provider\Validate;
use think\facade\View;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
        // 控制器初始化
        $this->initialize();
        if (!defined('IS_AJAX')) define('IS_AJAX', false);
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 返回调用控制器
     *
     * @param string $class 调用类名
     * @return string
     */
    protected function getControllerName($class)
    {
        $instance = explode('\\', $class);

        return end($instance);
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = app()->make(Validate::class);
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        if ($v->failException(false)->check($data) !== true) {
            return $v->getError();
        }

        return true;
    }

    /**
     * 请求为AJAX时返回
     *
     * @param array ...$args
     * @return void
     */
    protected function success(...$args)
    {
        return $args;
    }

    /**
     * 请求为AJAX时返回
     *
     * @param array ...$args
     * @return void
     */
    protected function error(...$args)
    {
        return $args;
    }

    /**
     * 解析和获取模板内容 用于输出
     * @access public
     * @param string $template 模板文件名或者内容
     * @param array  $vars     模板变量
     * @return string
     * @throws \Exception
     */
    protected function fetch(string $template = '', array $vars = [])
    {
        if (IS_AJAX) {
            return $this->success();
        } elseif ($template === '') {
            $array = debug_backtrace();
            $template = $this->getControllerName($array[1]['class']) . '/' . $array[1]['function'];
        }
        // 模板输出
        return View::fetch($template, $vars);
    }

    /**
     * 渲染内容输出
     * @access public
     * @param string $content 内容
     * @param array  $vars    模板变量
     * @return string
     */
    protected function display(string $content, array $vars = []): string
    {
        // 内容输出
        return View::dispaly($content, $vars);
    }

    /**
     * 模板变量赋值
     * @access public
     * @param string|array $name  模板变量
     * @param mixed        $value 变量值
     * @return $this
     */
    protected function assign($name, $value = null)
    {
        // 变量赋值
        return View::assign($name, $value);
    }
}
