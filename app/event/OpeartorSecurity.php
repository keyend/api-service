<?php
namespace app\event;

/**
 * 管理密码变更
 * 
 * @version 1.0.0
 */
class OpeartorSecurity
{
    public function handle($user = [])
    {
        if (!empty($user)) {
            redis()->delete("usr.{$user['SESSION_ID']}");
        }
    }
}