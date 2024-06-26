<?php
/**
 * APIv2
 * 
 * @version 1.0.0
 */
namespace app\controller\api;

class V2 extends ApiBase 
{
    public function test()
    {
        return redirect('/api/v2/test/index');
    }
}