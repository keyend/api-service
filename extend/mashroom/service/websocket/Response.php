<?php
/**
 * WEBSOCKET 约定消息
 * 
 * @package websocket.convention
 * @version 2.0.1
 */
namespace mashroom\service\websocket;

class Response
{
    // 连接错误
    const CONNECT_ERROR = 30001;
    // 关闭时出错
    const CONNECT_CLOSE_FAIL = 30031;
    // 处理事件错误
    const FAILED = 30002;

    // 约定代码
    public $code = 1;
    // 明细内容
    public $message = null;
    // 返回内容
    public $data = [];

    /**
     * 构造函数
     *
     * @param integer $code
     */
    public function __construct(int $code) {
        $this->code = $code;
    }

    /**
     * 创建返回明细
     *
     * @param [type] $code
     * @param array $decoded
     * @return void
     */
    public static function create($data, $code = 1)
    {
        $class = new static($code);

        if (is_array($data)) {
            $class->data = $data;
        } else {
            $class->message = $data;
        }

        return $class;
    }

    /**
     * 返回字串
     *
     * @return array
     */
    public function toJSON() 
    {
        $result = [];
        $result['status'] = $this->code;

        if ($this->code != 1 && $this->message) {
            $result['message'] = $this->message;
        }

        if (!empty($this->data)) {
            $result['data'] = $this->data;
        }

        return json_encode($result);
    }
}