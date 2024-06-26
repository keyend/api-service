<?php
namespace app\controller\admin;
/**
 * 管理中心首页
 * 
 * @version 1.0.0
 */
use app\model\system\Rule;
use app\model\system\User;

class Index extends Controller
{
    /**
     * 首页
     *
     * @param Rule $rule
     * @return void
     */
    public function index(Rule $rule, User $user)
    {
        $this->assign("config", gc("index"));
        return $this->fetch();
    }
}