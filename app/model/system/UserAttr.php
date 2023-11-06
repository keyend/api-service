<?php
namespace app\model\system;
/*
 * 用户属性
 * @package app.common.model
 * @Author: k.
 * @Date: 2021-05-10 20:19:31
 */
use app\Model;

class UserAttr extends Model
{
    protected $name = 'sys_user_attr';
    protected $pk = 'attr_id';

    /**
     * 权限明细
     * @collection relation.model
     */
    public function extends() {
        return $this->hasMany(UserExtend::class, 'attr_id', 'attr_id');
    }

    /**
     * 获取应用下的用户扩展字段
     *
     * @param string $app
     * @return Array
     */
    public function getList()
    {
        return self::field('attr_id,attr,type,title,required')->select()->toArray();
    }
}
