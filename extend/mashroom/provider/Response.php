<?php
namespace mashroom\provider;
/**
 * @package mashroom.provider
 * @author k.
 */
use think\response\View;

class Response extends View
{
    // 输出参数
    protected $options = [
        'json_encode_param' => JSON_UNESCAPED_UNICODE,
    ];

    /**
     * 发送HTTP状态
     * @access public
     * @param  integer $code 状态码
     * @return $this
     */
    public function code(int $code = 200)
    {
        return parent::code($code);
    }

    /**
     * 处理数据
     * @access protected
     * @param  mixed $data 要处理的数据
     * @return string
     */
    protected function output($data): string
    {
        if (CONTENT_TYPE == 'application/json') {
            return $this->json($data);
        } elseif (CONTENT_TYPE == 'application/xml') {
            return $this->xml($data);
        } else {
            return parent::output($data);
        }
    }

    /**
     * 返回JSON数据
     *
     * @param array $data
     * @return string
     */
    private function json($data)
    {
        try {
            // 返回JSON数据格式到客户端 包含状态信息
            $data = json_encode($data, $this->options['json_encode_param']);

            if (false === $data) {
                throw new \InvalidArgumentException(json_last_error_msg());
            }

            return $data;
        } catch (\Exception $e) {
            if ($e->getPrevious()) {
                throw $e->getPrevious();
            }
            throw $e;
        }
    }

    /**
     * 返回XML数据
     *
     * @access protected
     * @param  mixed $data 要处理的数据
     * @return mixed
     */
    protected function xml($data): string
    {
        if (is_string($data)) {
            if (0 !== strpos($data, '<?xml')) {
                $encoding = $this->options['encoding'];
                $xml      = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
                $data     = $xml . $data;
            }
            return $data;
        }

        // XML数据转换
        return $this->xmlEncode($data, $this->options['root_node'], $this->options['item_node'], $this->options['root_attr'], $this->options['item_key'], $this->options['encoding']);
    }
}