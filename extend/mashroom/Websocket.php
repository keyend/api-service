<?php
/**
 * WEBSOCKET服务
 * 
 * @package mashroom.websocket
 */
namespace mashroom;

use think\Request;
use mashroom\service\websocket\Response;
use Swoole\WebSocket\Frame;

class Websocket extends \mashroom\provider\Websocket
{
    /**
     * 监听WebSocket连接打开事件
     *
     * @document https://wiki.swoole.com/#/coroutine_client/socket
     * @param array $fd socket对应的文件描述符ID
     * @param mixed $request 请求对象
     * @return void
     */
    public function onOpen($fd, Request $request)
    {
        try {
            $this->event->trigger('swoole.websocket.open', [$fd, $this, $request]);
        } catch (Exception $e) {
            $this->push(Response::create($e->getMessage(), Response::CONNECT_ERROR));
        }
    }

    /**
     * 关闭连接
     *
     * @document https://wiki.swoole.com/#/server/events?id=onclose
     * @param int $fd socket对应的文件描述符ID
     * @param int $reactorId 来自哪个 reactor 线程，主动 close 关闭时为负数
     * @return void
     */
    public function onClose($fd, $reactorId)
    {
        try {
            $this->event->trigger('swoole.websocket.close', [$fd, $this, $reactorId]);
        } catch (Exception $e) {
            // $this->push(Response::create($e->getMessage(), Response::CONNECT_CLOSE_FAIL));
        }

        parent::onClose($fd, $reactorId);
    }

    /**
     * 接收消息
     *
     * @document https://wiki.swoole.com/#/websocket_server
     * @param Frame $frame Swoole\WebSocket\Frame 对象，包含了客户端发来的数据帧信息
     * @return void
     */
    public function onMessage(Frame $frame)
    {
        try {
            $this->event->trigger('swoole.websocket.message', [$frame, $this]);
        } catch (Exception $e) {
            $this->push(Response::create($e->getMessage(), Response::FAILED));
        }
    }
}
