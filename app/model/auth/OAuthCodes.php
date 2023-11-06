<?php
/**
 * 请求认证
 * 
 * @package OAuthCodes
 * @version 1.0.0
 */
namespace app\model\auth;
use app\Model;

class OAuthCodes extends Model 
{
    /**
     * 表名
     *
     * @var string
     */
    protected $name = 'oauth_codes';

    /**
     * 主键
     *
     * @var string
     */
    protected $pk = "id";

    /**
     * 创建一个新的授权请求
     *
     * @param array $params
     * @return void
     */
    public function build($params = [])
    {
        $sercret_key = conf("api.secret_key");
        if (strcasecmp($sercret_key, $params["key"])) {
            throw new \Exception("请求错误!");
        }

        $scope = app()->make(OAuthScope::class)->get($params["scope"]);

        return parent::create([
            "code" => getToken(),
            "scope" => $scope["scope"],
            "client" => $params["client"],
            "expire_in" => $scope["expire_in"]
        ]);
    }
}