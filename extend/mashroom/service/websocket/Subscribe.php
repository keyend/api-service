<?php
/**
 * Websocket.subscribe
 * 
 * @version 2.0.1
 * @package Websocket.subscribe
 * @description 
 * 
 * 1. 从C0客户端触发事件 -> 服务端C1提交publish -> 服务端C2.Subscribe得知C0需要请求
 * 2. C2.subscribe依据之前建立的链接用Service服务对象返回给对应数据给C0
 */
namespace mashroom\service\websocket;
use think\facade\Config;
use mashroom\service\websocket\Response;
use mashroom\service\websocket\Request;
use mashroom\exception\HttpException;
use Swoole\Timer;

class Subscribe
{
    // 默认房间
    const DEFAULT_ROOM = 'online';

    /**
     * socket对应的文件描述符ID
     *
     * @var int
     */
    protected $fd;

    /**
     * 应用服务组件
     *
     * @var WebSocket
     */
    protected $service;

    /**
     * 请求对象
     *
     * @var think\Request
     */
    protected $request;

    protected $pingTimeoutTimer  = null;
    protected $pingIntervalTimer = null;
    protected $pingInterval;
    protected $pingTimeout;

    /**
     * 返回连接socket对应的文件描述符ID
     *
     * @param [type] $sid
     * @return int
     */
    private function fd($sid, $value = null)
    {
        $name = $this->getName($sid);

        if ($value === null) {
            return redis()->get($name);
        } else {
            redis()->set($name, $value, 900);
        }
    }

    public function __construct()
    {
        $this->pingInterval = $this->config->get('swoole.websocket.ping_interval', 25000);
        $this->pingTimeout  = $this->config->get('swoole.websocket.ping_timeout', 60000);
    }

    /**
     * 受用 WebSocket 客户端连接入口
     * 页面刷新后，可能会建立新的链接，此时应注意如何与旧的链接对应
     *
     * @param mixed $args [fd service request]
     * @return mixed
     */
    public function onOpen($array_func_args)
    {
        // 获取事件参数
        [$this->fd, $this->service, $this->request] = $array_func_args;

        // 防止时间太长
        $this->reset();
        /**
         * 返回已连接明细
         * @return JSON
         */
        $this->service->to($this->fd)->push(Response::create([
            'type' => 'connected'
        ]));
    }

    /**
     * 设置超时定时器
     *
     * @return void
     */
    private function reset()
    {
        // 清除之前的超时
        Timer::clear($this->pingTimeoutTimer);
        // 超时时间
        $timeout = $this->pingInterval + $this->pingTimeout;
        // 如果超时
        $this->pingTimeoutTimer = Timer::after($timeout, function () {
            $this->service->close();
        });
    }

    /**
     * 受用 WebSocket 客户端发送消息
     *
     * @param mixed $array_func_args
     * @return mixed
     */
    public function onMessage($array_func_args)
    {
        // 获取事件参数
        [$frame, $this->service] = $array_func_args;

        $this->fd = $frame->fd;
        $request = Request::load($frame);
        if ($request->type == null) {
            throw new HttpException('Message parse error');
        }

        switch($request->type) 
        {
            case Request::CONNECTED:
                $this->onConnect($request, $frame);
                break;

            case Request::BOARDCAST:
                $this->onBoardCast($request);
                break;

            case Request::NOTICE:
                $this->onNotice($request);
                break;
            
            case Request::DEFAULT_EVENT:
                $this->onSend($request);
                $this->fd($request->data['sid'], $frame->fd);
                break;

            default:
                break;
        }

        // 清除定时器
        // Timer::clear($this->pingIntervalTimer);
        // 定时发送心跳
        // $this->pingIntervalTimer = Timer::after($this->pingInterval, function () {
        //     $this->reset();
        //     $this->ping();
        // });
    }

