<?php
namespace app\model\member;
/**
 * 会员套餐
 * @version 1.0.0
 */
use app\Model;
use app\model\system\Ip;
use think\facade\Db;

class Combo extends Model
{
    protected $name = 'member_combo';
}
