<?php
namespace app\model\auth;
/**
 * 请求认证
 * 
 * @package OAuthScopeData
 * @version 1.0.0
 */
use think\Model;
use app\model\Setting;

class OAuthScopeData extends Model 
{
    /**
     * 表名
     *
     * @var string
     */
    protected $name = 'oauth_scope_data';

    /**
     * 主键
     *
     * @var string
     */
    protected $pk = "id";
}