    /**
     * 受用 WebSocket 客户端关闭链接
     *
     * @param mixed $array_func_args
     * @return mixed
     */
    public function onClose($array_func_args)
    {
        // 获取事件参数
        [$this->fd, $this->service, $reactorId] = $array_func_args;
        // 房间列表
        $rooms = $this->service->room->getRooms($this->fd);
        // 退出房间
        $this->service->leave($rooms);
    }

    /**
     * 返回缓存名称
     *
     * @param string $str
     * @return void
     */
    private function getName($str) {
        return 'sw_' . $str;
    }

    /**
     * 发送心跳
     *
     * @return void
     */
    public function ping($service)
    {
        // 获取事件参数
        $service->to($service->getSender())->push(Response::create([
            'type' => 'ping'
        ]));
    }

    /**
     * 建立链接(会话载入时进行一次)
     *
     * @param object $request
     * @param object $frame
     * @return void
     */
    public function onConnect($request, $frame)
    {
        // POST.CONNECTED { sid: 'wx2394123', room: 'online' }
        // 已有旧的链接
        if (isset($request->data['sid'])) {
            // 旧的连接socket对应的文件描述符ID
            $fd1 = $this->fd($request->data['sid']);
            if ($fd1 && $fd1 != $frame->fd) {
                // 房间列表
                $rooms = $this->service->room->getRooms($fd1);
                // 退出房间
                $this->service->room->delete($fd1, $rooms);
                // 重新进入
                $this->service->join($rooms);
            }
            // 更新描述符ID
            $this->fd($request->data['sid'], $frame->fd);
        } else {
            // 获取唯一UID
            $request->data['sid'] = uniqid() . '' . date('His');
            // 更新描述符ID
            $this->fd($request->data['sid'], $frame->fd);
        }

        // 当前已进入的房间
        $rooms = $this->service->room->getRooms($frame->fd);
        // 无指定房间名,且未进入房间
        if (!isset($request->data['roomName'])) {
            if (empty($rooms)) {
                $request->data['roomName'] = self::DEFAULT_ROOM;
            }
        }

        // 进入新的房间
        if (isset($request->data['roomName'])) {
            // 进入房间
            $this->service->join($request->data['roomName']);
        }

        // 发送消息
        $this->service->to($this->service->getSender())->push(Response::create([
            'type' => 'articulate',
            'data' => [
                'interval' => $this->pingInterval, // 心跳时间
                'timeout' => $this->pingTimeout, // 超时时间(超过多长时间就关闭链接)
                'sid' => $request->data['sid']
            ]
        ]));
    }

    /**
     * 发送群通知
     *
     * ------------------------------------------------
     * type: notice, data: { sid: 'wx2394123', room: 'online', message: 'hello world!' }
     * ------------------------------------------------
     * @param object $request
     * @return void
     */
    public function onNotice($request)
    {
        $clients = $this->room->getClients($request->data['roomName']);
        $this->service->to($clients)->push(Response::create([
            'type' => 'notice',
            'data' => $request->data['message']
        ]));
    }

    /**
     * 发送广播消息
     *
     * ------------------------------------------------
     * type: boardcast, data: { sid: 'wx2394123', room: 'online', message: 'hello world!' }
     * ------------------------------------------------
     * @param object $request
     * @return void
     */
    public function onBoardCast($request)
    {
        if (isset($request->data['roomName'])) {
            $clients = $this->room->getClients($request->data['roomName']);
            $service = $this->service->to($clients);
        } else {
            $service = $this->service->broadcast();
        }

        $service->push(Response::create([
            'id' => $this->fd,
            'message' => 'boardcast',
            'data' => $request->data['message']
        ]));
    }

    /**
     * 点对点发送消息
     *
     * @param [type] $request
     * @return void
     */
    public function onSend($request)
    {
        $this->service->to($this->fd($request->data['to']))->push(Response::create([
            'id' => $this->fd,
            'message' => 'boardcast',
            'data' => $request->data['message']
        ]));
    }
}
