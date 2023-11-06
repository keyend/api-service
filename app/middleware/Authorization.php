<?php
namespace app\middleware;
/*
 * 管理权限登录校验中间件
 * 
 * @Date: 2020-11-10
 * @version 1.0.0
 */
use mashroom\middleware\BaseMiddleware;
use mashroom\exception\ValidateException;
use app\model\system\User;
use app\model\system\Rule;

class Authorization extends BaseMiddleware
{
    /**
     * 权限验证
     *
     * @param Request $request
     * @return boolean
     */
    private function checkAccess($request)
    {
        $ruleName = $request->rule()->getName();
        if (!empty($ruleName)) {
            return app()->make(Rule::class)->check($ruleName, $request->user);
        }
        return true;
    }

    /**
     * Undocumented function
     *
     * @param Request $request
     * @param \Closure $next
     * @param [type] ...$args 可不带，或带Boolean
     * @return void
     */
    public function handle($request, \Closure $next, ...$args) 
    {
        if ($args) {
            $isCheck = $this->getArgument($args, 0, false);
            if (is_bool($isCheck)) {
                if (empty($request->user) || !isset($request->user["user_id"])) {
                    if (IS_AJAX) {
                        throw new ValidateException('no login.', 403);
                    }
                    redirect(url('login'))->send();
                    die;
                } elseif(!isset($request->user["username"])) {
                    $user_info = User::getInfo([["user_id", "=", $request->user["user_id"]]]);
                    if (empty($user_info)) {
                        $request->user = [ "SESSION_ID" => S1 ];
                        $request->login();
                        if (IS_AJAX) {
                            throw new ValidateException('no login.', 403);
                        }
                        redirect(url('login'))->send();
                        die;
                    }

                    $request->user = array_merge($request->user, $user_info->toArray());
                    $request->login();

                    $user_info->login_time = TIMESTAMP;
                    $user_info->login_real_ip = $request->ip();
                    $user_info->save();
                }

                define('S1', $request->user['user_id']);
                define('S2', $request->user['username']);
                define('S3', $request->user['parent_id']);
                define('S4', $request->user['group']);

                if ($isCheck) {
                    if (!$this->checkAccess($request)) {
                        throw new \Exception(lang("no access"));
                    }
                }
            }
        }

        return $next($request);
    }
}