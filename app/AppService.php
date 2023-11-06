<?php
declare (strict_types = 1);
namespace app;
use think\Service;
/**
 * 应用服务类
 * 
 * app('mushroom')->__call();
 */
class AppService extends Service
{
    /**
     * 服务注册
     *
     * @return void
     */
    public function register()
    {
        // $this->app->bind('mushroom', \mashroom\service\App::class);
    }

    public function boot()
    {
        // 服务启动
    }
}
