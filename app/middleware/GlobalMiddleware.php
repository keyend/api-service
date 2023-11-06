<?php
namespace app\middleware;
/*
 * 全局校验中间件
 * 
 * @Date: 2020-11-10
 * @version 1.0.0
 */
use mashroom\middleware\BaseMiddleware;
use mashroom\exception\ValidateException;

class GlobalMiddleware extends BaseMiddleware
{
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
        $session_id = cookie($this->app->config->get("session.name"));
        if (empty($session_id)) {
            $session_id = md5(uniqid());
            cookie($this->app->config->get("session.name"), $session_id);
        }

        if ($this->app->config->get("site.status", "0") == 1) {
            throw new ValidateException('The service has been closed or is undergoing maintenance and upgrade.', 50001);
        }

        define('S0', $session_id);
        $request->user = redis()->get("usr.{$session_id}");
        if (!empty($request->user)) {
            if (isset($request->user["authorize"])) {
                $request->user["authorize"]["expire_at"] = $request->user["authorize"]["expire_at"] ?? 0;
                $request->user["authorize"]["access_token"] = $request->user["authorize"]["access_token"] ?? "";
                if (TIMESTAMP < $request->user["authorize"]["expire_at"]) {
                    define("S5", $request->user["authorize"]["access_token"]);
                }
            }
        } else {
            $request->user = [ "SESSION_ID" => S0 ];
            $request->login($request->user);
        }

        return $next($request);
    }
}