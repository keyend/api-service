<?php
namespace mashroom\exception;
/*
 * 用户不存在
 * @Author: k
 */
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Throwable;

class UserNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
    public function __construct(string $message, Throwable $previous = null)
    {
        $this->message = $message;
        parent::__construct($message, 40081, $previous);
    }
}
