<?php
/**
 * Api控制器基础类
 * 
 * @version 1.0.0
 */
namespace app\api;

class Index extends ApiBase 
{
    public function test()
    {
        return $this->success();
    }
}