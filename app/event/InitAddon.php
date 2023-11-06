<?php
namespace app\event;
/**
 * 插件初始化
 * 
 * @version 1.0.0
 */
use think\App;
use app\model\system\Rule;

class InitAddon
{
    /**
     * 中间件
     *
     * @var array
     */
    private function middleware($module) 
    {
        $middlewares = [
            'admin' => [ \app\middleware\Authorization::class ]
        ];
        return $middlewares[$module] ?? [];
    }

    /**
     * 插件事件
     *
     * @param array $param
     * @return void
     */
    public function handle(App $app)
    {
        $pathinfo = $app->request->pathinfo();
        $dispatch = $app->make(Rule::class)->getRule([[ 'rule' , '=', $pathinfo]]) ?? [];
        $rule = !isset($dispatch["rule"]) || empty($dispatch["rule"]) ? $app->request->prefix() . "/" . $app->request->action() : $dispatch["rule"];
        $ruleName = $dispatch["name"] ?? "";
        $app->route->rule($pathinfo, $rule)->name($ruleName)->middleware($this->middleware($app->request->module()), empty($ruleName));
    }
}