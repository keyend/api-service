<?php
namespace app\addons\member\admin\controller;
/**
 * 会员管理
 * 
 * @version 1.0.0
 */
use app\controller\admin\Controller;
use app\addons\member\admin\model\Member as MemberModel;

class Member extends Controller
{
    /**
     * 会员列表
     *
     * @param MemberModel $member
     * @return void
     */
    public function lists(MemberModel $member)
    {
        if (IS_AJAX) {

        } else {
            return $this->fetch();
        }
    }
}