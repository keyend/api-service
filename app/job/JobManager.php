<?php
namespace app\job;
/**
 * 消费队列
 * 
 * @version 1.0.0
 * @suggest topthink/think-queue
 */
use think\queue;
use think\queue\Job;
use think\facade\Log;

class JobManager
{
    protected static $name = 'JobManager_101';

    /**
     * 消费消息
     *
     * @param Job $job
     * @param array $data
     * @return void
     */
    public function fire(Job $job, array $data)
    {
        $job->delete();

        if (!isset($data["require"])) {
            Log::error("[FAIL]消息队列运行 => " . json_encode($data));
        }

        $release = false;
        if (isset($data["loop"])) {
            $release = true;
            if (!isset($data["times"])) $data["times"] = 0;
            if (is_numeric($data["loop"])) {
                if ($data["loop"] <= $data["times"]) {
                    $release = false;
                }
            }
            if (is_array($data["loop"])) {
                if (is_string($data["loop"])) {
                    $link = redis()->get($data["loop"]) ?? false;
                    if ($link == false) {
                        $release = false;
                    }
                }
            }
            $data["times"] += 1;
        }

        if (!class_exists($data["require"])) {
            Log::error("消费队列失败 => class not exists::{$data['require']}");
            return;
        }

        $argv = isset($data["argv"]) ? $data["argv"] : [];
        if (!is_array($argv)) $argv = [$argv];
        try {
            call_user_func_array(
                [app()->make($data["require"], (isset($data["params"]) ? data["params"] : [])), $data["method"]], 
                $argv
            );
        } catch(Exception $e) {
            Log::error("消费队列失败 => {$e->getMessage()}");
            return;
        }

        if ($release && isset($data['id'])) {
            $isStoped = redis()->get("queue.{$data['id']}");
            if (empty($isStoped)) {
                redis()->decr("heartbeat");
                $release = false;
            }
        }

        if ($release) {
            self::push($data);
        }
    }

    /**
     * 添加消息
     *
     * @param mixed $params
     * @return void
     */
    public static function push($params = [])
    {
        queue(__CLASS__, $params, isset($params["delay"]) ? $params["delay"] : 0, self::$name);
    }

    /**
     * 异步不等待请求
     *
     * @param [type] $url
     * @param integer $time
     * @return void
     */
    public function get($url, $params = [])
    {
        if (!empty($params)) {
            if (strpos($url, '?') !== false) {
                $url .= "&" . http_build_query($params);
            } else {
                $url .= "?" . http_build_query($params);
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_exec($ch);
    }

    /**
     * 异步不等待请求
     *
     * @param [type] $url
     * @param integer $time
     * @return void
     */
    public function post($url, $params = [])
    {
        $client = new \GuzzleHttp\Client();
        $client->request('POST', $url, $params);
    }
}