<?php
namespace app\service;
/**
 * 插件服务
 * 
 * @version 1.0.0
 */
use think\Service;
use think\facade\Cache;
use app\model\system\Config;
use app\event\InitAddon;
use app\event\Constant;
use app\event\AdapterFilter;
use app\event\HeartBeat;

class AddonService extends Service
{
    /**
     * 服务注册
     *
     * @return void
     */
    public function register()
    {
        $pathinfo = $this->app->request->pathinfo();
        $sys_modules = $this->app->make(Config::class)->getModules();
        $pathinfo_array = array_filter(explode('/', $pathinfo));
        $pathinfo_array[0] = $pathinfo_array[0] ?? config("app.default_app", "admin");
        $check_module = trim($pathinfo_array[0], '.html');
        $addon = '';
        $appPath = $this->app->getAppPath();
        $configPath = $appPath . 'config' . DIRECTORY_SEPARATOR;
        $configExt = $this->app->env->get('config_ext', '.php');
        if (is_dir($configPath)) {
            $files = glob($configPath . '*' . $configExt);
            foreach ($files as $file) {
                $this->app->config->load($file, pathinfo($file, PATHINFO_FILENAME));
            }
        }

        if (count($pathinfo_array) > 1 && !in_array($check_module, $sys_modules)) {
            $addon = array_shift($pathinfo_array);
            $module = str_replace('.html', '', isset($pathinfo_array[0]) ? array_shift($pathinfo_array) : '');
            $action = str_replace('.html', '', !empty($pathinfo_array) && count($pathinfo_array) > 1 ? array_pop($pathinfo_array) : 'index');
            $controller = str_replace('.html', '', !empty($pathinfo_array) ? end($pathinfo_array) : 'index');
            $prefix = str_replace(".html", "", implode(".", $pathinfo_array));
            $this->app->setNamespace("app\\addons\\" . $addon . '\\' . $module);
            $this->app->setAppPath($this->app->getAppPath() . 'addons' . DIRECTORY_SEPARATOR . $addon . DIRECTORY_SEPARATOR . (!empty($module) ? $module . DIRECTORY_SEPARATOR : ''));
        } else {
            $module = str_replace('.html', '', !empty($pathinfo_array) && count($pathinfo_array) > 0 ? array_shift($pathinfo_array) : 'index');
            $action = str_replace('.html', '', !empty($pathinfo_array) && count($pathinfo_array) > 1 ? array_pop($pathinfo_array) : 'index');
            $controller = str_replace('.html', '', !empty($pathinfo_array) ? end($pathinfo_array) : 'index');
            $prefix = $module . "." . (empty($pathinfo_array) ? 'index' : str_replace(".html", "", implode(".", $pathinfo_array)));
        }

        $this->app->request->setAddon($addon);
        $this->app->request->setModule($module);
        $this->app->request->setController($controller);
        $this->app->request->setAction($action);
        $this->app->request->setPrefix($prefix);
        $this->app->event->listenEvents([
            'HttpRun' => [ Constant::class, InitAddon::class, AdapterFilter::class ],
            'HttpEnd' => [ HeartBeat::class ]
        ]);

        define('MODULE', $module);
        define('CONTROLLER',$controller);
        define('ACTION', $action);
        define('ADDON', $addon);
    }

    /**
     * 订阅插件事件
     *
     * @return void
     */
    private function getAddonEvents()
    {
        $events = Cache::get("system_addons_events") ?? [];
        if (empty($events)) {
            $events = [
                'bind' => [],
                'listen' => [],
                'subscribe' => []
            ];
            $addons = $this->app->make(Config::class)->getAddons();
            $addonPath = $this->app->getRootPath() . "app" . DIRECTORY_SEPARATOR . "addons" . DIRECTORY_SEPARATOR;
            foreach ($addons as $addon) {
                $eventPath = $addonPath . $addon . DIRECTORY_SEPARATOR . 'event.php';
                if (file_exists($eventPath)) {
                    $current = include $eventPath;
                    foreach($events as $ev => $values) {
                        if (isset($current[$ev])) {
                            $events[$ev] = array_merge($values, $current[$ev]);
                        }
                    }
                }
            }
            Cache::tag("config")->set("system_addons_events", $events);
        }
        return $events;
    }

    /**
     * 服务注册
     *
     * @return void
     */
    public function boot()
    {
        if (!empty($this->app->request->addon())) {
            $appPath = $this->app->getAppPath();
            $configPath = $appPath . 'config' . DIRECTORY_SEPARATOR;
            $configExt = $this->app->env->get('config_ext', '.php');
            if (is_dir($configPath)) {
                $files = glob($configPath . '*' . $configExt);
                foreach ($files as $file) {
                    $this->app->config->load($file, pathinfo($file, PATHINFO_FILENAME));
                }
            }

            if (is_file($appPath . 'common.php')) {
                include_once $appPath . 'common.php';
            }

            if (is_file($appPath . 'event.php')) {
                $this->app->loadEvent(include $appPath . 'event.php');
            }

            if (is_file($appPath . 'middleware.php')) {
                $this->app->middleware->import(include $appPath . 'middleware.php');
            }
        }

        $events = $this->getAddonEvents();
        if (!empty($events)) {
            $this->app->loadEvent($events);
        }
    }
}