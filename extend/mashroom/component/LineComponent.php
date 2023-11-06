<?php
namespace mashroom\component;
/**
 * PHP表单生成器
 *
 * @package  FormBuilder
 * @author   xaboy <xaboy2005@qq.com>
 * @version  2.0
 * @license  MIT
 * @link     https://github.com/xaboy/form-builder
 * @document http://php.form-create.com
            $rule->title()
            $rule = new \FormBuilder\Driver\CustomComponent('div');
            $title = new \FormBuilder\Driver\CustomComponent('div');
            $line = new \FormBuilder\Driver\CustomComponent('hr');
            $line->props([
                'color' => '#e0e0e0',
                'size' => 1,
                'style' => 'margin-bottom: 1rem;'
            ]);
            $rule->appendChild($line);
            $rule->props([
                'class' => 'el-col el-col-24'
            ]);
 */
use FormBuilder\Driver\CustomComponent;

/**
 * 自定义组件Line
 * Class CustomComponent
 */
class LineComponent extends CustomComponent
{
    // 默认属性
    protected $defaultProps = [
        'class' => 'el-col el-col-24',
        'style' => 'margin-top: 1rem;'
    ];

    // 标题
    protected $title;
    // 横线
    protected $line;

    /**
     * CustomComponent constructor.
     * @param null|array $data
     */
    public function __construct($name, $data = [])
    {
        $this->setRuleType('div')->props($this->defaultProps);

        if (!isset($data['title'])) {
            $data['title'] = lang("config.{$name}.{$data['name']}");
        }

        $this->title = new CustomComponent('h4');
        $this->title->props([
            'style' => 'font-weight: normal; display: inline; padding: 0px 1rem;'
        ]);
        $this->title($data['title']);

        $this->line = new CustomComponent('hr');
        $this->line->props([
            'color' => '#e0e0e0',
            'size' => 1,
            'style' => 'margin-bottom: 1rem;'
        ]);


        $this->appendChild($this->title);
        $this->appendChild($this->line);
    }

    /**
     * 设置标题
     *
     * @param string $title
     * @return void
     */
    public function title($title)
    {
        $this->title->appendChild($title);

        return $this;
    }
}