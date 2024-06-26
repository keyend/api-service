<?php
namespace app\model\system;
/**
 * IP地址库
 *
 * @package app.common.model
 * @date: 2021-05-10 20:19:31
 */
use app\Model;

class Ip extends Model
{
    /**
     * 表名
     *
     * @var string
     */
    protected $name = 'sys_area_ips';

    /**
     * 返回列表
     *
     * @return void
     */
    public function getList(int $page = 1, int $limit = 20, $condition = [])
    {
        $query = $this->where($condition)->order('id DESC');
        $count = $query->count();
        $list = $query->order('id DESC')->page($page, $limit)->select()->toArray();
        return compact('count', 'list');
    }

    /**
     * 从IP地址获取区域
     *
     * @param [type] $ip
     * @return void
     */
    public function getAddress($ip)
    {
        $city =  self::where('ip', $ip)->value('city');
        if (!empty($city)) {
            $area = new Area();
            $data = $area->where('areaname', 'like', '%{$city}%')->find();
            if (!empty($data)) {
                return $area->getFullAddress($data);
            }
        }
    }

    /**
     * 添加邮编
     *
     * @param array $data
     * @return void
     */
    public function addIp($data = [])
    {
        $data['id'] = $this->insertGetId($data);
        return $data;
    }
}