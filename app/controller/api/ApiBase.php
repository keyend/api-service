<?php
/**
 * Api控制器基础类
 * 
 * @version 1.0.0
 */
namespace app\api;
use app\BaseController;

class ApiBase extends BaseController
{
    /**
     * 请求参数
     *
     * @var array
     */
    protected $params = [];

    /**
     * 魔术方法
     *
     * @var array
     */
    private function magic()
    {
        return [
            'logger' => [
                'call' => 'info', 
                'class' => \app\model\system\LogsModel::class
            ]
        ];
    }

    /**
     * 遍历返回值
     * @param Array   $args 参数列表
     * @param String  $message 默认返回MSG
     * @param Integer $status 默认返回状态值
     * @return ResponseArray
     */
    protected function get_defined_vals($args, $message = '', $code = 0)
    {
        if (count($args) > 0) {
            if (is_string($args[0])) {
                $message = array_splice_value($args);
            } elseif(is_integer($args[0]) || is_numeric($args)) {
                $code = array_splice_value($args);
            }

            foreach ($args as $i => $arg) {
                if (is_array($arg)) {
                    $data = array_splice_value($args, $i);
                } elseif(is_callable([$this, $args[0]])) {
                    $method = array_splice_value($args, $i);
                    $data = $this->$method(...$args);
                } elseif(is_callable($args[0])) {
                    $method = array_splice_value($args, $i);
                    $data = call_user_func($method, $args);
                } elseif(is_integer($args[0]) || is_numeric($args)) {
                    $code = array_splice_value($args, $i);
                } elseif (is_string($args[0])) {
                    $message = array_splice_value($args, $i);
                } elseif (method_exists($arg, 'toArray')) {
                    $data = $arg->toArray();
                }
            }
        }

        if (isset($data)) {
            if ($data instanceof Arrayable) {
                $data = $data->toArray();
            } elseif ($data instanceof Response) {
                return $data;
            } elseif(!is_array($data)) {
                $data = (array)$data;
            }
        } else {
            $data = null;
        }

        return compact('code', 'message', 'data');
    }

    /**
     * 返回成功
     *
     * @param [type] ...$args
     * @return void
     */
    protected function success(...$args)
    {
        return $this->get_defined_vals($args, 'success', 0);
    }

    /**
     * 返回错误
     *
     * @param [type] ...$args
     * @return void
     */
    protected function fail(...$args)
    {
        return $this->get_defined_vals($args, 'failed', 500);
    }

    /**
     * 构造函数
     * @depends methodName
     */
    protected function initialize()
    {
        parent::initialize();
        $this->model = app()->make(\app\Model::class, [get_called_class()]);
        $this->params = $this->request->param();
    }

    /**
     * 返回参数列表
     *
     * @param array $params
     * @return void
     */
    protected function params($filters = [], $force = false)
    {
        return array_keys_filter($this->params, $filters, $force);
    }

    /**
     * 调用魔术方法
     *
     * @param string $name
     * @param mixed $arguments
     * @return object
     */
    public function __call($name, $arguments)
    {
        $magic = $this->magic();
        if (in_array($name, array_keys($magic))) {
            $params = $magic[$name];
            $class = $this->app->make($params['class']);
            return call_user_func_array([$class, $params['call']], $arguments);
        }
    }
}