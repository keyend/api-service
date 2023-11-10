<?php
namespace app\event;
/**
 * 自定义参数解析
 *
 * @param array $param
 * @return void
 * @version 1.0.0
 */
use think\App;
use app\controller\admin\Controller;
use app\model\system\Config as SystemConfig;

class ConfigurationComponent extends Controller
{
    /**
     * 解析参数
     *
     * @param array $data
     * @return void
     */
    public function handle($data = [])
    {
        foreach($data["params"] as &$param) {
            $param["attr"] = json_decode($param["attr"], true);
        }
        $this->assign('value', $data);
        $this->assign("uploadImage", false);
        $content = $this->fetch("Config/component");
        echo ($content);
    }
}