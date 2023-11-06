<?php
namespace mashroom\websocket;
/**
 * SOCKET分解
 * @package mashroom.websocket
 */
use Exception;
use Swoole\Server;
use Swoole\Timer;
use Swoole\Websocket\Frame;
use think\App;
use think\Config;
use think\Event;
use think\Request;
use mashroom\service\Websocket;
use think\swoole\websocket\Room;

class Handler extends Websocket
{
    /**
     * 心跳时间
     *
     * @var integer 
     */
    protected $pingInterval;

    /**
     * 超时时间
     *
     * @var integer
     */
    protected $pingTimeout;

    public function __construct(App $app, Server $server, Room $room, Event $event, Config $config)
    {
        $this->config       = $config;
        $this->pingInterval = $this->config->get('swoole.websocket.ping_interval', 25000);
        $this->pingTimeout  = $this->config->get('swoole.websocket.ping_timeout', 60000);

        parent::__construct($app, $server, $room, $event);
    }

    /**
     * "onOpen" listener.
     *
     * @param int $fd
     * @param Request $request
     */
    public function onOpen($fd, Request $request)
    {
        $this->event->trigger('swoole.websocket.Open', [$this, $request, [
            'ping_interval' => $this->pingInterval,
            'ping_timeout'  => $this->pingTimeout,
        ]]);
    }

    /**
     * "onMessage" listener.
     *
     * @param Frame $frame
     */
    public function onMessage(Frame $frame, Request $request)
    {
        $this->event->trigger('swoole.websocket.Message', [$this, $request, $frame]);
    }

    /**
     * "onClose" listener.
     *
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose($fd, $reactorId)
    {
        $this->event->trigger('swoole.websocket.Close', [$this, $fd, $reactorId]);
    }
}
