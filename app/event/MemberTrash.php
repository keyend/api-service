<?php
namespace app\event;
/**
 * package: app.event
 * 会员注销事件
 * 
 * @version 1.0.0
 */
use app\model\member\Member as MemberModel;
class MemberTrash
{
    public function handle($member = [])
    {
        $member_model = new MemberModel();
        if (!empty($member_model->where('parent_id', $member['member_id'])->field("member_id")->find())) {
            throw new \Exception("存在子级会员!");
        }
    }
}