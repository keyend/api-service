<?php
namespace mashroom\service;
/**
 * SOCKET服务
 * 
 * @author k. <email@email.com>
 * @version 1.0.0
 */
use think\Container;
use think\Request;

class WebsocketService
{
    /**
     * think\App
     * @var std
     */
    protected $app;

    /**
     * SOCKET
     * @var std
     */
    protected $service;

    /**
     * REQUEST
     * @var std
     */
    protected $request;

    /**
     * payload
     * @var Array
     */
    protected $payload;


    /**
     * 房间
     * @var std
     */
    protected $room;

    /**
     * 令牌号
     * @var string
     */
    protected $token;
    
    /**
     * 缓存对象
     *
     * @var cache.driver
     */
    protected $cache;

    /**
     * 当前时间戮
     *
     * @var Array
     */
    protected $timestamp;

    // 默认返回消息类型
    const DEFAULT_EVENT = 'message';

    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->timestamp = time();
    }

    /**
     * Token值加入时间戮
     * @access protected
     * @param Array $user
     * @param integer $expire 到期时间
     * @return void
     */
    protected function assignToken($user, $expire)
    {
        return implode(".", [$user['token'], $user['id'], $user['fd'], $this->timestamp + $expire]);
    }

    /**
     * 解析Token队列值
     * @access protected
     * @param string $val
     * @return void
     */
    protected function parseToken($val)
    {
        $val = explode(".", $val);

        return [
            'fd' => $val[2],
            'id' => $val[1],
            'token' => $val[0],
            'expire' => $val[2]
        ];
    }

    /**
     * 发送消息
     * @access protected
     * @param Array ...$args
     * @return void
     */
    protected function emit(...$args)
    {
        $length = count($args);
        foreach($args as $i => $arg) {
            if (is_string($arg) && $length > 0 && !isset($event)) {
                $event = current(array_splice($args, $i, 1));
            }
        }

        if (!isset($event)) $event = self::DEFAULT_EVENT;

        return $this->service->emit($event, ...$args);
    }

    /**
     * 设置更新匹配令牌
     * @access protected
     * @param string $token
     * @return void
     */
    protected function match($token)
    {
        $tokenName = "usr.{$token}";
        $user = redis()->get($tokenName);
        // 未登录账户无法完成匹配
        if (!$user) return false;
        // 更新client_id
        $user['fd'] = $this->service->getSender();
        $user['heartbeat_time'] = $this->timestamp;
        // 刷新登录明细
        $expire = config('common.user_logged_stay', 1) * 3600;
        redis()->set($tokenName, $user, $expire);

        return $user;
    }

    /**
     * 获取新的令牌
     * @access protected
     * @return void
     */
    protected function getToken(Request $request)
    {
        if (!$this->token) {
            $token = $request->cookie('token');
            if (!$token) {
                $token = $request->param('token');
                if (!$token) $token = $request->header('SAM-Token');
            }

            // 验证并查找匹配加入访问列表
            if (!$token || !($user = $this->match($token))) {
                return false;
            }

            $this->updateQueue();
            $this->appendQueue($user);
            $this->token = $token;
        }

        return $this->token;
    }

    /**
     * 加入访问列表
     * @access protected
     * @param Array $user
     * @return void
     */
    protected function appendQueue($user)
    {
        foreach($this->getQueues() as $queue) {
            $item = $this->parseToken($queue);
            if ($item['token'] === $user['token']) {
                $this->cache->srem($queue);
                break;
            }
        }
        // 超时时间
        $expire = config('common.user_logged_stay', 1) * 3600;

        return $this->cache->sAdd(redis()->getCacheKey("usr.list"), $this->assignToken($user, $expire));
    }

    /**
     * 更新访问列表
     * @access protected
     * @return array
     */
    protected function updateQueue()
    {
        // 更新访问列表
        $currentTime = time();
        $rows = [];

        foreach($this->getQueues() as $queue) {
            $item = $this->parseToken($queue);
            if ($item['expire'] < $currentTime) {
                $this->cache->srem($queue);
            } else {
                $rows[] = $item;
            }
        }

        return $rows;
    }

    /**
     * 列表去除
     * @access protected
     * @return void
     */
    protected function removeQueue($token)
    {
        foreach($this->getQueues() as $queue) {
            $item = $this->parseToken($queue);
            if ($item['token'] === $token) {
                $this->cache->srem($queue);
                break;
            }
        }
    }

    /**
     * 返回访问列表
     * @access protected
     * @return void
     */
    protected function getQueues()
    {
        if (!$this->cache) {
            $this->cache = redis()->handler();
        }

        return $this->cache->sMembers(redis()->getCacheKey("usr.list"));
    }

    /**
     * 获取client_id列表
     * @access public
     * @param array $fds
     * @return void
     */
    public function getFds($ids)
    {
        $res = [];
        foreach($this->getQueues() as $queue) {
            $item = $this->parseToken($queue);
            // 验证队列里面的已登录的客户
            if (in_array($item['id'], $ids)) {
                $res[] = $user['fd'];
            }
        }

        return $res;
    }

    /**
     * 获取映射表对应的令牌
     *
     * @param string $fd
     * @return void
     */
    public function getTokenByFd($fd)
    {
        foreach($this->getQueues() as $queue) {
            $item = $this->parseToken($queue);
            if ($item['fd'] == $fd) {
                return $item['token'];
            }
        }

        return null;
    }
}
