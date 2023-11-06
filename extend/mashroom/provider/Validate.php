<?php
namespace mashroom\provider;
/**
 * 验证器
 * @package mashroom.provider
 */
use mashroom\provider\Request;
use mashroom\exception\ValidateException;
use think\Lang;

class Validate extends \think\Validate
{
    /**
     * 请求对象
     * @var Closure[]
     */
    protected $request;

    /**
     * 验证失败是否抛出异常
     * @var bool
     */
    protected $failException = false;

    /**
     * 构造方法
     * @access public
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        parent::__construct();
    }

    /**
     * 验证方法
     * @var string
     */
    public function filterCheck($data = [], $filters = [])
    {
        if (!empty($filters)) {
            $data = array_keys_filter($data, $filters);
        }

        return parent::check($data) ? $data : [];
    }

    /**
     * 数据验证
     * @param Array $filter 过滤规则
     * @param Array $ext
     * @return boolean
     */
    public function get($filter = [], $ext = [])
    {
        return $this->filterCheck(array_merge($this->request->get(), $ext), $filter);
    }

    /**
     * 数据验证
     * @param Array $filter 过滤规则
     * @param Array $ext
     * @return boolean
     */
    public function post($filter = [], $ext = [])
    {
        return $this->filterCheck(array_merge($this->request->post(), $ext), $filter);
    }

    /**
     * 数据验证
     * @param Array $filter 过滤规则
     * @param Array $ext
     * @return boolean
     */
    public function param($filter = [], $ext = [])
    {
        return $this->filterCheck(array_merge($this->request->param(), $ext), $filter);
    }
}
