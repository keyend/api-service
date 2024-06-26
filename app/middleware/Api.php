<?php
namespace app\middleware;
use mashroom\middleware\BaseMiddleware;
use mashroom\exception\HttpException;
use app\model\protocol\Protocol;
use app\model\protocol\Logs;
use app\model\protocol\Apps;

class Api extends BaseMiddleware
{
    /**
     * API中间件
     *
     * @param Request $request
     * @param \Closure $next
     * @param mixed ...$args 可不带，或带Boolean
     * @return void
     */
    public function handle($request, \Closure $next, ...$args) 
    {
        $this->protocolAdapterFilter($request);

        return $next($request);
    }

    /**
     * API请求
     *
     * @param Request $request
     * @return void
     */
    private function protocolAdapterFilter($request)
    {
        $pathinfo = substr($request->pathinfo(), 3);
        if (empty($pathinfo)) {
            return false;
        }

        $protocol = Protocol::search("{$pathinfo}");
        if (empty($protocol)) {
            return false;
        }

        define('MIDDLEWARE_JSON', true);
        $param = $request->param();
        if (!isset($param['mer_id']) || empty($param['mer_id'])) {
            throw new HttpException("参数错误([mer_id]不能为空)", 50024);
        } elseif(!isset($param['sign'])) {
            throw new HttpException("参数错误([sign]不能为空)", 50028);
        }

        $app = Apps::find($param['mer_id']);
        if (empty($app)) {
            throw new HttpException("[mer_id]不正确", 50025);
        } elseif(!$app->isSign($param)) {
            throw new HttpException("[sign]错误", 50025);
        }

        $member_info = $app->member;
        $quota = $member_info->hasComboQuotaLimit();
        if ($quota === 'NO_TIMES') {
            throw new HttpException("调用配额不足", 50026);
        } elseif ($quota === 'SH_EXPIRE') {
            throw new HttpException("会员已到期", 50027);
        } elseif ($quota === 'NO_MATCH') {
            throw new HttpException("请购买后调用", 50028);
        }

        $transaction = Logs::create([
            'protocol_id' => $protocol->id,
            'protocol' => $protocol->protocol,
            'pathname' => $pathinfo,
            'param' => json_encode($param),
            'ip' => request()->ip(),
            'member_id' => $member_info['member_id'],
            'nickname' => $member_info['nickname'],
            'username' => $member_info['username'],
            'mobile' => $member_info['mobile'],
            'order_id' => $quota['order_id'],
            'combo_method' => $quota['combo_method'],
            'times' => $quota['times'],
            'create_time' => time()
        ]);

        define('S6', $quota['id']);
        define('S7', $transaction['id']);
    }
}