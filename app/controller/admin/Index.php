<?php
namespace app\controller\admin;
/**
 * 管理中心首页
 * 
 * @version 1.0.0
 */

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }
}