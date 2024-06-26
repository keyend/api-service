<?php
namespace app\controller\admin;
/**
 * 缓存
 * 
 * @version 1.0.0
 */
use think\facade\Cache as ThinkCache;
use app\model\system\User;

class Cache extends Controller
{
    /**
     * 首页
     *
     * @return void
     */
    public function clear()
    {
        if ($this->request->isAjax()) {
            if (!ThinkCache::clear()) {
                return $this->error();
            }
            if (defined('S1')) {
                $auth = User::find(S1);
                $userData = $auth->getUserData();
                $this->request->login($userData);
            }
            return $this->success();
        }
    }
}