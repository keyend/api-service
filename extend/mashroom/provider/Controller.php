<?php
namespace mashroom\provider;
/*
 * @Author: 控制器
 * @Date: 2021-05-10 20:19:31
 */
use app\BaseController;

class Controller extends BaseController
{
    /**
     * 遍历返回值
     * @param Array   $args 参数列表
     * @param String  $message 默认返回MSG
     * @param Integer $status 默认返回状态值
     * @return ResponseArray
     */
    protected function get_defined_vals($args, $message = '', $code = 200)
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

    protected function success(...$args) {
        return $this->get_defined_vals($args, 'success', 200);
    }

    protected function fail(...$args) {
        return $this->get_defined_vals($args, 'failed', 500);
    }
}
