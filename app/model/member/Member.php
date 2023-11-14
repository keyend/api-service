<?php
namespace app\model\member;
/**
 * 会员
 * @version 1.0.0
 */
use app\Model;

class Member extends Model
{
    protected $name = 'member';

    /**
     * 获取器.标签
     *
     * @param mixed $value
     * @return void
     */
    public function getLabelAttr($value)
    {
        return Label::where([ ['label_id', 'IN', $value] ])->select();
    }

    /**
     * 保存当前数据对象
     * @access public
     * @param array  $data     数据
     * @param string $sequence 自增序列名
     * @return bool
     */
    public function save(array $data = [], string $sequence = null): bool
    {
        $data['update_time'] = TIMESTAMP;
        return parent::save($data, $sequence);
    }
}