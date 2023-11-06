<?php
namespace mashroom\exception;
/*
 * 验证错误
 * @Author: k
 */

class ValidateException extends HttpException
{
    /**
     * 未登录
     *
     * @var array
     */
    private $login = [50008, 50014, 50012];

    public function __construct(string $message, int $code)
    {
        if (!IS_AJAX) {
            if (in_array($code, $this->login)) {
                redirect(url("sysLogin"))->send();
                die;
            }
        }

        parent::__construct($message, $code);
    }
}
