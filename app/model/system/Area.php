<?php
namespace app\model\system;
/**
 * 区域
 *
 * @package app.common.model
 * @date: 2021-05-10 20:19:31
 */
use app\Model;

class Area extends Model
{
    /**
     * 表名
     *
     * @var string
     */
    protected $name = 'sys_area';

    /**
     * 返回地区列表
     *
     * @param integer $page
     * @param integer $limit
     * @param array $condition
     * @param string $field
     * @return void
     */
    public function getList(int $page, int $limit, $condition = [], $field = '*')
    {
        $list = $this->where($condition)
        ->field($field)
        ->page($page, $limit)
        ->order('sort ASC')
        ->column($field, "id");
        $sql = $this->getLastSql();
        $count = count($list);
        return compact('count', 'list', 'sql');
    }

    /**
     * 拼音转换
     *
     * @param string $str
     * @return void
     */
    private function pinyin($str)
    {
        $pinyin = app()->make(\Overtrue\Pinyin\Pinyin::class);
        $result = $pinyin->convert($str);
        return implode(' ', $result);
    }

    /**
     * 添加区域
     *
     * @param array $data
     * @return void
     */
    public function addArea($data = [])
    {
        $data['pinyin'] = $this->pinyin($data['areaname']);
        $data['pinyin_char'] = strtoupper(substr($data['pinyin'], 0, 1));
        $data['id'] = $this->insertGetId($data);
        if (!empty($data['zipcode'])) {
            $this->synchronousZipCode($data);
        }
        $this->logger('logs.sys.area.create', 'CREATEED', $data);
        return $data;
    }

    /**
     * 更新区域
     *
     * @param array $data
     * @return void
     */
    public function editArea($data = [])
    {
        $originData = $this->getData();
        $data['pinyin'] = $this->pinyin($data['areaname']);
        $data['pinyin_char'] = strtoupper(substr($data['pinyin'], 0, 1));
        $this->save($data);
        $data = $this->getData();
        if ($data['zipcode'] != $originData['zipcode']) {
            $this->synchronousZipCode($data);
        }
        $this->logger('logs.sys.area.edit', 'UPDATED', [$originData, $data]);
        return $data;
    }

    /**
     * 返回匹配邮编
     *
     * @return void
     */
    public function getZipcode()
    {
        $data = $this->getData();
        if (!empty($data["zipcode"])) {
            $code = $data["zipcode"];
        } else {
            $address = $this->getFullAddress($data);
            $zipcode = (new Zipcode())->getZipcode($address);
            $code = $zipcode['code'] ?? '';
            if (!empty($zipcode)) {
                $this->zipcode = $zipcode['code'];
                $this->save();
            }
        }

        return $code;
    }

    /**
     * 获取完整地址
     *
     * @param array $data
     * @return void
     */
    private function getFullAddress($data = [])
    {
        $level = (int)$data['level'];
        $parentid = $data['parentid'];
        $result = [];
        $result[$level] = $data['areaname'];
        $deep = 0;
        $central = false;
        while($parentid > 0) {
            $parent = $this->where('id', '=', $parentid)->find();
            if (empty($parent) || $deep > 5) {
                break;
            }
            $level = (int)$parent['level'];
            $parentid = $parent['parentid'];
            $result[$level] = $parent['areaname'];
            $result[$level + 10] = $parent['id'];
            $result[$level + 20] = [$parent['longitude'], $parent['latitude']];
            $deep += 1;
        }
        if (in_array($result[1], ['北京', '上海', '天津', '重庆'])) {
            $result[1] = $result[2];
            $central = true;
        }
        $area = [
            'province' => $result[1] ?? '',
            'province_id' => $result[11] ?? 0,
            'city' => $result[2] ?? '',
            'city_id' => $result[12] ?? 0,
            'district' => $result[3] ?? '',
            'district_id' => $result[13] ?? 0,
            'street' => $result[4] ?? '',
            'street_id' => $result[14] ?? 0,
        ];

        if ($area['street_id'] !== 0) {
            $area['longitude'] = $result[24][0] ?? 0;
            $area['latitude'] = $result[24][1] ?? 0;
        } elseif($area['district_id'] !== 0) {
            $area['longitude'] = $result[23][0] ?? 0;
            $area['latitude'] = $result[23][1] ?? 0;
        } elseif($area['city_id'] !== 0) {
            $area['longitude'] = $result[22][0] ?? 0;
            $area['latitude'] = $result[22][1] ?? 0;
        } else {
            $area['longitude'] = $result[21][0] ?? 0;
            $area['latitude'] = $result[21][1] ?? 0;
        }

        ksort($result);
        if ($central) {
            unset($result[1]);
        }

        $area['address'] = implode('', $result);
        if (empty($area['province']) || empty($area['city']) || empty($area['district'])) {
            return [];
        }
        return $area;
    }

    /**
     * 同步区域邮编
     *
     * @param array $data
     * @return void
     */
    private function synchronousZipCode($data = [])
    {
        $address = $this->getFullAddress($data);
        if (empty($address)) {
            return false;
        }

        $zipcode = null;
        if (!empty($data['zipcode'])) {
            $zipcode = Zipcode::where([ ['zipcode', '=', $data['zipcode']] ])->find();
        }

        if (empty($zipcode)) {
            $zipcode = (new Zipcode())->getZipcode($address);
        }

        $data = [
            'code' => $data['zipcode'],
            'province' => $address['province'],
            'city' => $address['city'],
            'district' => $address['district'],
            'address' => $address['address'],
            'area' => $address['area']
        ];

        if (empty($zipcode)) {
            $this->logger('logs.sys.zipcode.add', 'CREATEED', $data);
            Zipcode::create($data);
        } else {
            if (empty($data['code'])) {
                $this->logger('logs.sys.zipcode.delete', 'DELETE', $zipcode);
                $zipcode->delete();
            } else {
                $this->logger('logs.sys.zipcode.edit', 'UPDATED', $data);
                $zipcode->save($data);
            }
        }
    }
}