<?php
/**
 * @package OAuth
 * 请求认证
 */
namespace app\model\auth;
use think\Model;

class OAuth extends Model 
{
    /**
     * 表名
     *
     * @var string
     */
    protected $name = 'oauth_tokens';

    /**
     * 返回access_token
     *
     * @param array $params
     * @return void
     */
    public function access_token($params = [])
    {
        $data = [];
        $access_token = !empty($this->access_token) ? $this->access_token : $params['session_id'];
        if (!empty($access_token)) {
            $data = $this->where("access_token", $access_token)->where("expire_at", "<", TIMESTAMP)->order('id DESC')->find();
        }

        if (empty($data)) {
            $data = self::create([
                "code" => $params["code"],
                "access_token" => $params['session_id'] ?? getToken(),
                "expire_at" => $params["expire_in"] + TIMESTAMP,
                "user_id" => 0
            ]);
        }

        return $data;
    }
}