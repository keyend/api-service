<?php
namespace mashroom\exception;
/*
 * 代码错误
 * @Author: k
 */

class HttpException extends \think\exception\HttpException
{
    public function __construct(string $message = '', $code = 50001, $httpCode = 200)
    {
        parent::__construct($httpCode, $message, null, [], $code);
    }
}
