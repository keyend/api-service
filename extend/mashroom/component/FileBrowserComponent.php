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
class FileBrowserComponent extends CustomComponent
{
    protected $defaultProps = [
        'url' => '',
        'class' => 'el-filebrowser _fc-upload',
        'accept' => '*',
        'headers' => [],
        'form_params' => [],
    ];

    protected $title;

    /**
     * 构造编辑器
     *
     * @param string $field
     */
    public function __construct($field, $title, $value = null)
    {
        parent::__construct($field, $title, $value);
        $this->type = 'fileBrowser';
        $this->title = $title;
        $this->value = $value;
        $this->field = $field;
    }
}