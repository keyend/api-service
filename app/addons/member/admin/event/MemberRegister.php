<?php
namespace app\addons\member\admin\event;
use app\addons\member\admin\model\Member;
use app\addons\member\admin\model\MemberLevel;
use think\facade\Log;

class MemberRegister
{
    public function handle($param = [])
    {
        $level = MemberLevel::order('level ASC,is_default DESC')->find();
        $member = Member::find($param['member_id']);
        $member->level_id = $level->level;
        $member->levelname = $level->levelname;
        $member->save();
        $level->total_num = $level->total_num + 1;
        $level->save();
        Log::info("会员 {$member->member_id} 设置默认等级 {$level->levelname}");
    }
}