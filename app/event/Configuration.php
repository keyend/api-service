<?php
namespace app\event;
/**
 * 通用注册方法
 *
 * @param array $param
 * @return void
 * @version 1.0.0
 */
use think\App;
use app\controller\admin\Controller;
use app\model\system\Config as SystemConfig;

class Configuration extends Controller
{
    /**
     * 解析参数
     *
     * @param array $args
     * @return void
     */
    public function handle($args = [])
    {
        $name = strtoupper(CONTROLLER . "_" . ACTION);
        [$template, $data] = $args;
        $config = new SystemConfig();
        if (IS_AJAX) {
            $config->setConfig($name, [ "value" => $this->params ]);
            return $this->success();
        } else {
            $this->assign("value", $config->getConfig($name));
            $template = $template == '' ? 'Config/setting' : $template;
            return $this->fetch($template);
        }
    }
}