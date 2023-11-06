<?php
namespace mashroom\middleware;
/**
 * 中间件基础类
 * 
 * @version 1.0.0
 */
use think\App;

class BaseMiddleware
{
    protected $app;

    /**
    * @param int $num
    * @param mixed $default
    * @return mixed
    * @author xaboy
    * @day 2020-04-10
    */
    protected function getArgument($args, $num, $default = null)
    {
        return isset($args[$num]) ? $args[$num] : $default;
    }

    /**
     * Undocumented function
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }
}
