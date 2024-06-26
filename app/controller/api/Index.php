<?php
/**
 * Api控制器基础类
 * 
 * @version 1.0.0
 */
namespace app\controller\api;

class Index extends ApiBase 
{
    public function index()
    {
        return $this->success();
    }
}