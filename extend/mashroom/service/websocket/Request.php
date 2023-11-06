<?php
/**
 * WEBSOCKET 约定消息
 * 
 * @package websocket.convention
 * @version 2.0.1
 */
namespace mashroom\service\websocket;

class Request
{
    // 默认事件
    const DEFAULT_EVENT = 'message';
    
    // 连接匹配
    const CONNECTED = 'connected';

    // 广播
    const BOARDCAST = 'boardcast';

    // 群通知
    const NOTICE = 'notice';

    /**
     * 消息类型
     *
     * @var string
     */
    public $type;

    /**
     * 消息发送人
     *
     * @var string
     */
    public $from;

    /**
     * 消息发送人
     *
     * @var string
     */
    public $to;

    /**
     * 消息内容
     *
     * @var string|int|array
     */
    public $data;

    /**
     * 解析报文
     *
     * @param string $payload
     * @return Array
     * --------------------------------------------
     * 请求
     * [
     *      type => [message]
     *      data => [
     *          to => wx203841283423
     *          from => wx832848234212
     *          data => iwKe2371nJnweoHuYFUhgwer==
     *      ]
     * ]
     * --------------------------------------------
     * 返回
     * [
     *      status => 1,
     *      message => ...
     *      data => []
     * ]
     * --------------------------------------------
     */
    private static function decode($payload)
    {
        $payload = json_decode($payload, true);

        if ($payload == null) {
            $payload = ['data' => $payload];
        }

        $data = [
            'type' => $payload['type'] ?? self::DEFAULT_EVENT,
            'data' => $payload['data'] ?? []
        ];

        return $data;
    }

    /**
     * 载入内容
     *
     * @param [type] $data
     * @return void
     */
    public static function load($req)
    {
        $ds = self::decode($req->data);
        if (!isset($ds['data']['from'])) {
            $data['data']['from'] = $req->fd;
        }

        $instance = new self();
        $instance->type = $ds['type'];
        $instance->from = $ds['data']['from'];
        $instance->to = $ds['data']['to']??null;
        $instance->data = $ds['data'];

        return $instance;
    }
}