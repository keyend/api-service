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
        $pathinfo = str_replace(".html", "", $app->request->pathinfo());
        $addon = $app->request->addon();
        $dispatch = $app->make(Rule::class)->getRule([ [ 'rule_id' , '=', $pathinfo], [ 'module', '=', $app->request->module() ], [ 'addon', '=', $addon ] ]) ?? [];
        $rule = $app->request->prefix() . "/" . $app->request->action();
        $ruleName = $dispatch["name"] ?? "";
        $prefix = $app->request->prefix();
        if (!empty($prefix)) {
            $app->route->rule($pathinfo, "/" . $app->request->action())
            ->name($ruleName)
            ->middleware($this->middleware($app->request->module()), $app->request->controller() != "login" ? !empty($ruleName) : null)
            ->prefix($prefix);
        } else {
            $app->route->rule($pathinfo, $rule)
            ->name($ruleName)
            ->middleware($this->middleware($app->request->module()), !empty($ruleName));
        }
    }
}