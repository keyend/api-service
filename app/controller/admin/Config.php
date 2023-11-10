<?php
namespace app\controller\admin;
/**
 * 配置
 * @version 1.0.0
 */
use app\model\system\Config as SystemConfig;

class Config extends Controller
{
    // 初始化
    protected function initialize()
    {
        parent::initialize();
        listen('Configuration', 'app\event\Configuration');
    }

    /**
     * 保存配置表单
     *
     * @param SystemConfig $conf
     * @return void
     */
    public function customize(SystemConfig $config)
    {
        if (IS_AJAX) {
            try {
                $values = array_keys_filter($this->params, [ 'module', 'name', 'title', ['addon', ''], ['user_id', 0], ['params', []] ], true);
                $params = [];
                foreach($this->params['params'] as $key => $value) {
                    $id = $key + 1;
                    $value["id"] = $id;
                    $params[$id] = $value;
                }
                $values['params'] = $params;
                $config->setConfig($this->params['name'], $values);
                return $this->success();
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        }
    }

    /**
     * 基础配置
     *
     * @param SystemConfig $conf
     * @return void
     */
    public function index(SystemConfig $config)
    {
        return event('Configuration', ['Config/' . __FUNCTION__, $this->params], true);
    }

    /**
     * 注册配置
     *
     * @param SystemConfig $conf
     * @return void
     */
    public function register(SystemConfig $config)
    {
        return event('Configuration', ['', $this->params], true);
    }

    /**
     * 上传配置
     *
     * @param SystemConfig $conf
     * @return void
     */
    public function upload()
    {
        return event('Configuration', ['', $this->params], true);
    }

    /**
     * 验证码
     *
     * @param SystemConfig $conf
     * @return void
     */
    public function valid()
    {
        return event('Configuration', ['', $this->params], true);
    }
}