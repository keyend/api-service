<?php
namespace mashroom\exception;
/*
 * 用户错误
 * @Author: k
 */
class UserException extends HttpException
{
    public function __construct(string $message = '', $code = 40001)
    {
        parent::__construct($message, $code);
    }
}
