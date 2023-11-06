<?php
/**
 * PHP表单生成器
 *
 * @package  FormBuilder
 * @author   xaboy <xaboy2005@qq.com>
 * @version  2.0
 * @license  MIT
 * @link     https://github.com/xaboy/form-builder
 * @document http://php.form-create.com
 */
namespace mashroom\component;

use FormBuilder\Driver\FormComponent;
/**
 * 富文本编辑器
 * Class Cascader
 *
 * @method $this type(string $type) 数据类型, 支持 city_area(省市区三级联动), city (省市二级联动), other (自定义)
 * @method $this props(array $props) 配置选项
 * @method $this size(string $size) 尺寸, 可选值: medium / small / mini
 * @method $this placeholder(string $placeholder) 输入框占位文本, 默认值: 请选择
 * @method $this disabled(bool $disabled) 是否禁用, 默认值: false
 * @method $this clearable(bool $clearable) 是否支持清空选项, 默认值: false
 * @method $this showAllLevels(bool $showAllLevels) 输入框中是否显示选中值的完整路径, 默认值: true
 * @method $this collapseTags(bool $collapseTags) 多选模式下是否折叠Tag, 默认值: false
 * @method $this separator(string $separator) 选项分隔符, 默认值: 斜杠' / '
 * @method $this filterable(bool $filterable) 是否可搜索选项
 * @method $this debounce(float $debounce) 搜索关键词输入的去抖延迟，毫秒, 默认值: 300
 * @method $this popperClass(string $popperClass) 自定义浮层类名
 */
class TinymceComponent extends FormComponent
{
    protected static $propsRule = [
        'height' => 'string',
        // 菜单栏
        'menubar' => 'bool',
        // 插件
        'plugins' => 'array',
        // 工具栏
        'toolbar' => 'array',
        // 文档格式
        'schema' => 'string',
        // 保留注释
        'allow_conditional_comments' => 'bool',
        // 自动加<P>
        'force_p_newlines' => 'bool',
        'forced_root_block ' => 'string',
        // HTML过滤
        'verify_html' => 'bool',
        'invalid_elements' => 'string',
        'keep_styles' => 'bool',
        'valid_children' => 'string',
        'content_style' => 'string',
        'content_css' => 'string',
        // 附加内容
        'uploadExtension' => 'array'
    ];

    protected $defaultProps = [
        'height' => '100px',
        'menubar' => '',
        'force_p_newlines' => false,
        'forced_root_block' => '',
        'verify_html' => false,
        'keep_styles' => false,
        'invalid_elements' => '*[*]',
        'valid_children' => '+p[i],+div[i],+span[i]',
        'plugins' => [
            'advlist autolink lists link image charmap print preview anchor textcolor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table contextmenu paste code help wordcount'
        ],
        'toolbar' => ['insert | formatselect | bullist numlist outdent indent | code removeformat'],
        'content_style' => 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
    ];

    /**
     * 构造编辑器
     *
     * @param string $field
     */
    public function __construct($field, $title, $value = null)
    {
        parent::__construct($field, $title, $value);
        $this->type = 'tinymce';
    }

    public function createValidate()
    {
        return Elm::validateStr();
    }
}
