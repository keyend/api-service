<?php
/**
 * Api控制器基础类
 * 
 * @version 1.0.0
 */
namespace app\controller\api;
use app\BaseController;
use think\Response;

class ApiBase extends BaseController
{

    /**
     * 请求参数
     *
     * @var array
     */
    protected $params = [];

    /**
     * 请求路径
     *
     * @var string
     */
    protected $pathinfo = null;
 
    /**
     * 接口调用初始化
     *
     * @return void
     */
    protected function initialize()
    {
        parent::initialize();
        $this->pathinfo = $this->request->pathinfo();
        $this->mer_id = input('mer_id', '');
        $this->params = $this->request->param();
    }

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
                'class' => \app\model\system\Logs::class
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
    protected function dispatch($args, $message = '', $code = 0)
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
                } elseif ($arg instanceof \app\Model) {
                    $data = $arg;
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
            } elseif ($arg instanceof \app\Model) {
                $data = $data->toArray();
            } elseif ($data instanceof Response) {
                return $data;
            } elseif(!is_array($data)) {
                $data = (array)$data;
            }
        } else {
            $data = null;
        }

        $data = compact('code', 'message', 'data');

        return $this->_output($data);
    }

    /**
     * 返回成功
     *
     * @param [type] ...$args
     * @return void
     */
    protected function success(...$args)
    {
        return $this->dispatch($args, 'success', 0);
    }

    /**
     * 返回错误
     *
     * @param [type] ...$args
     * @return void
     */
    protected function error(...$args)
    {
        return $this->dispatch($args, 'failed', 500);
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

    /**
     * 返回输出内容
     *
     * @param array $data
     * @return void
     */
    private function _output($data)
    {
        if (defined('S6')) {
            $transcation = \app\model\protocol\Logs::find(S7);
            $transcation->response = is_string($data) ? $data : json_encode($data);
            $transcation->save();

            $order = $transcation->comboOrder;
            if ($order->combo_method == 'times') {
                $order->times = $order->times - 1;
                $order->save();
            }
        }

        return Response::create($data, 'json');
    }
}