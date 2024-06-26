<?php
/**
 * 身份验证接口实现
 * 
 * @version 1.0.0
 */
namespace app\controller\api\v2;
use app\controller\api\ApiBase;

class Idcard extends ApiBase 
{
    /**
     * 验证姓名和身份证号
     *
     * @return void
     */
    public function auth()
    {
        return $this->success([
            'ret1' => 1,
            'ret2' => 2
        ]);
    }

    /**
     * 身份证四要素验证
     *
     * @return void
     */
    public function consistent()
    {
        return $this->success([
            'ret1' => 1,
            'ret2' => 2
        ]);
    }

    /**
     * 人像比对
     *
     * @return void
     */
    public function compare()
    {
        return $this->success([
            'ret1' => 1,
            'ret2' => 2
        ]);
    }
}