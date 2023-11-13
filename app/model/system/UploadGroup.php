<?php
namespace app\model\system;
/*
 *********************************************************************
 * 应用列表
 * @package app.common.model
 * @Author: k.
 * @Date: 2021-05-10 20:19:31
 * 
 * 分组说明
 * 
 * 前后端管理分离查看管理图片
 *********************************************************************
 */
use app\Model;

class UploadGroup extends Model
{
    protected $name = 'sys_upload_group';
    protected $pk = 'id';

    /**
     * 返回列表
     *
     * @param integer $page
     * @param integer $limit
     * @param array $filter
     * @return array
     */
    public function getList(int $page = 1, int $limit = 9999, array $filter = [])
    {
        // 倒序
        $query = self::where($filter)->order('id ASC');
        // 记录条数
        $count = $query->count();
        // 获取所有记录
        $list = $query->page($page, $limit)->select()->toArray();

        return compact('count', 'list');
    }

    /**
     * 返回分组列表
     *
     * @param integer $type
     * @return void
     */
    public function getAlbumList($type = 0)
    {
        return $this->getList(1, 100, [['user_id', '=', S1], ['type', '=', $type]]);
    }

    /**
     * 返回默认分组
     *
     * @param integer $type
     * @return void
     */
    public function getDefaultGroup($type = 0)
    {
        $this->createGroup([
            'type' => $type,
            'user_id' => S1,
            'group_name' => '默认',
            'is_default' => 1,
            'filecount' => 0
        ]);
        return $this->getAlbumList($type);
    }

    /**
     * 返回前端默认分组
     *
     * @param integer $type
     * @return void
     */
    public function getAlbumDefaultGroup($type = 0)
    {
        return $this->where('type', '=', $type)->where('group_name', '=', '默认')->order('id ASC')->find();
    }

    /**
     * 创建分组
     *
     * @param array $data
     * @return void
     */
    public function createGroup($data = [])
    {
        $data['id'] = self::insert($data);
        return $data;
    }

    /**
     * 返回临时分组
     *
     * @return void
     */
    public function getTempGroup()
    {
        $system_id = UserModel::where("parent_id", 0)->value("user_id");
        $result = self::where([['group_name', 'LIKE', '%临时%'], ["user_id", '=', $system_id]])->find();
        if (!$result) {
            $result = $this->createGroup([
                "group_name" => "临时图库",
                "user_id" => $system_id
            ]);
        }

        return $result;
    }

    /**
     * 增加分组
     *
     * @param array $condition
     * @param array $data
     * @return void
     */
    public function addGroup($condition = [], $data = [])
    {
        if (self::where($condition)->find()) {
            throw new \Exception("已存在相似分组");
        }

        $data['user_id'] = S1;
        return $this->createGroup($data);
    }

    /**
     * 刷新分组文件数量
     *
     * @param array $ids
     * @return void
     */
    public function refresh($ids = [])
    {
        foreach($ids as $group_id) {
            $filecount = Upload::where([ ['group_id', '=', $group_id] ])->count();
            $this->where([ ['id', '=', $group_id] ])->update([ 'filecount' => $filecount]);
        }
    }
}
