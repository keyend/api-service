<?php
namespace app\model\protocol;

use think\facade\Log;
use app\Model;
use app\model\member\Member;

class Apps extends Model
{
    protected $name = 'member_apps';

    /**
     * 关联订单
     *
     * @return void
     */
    public function member()
    {
        return $this->hasOne(Member::class, 'member_id', 'member_id');
    }

    /**
     * 返回对应会员
     *
     * @param array $condition
     * @return void
     */
    public function getMemberInfo($condition = [])
    {
        $app = self::where($condition)->find();

        return $app->member;
    }

    /**
     * 签名
     *
     * @param array $param
     * @return void
     */
    public function sign($param)
    {
        $arr = [];
        foreach($param as $key => $value) {
            if ($value !== '' && $key != 'sign') {
                $arr[$key] = $value;
            }
        }
        ksort($arr);
        $arr['key'] = $this->app_key;
        $signString = http_build_query($arr);
        Log::info("将对 {$signString} 签名");
        $sign = md5($signString);
        Log::info("签名结果 -> {$sign}");

        return $sign;
    }

    /**
     * 签名验证
     *
     * @param array $param
     * @return boolean
     */
    public function isSign($param = [])
    {
        $sign = $param['sign'];

        return $this->sign($param) == $sign;
    }
}