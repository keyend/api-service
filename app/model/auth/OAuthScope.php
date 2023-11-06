<?php
/**
 * 请求认证
 * 
 * @package OAuthScope
 * @version 1.0.0
 */
namespace app\model\auth;
use app\Model;

class OAuthScope extends Model 
{
    /**
     * 表名
     *
     * @var string
     */
    protected $name = 'oauth_scope';

    /**
     * 主键
     *
     * @var string
     */
    protected $pk = "id";

    /**
     * 返回作用域列表
     *
     * @param string $scope
     * @return void
     */
    public function get($scope = 'userinfo')
    {
        $scope = self::where("scope", "=", $scope)->find();
        if (empty($scope)) {
            throw new \Exception("INVALID SCOPE");
        }
        $scope["data"] = app()->make(OAuthScopeData::class)->where([["scope", "in", [$scope["scope"], "common"]]])->column("name");
        return $scope;
    }
}