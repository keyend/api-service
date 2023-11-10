<?php
namespace app\controller\admin;
/**
 * 缓存
 * 
 * @version 1.0.0
 */
use think\facade\Cache as ThinkCache;

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
            return $this->success();
        }
    }
}