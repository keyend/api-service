<?php
namespace app\event;
/**
 * 心跳
 * 
 * @version 1.0.0
 */
use think\facade\Log;
use app\job\JobManager;

class HeartBeat
{
    const name = 'heartbeat';
    const limit = 2;
    const delay = 2;

    /**
     * 添加一条心跳
     *
     * @return void
     */
    private function push()
    {
        $serialize_id = uniqid();
        JobManager::push([
            'id'      => $serialize_id,
            'require' => JobManager::class,
            'method'  => "get",
            "argv"    => conf("basic.domain", request()->domain()) . "/cron/index.html",
            "delay"   => self::delay,
            "loop"    => "queue.{$serialize_id}"
        ]);
        redis()->tag("config")->set("queue.{$serialize_id}", 1);
        redis()->incr(env("cache.prefix", "") . self::name);
        Log::info("添加心跳定时器 => {$serialize_id}");
    }

    /**
     * 缓存名称
     *
     * @return void
     */
    private function getName() 
    {
        return env("cache.prefix", "") . self::name;
    }

    public function handle()
    {
        if (defined('MODULE') && MODULE == "cron") {
            return;
        }

        $length = redis()->get($this->getName()) ?? 0;
        if (self::limit < $length) {
            $this->push();
        }
    }
}