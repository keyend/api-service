<?php
namespace mashroom\provider;

use think\Response;
use think\Request;
use think\event\HttpRun;
/**
 * Web应用管理类
 * @package mashroom.provider
 */
class Http extends \think\Http
{
    /**
     * 执行应用程序
     * @access public
     * @param Request|null $request
     * @return Response
     */
    public function run(Request $request = null): Response
    {
        //初始化
        $this->initialize();

        //自动创建request对象
        $request = $request ?? $this->app->request;
        $this->app->instance('request', $request);

        try {
            $response = $this->runWithRequest($request);
        } catch (Throwable $e) {
            $this->reportException($e);

            $response = $this->renderException($request, $e);
        }

        return $response;
    }

    /**
     * 执行应用程序
     * @param Request $request
     * @return mixed
     */
    protected function runWithRequest(Request $request)
    {
        // 加载全局中间件
        $this->loadMiddleware();

        // 监听HttpRun
        $this->app->event->trigger(HttpRun::class, $this->app);

        return $this->app->middleware->pipeline()
            ->send($request)
            ->then(function ($request) {
                return $this->dispatchToRoute($request);
            });
    }

    /**
     * 加载路由
     * @access protected
     * @return void
     */
    protected function loadRoutes(): void
    {
        // 加载路由定义
        $routePath = $this->app->getAppPath() . 'route' . DIRECTORY_SEPARATOR;

        if (is_dir($routePath)) {
            $files = glob($routePath . '*.php');
            foreach ($files as $file) {
                include $file;
            }
        }

        $this->app->event->trigger(RouteLoaded::class);
    }
}