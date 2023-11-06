<?php
namespace app\controller\cron;
/**
 * 异步任务执行器
 * 
 * @version 1.0.0
 */
use app\BaseController;
use app\model\system\Cron;

class Index extends BaseController
{
    /**
     * 订阅消息
     *
     * @param [type] $cron
     * @return void
     */
    private function execute($cron)
    {
        $payload = $cron->payload;
        if ($cron["type"] == 1) {
            if (is_array($payload)) {
                [$class, $method] = explode("::", $payload[0]);
                JobManager::push([
                    'require' => $class,
                    'method'  => $method,
                    "argv"    => $payload[1]
                ]);
            } else {
                if (strpos($payload[0], "::") !== FALSE) {
                    [$class, $method] = explode("::", $payload[0]);
                    $argv = $payload[1];
                    app()->make($class)->$method($argv);
                } else {
                    $class = $payload[0];
                    $method = $payload[1];
                    app()->make($class)->$method();
                }
            }
        } elseif($cron["type"] == 2) {
            if (is_array($payload)) {
                event($cron["event"], $cron["param"] ?? []);
            } else {
                event($payload);
            }
        }
    }

    /**
     * 自动消费队列
     *
     * @param Cron $cron_model
     * @return void
     */
    public function index(Cron $cron)
    {
        ignore_user_abort(true);
        set_time_limit(0);
        Log::write("事件执行");
        $lastTimestamp = (int)redis()->get("cron_timestamp");
        if ($lastTimestamp - TIMESTAMP < 1) {
            exit();
        }
        redis()->tag("config")->set("cron_timestamp", TIMESTAMP);
        $cron = $cron->getSubscribe();
        if (!empty($cron)) {
            $cron->reserved_at = TIMESTAMP;
            $cron->reserved = Db::raw('reserved-1');
            $cron->attempts = Db::raw('attempts+1');
            $cron->save();
            $this->execute($cron);
        }
    }
}