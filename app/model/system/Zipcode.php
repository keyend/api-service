<?php
namespace app\model\system;
/**
 * 区域邮编
 *
 * @package app.common.model
 * @date: 2021-05-10 20:19:31
 */
use app\Model;

class Zipcode extends Model
{
    /**
     * 表名
     *
     * @var string
     */
    protected $name = 'sys_area_zipcode';

    /**
     * 获取匹配区域邮编
     *
     * @param array $address
     * @return void
     */
    public function getZipcode($address = [])
    {
        $res = $this->where([ ['province', '=', $address['province']],  ['city', '=', $address['city']], ['district', '=', $address['district']], ['address', '=', $address['street']] ])
        ->find();
        return $res;
    }

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
     * 添加邮编
     *
     * @param array $data
     * @return void
     */
    public function addZipcode($data = [])
    {
        $data['area'] = ($data['province'] != $data['city'] ? $data['province'] : '') . "{$data['city']}{$data['district']}{$data['address']}";
        $data['id'] = $this->insertGetId($data);
        return $data;
    }
}