<?php
namespace app\addons\member\admin\event;
/**
 * 会员更新
 * 
 * @version 1.0.0
 */
use app\addons\member\admin\model\Member;
use app\addons\member\admin\model\MemberLevel;

class MemberUpdate
{
    public function handle($params = [])
    {
        [ $before, $after ] = $params;
        //上级会员发生变动
        if ($before['parent_id'] != $after['parent_id']) {
            (new Member())->switchReferrals($after['member_id'], $before['parent_id'], $after['parent_id']);
        }
        //会员级别发生变动
        if ($before['level_id'] != $after['level_id']) {
            MemberLevel::where('level', $before['level_id'])->dec('total_num')->update();
            MemberLevel::where('level', $after['level_id'])->inc('total_num')->update();
        }
    }
}