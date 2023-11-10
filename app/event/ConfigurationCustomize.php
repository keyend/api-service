<?php
namespace app\event;
/**
 * 自定义参数配置
 *
 * @param array $param
 * @return void
 * @version 1.0.0
 */
use think\App;
use app\controller\admin\Controller;
use app\model\system\Config as SystemConfig;

class ConfigurationCustomize extends Controller
{
    /**
     * 表单类型列表
     *
     * @return void
     */
    private function getTypes()
    {
        return [
            ['name' => 'input', 'title' => '输入框'],
            ['name' => 'textarea', 'title' => '文本域'],
            ['name' => 'title', 'title' => '标题'],
            ['name' => 'warning', 'title' => '警示文字'],
            ['name' => 'editor', 'title' => '富文本'],
            ['name' => 'checkbox', 'title' => '复选框'],
            ['name' => 'radio', 'title' => '单选框'],
            ['name' => 'switch', 'title' => '开关'],
            ['name' => 'image', 'title' => '单图片选择'],
            ['name' => 'album', 'title' => '多图片选择'],
            ['name' => 'password', 'title' => '密码输入框'],
            ['name' => 'date', 'title' => '日期选择'],
            ['name' => 'datetime', 'title' => '日期时间选择'],
            ['name' => 'daterange', 'title' => '日期时间范围选择'],
            ['name' => 'select', 'title' => '下拉选择框'],
            ['name' => 'colorpicker', 'title' => '颜色选择器'],
            ['name' => 'iconpicker', 'title' => '图标选择器']
        ];
    }

    public function handle($params = [])
    {
        $this->assign('value', $params);
        $this->assign('types', $this->getTypes());
        $content = $this->fetch("Config/customize");
        echo ($content);
    }
}