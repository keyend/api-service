<?php
namespace app\model\system;
/**
 * 消息队列
 *
 * @package app.common.model
 * @author: k.
 * @date: 2021-05-10 20:19:31
 */
use app\Model;
use think\facade\Db;

class Cron extends Model
{
    protected $name = 'sys_jobs';
    protected $pk = 'id';

    /**
     * 自动序列化
     *
     * @param [type] $value
     * @param [type] $data
     * @return void
     */
    public function getPayloadAttr($value, $data)
    {
        return unserialize($value);
    }

    /**
     * 发布消息
     *
     * @param string  $queue 队列名称
     * @param array   $argv  消息明细
     * @param integer $later 有效执行时间
     * @param integer $limit 执行次数
     * @return void
     */
    public function addCron($queue = '', $argv, $later = 0, $limit = 1, $type = 1)
    {
        return $this->insert([
            'type'      => $type,
            'queue'     => $queue,
            'payload'   => serialize($argv),
            'reserved'  => $limit,
            'available_at' => TIMESTAMP + $later,
            'created_at' => TIMESTAMP
        ]);
    }

    /**
     * 返回一个在时间范围内可执行的队列
     * 1) 有效时间不超过当前时间
     * 2) 有订阅次数的
     * @return void
     */
    public function getSubscribe()
    {
        return $this->order("id ASC,reserved_at ASC")->where([ ["available_at", "<=", TIMESTAMP], ["reserved", ">", 0] ])->field("id,queue,payload")->find();
    }
}