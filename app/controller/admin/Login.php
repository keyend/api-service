<?php
namespace app\controller\admin;
/**
 * 操作员登录
 * 
 * @version 1.0.0
 */
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use app\model\system\User;

class Login extends Controller
{
    /**
     * 登录
     *
     * @return void
     */
    public function login(User $user_model)
    {
        if ($this->request->isPost()) {
            if (empty($this->params)) {
                return $this->error($this->validate->getError());
            }

            $verifyType = (int)gc("valid.backend", 0);
            $valid = $verifyType === 2 ? $this->validateBehaviour($this->aesDecrypt($this->params['code'], $this->params['key'])) : $this->params['code'];
            if ($verifyType === 1) {
                $name = "sys.captcha.{$this->params['key']}";
                $code = strtolower(redis()->get($name));
                redis()->delete($name);
                $valid = strtolower($valid);
            }
            if (($verifyType === 2 && true !== $valid) || ($verifyType === 1 && $valid != $code)) {
                return $this->error("验证码填写不正确");
            } elseif (true !== $this->request->isLogin()) {
                $res = $user_model->validate($this->params);
                $this->request->login($res);
                $this->logger('logs.user.login', 'LOGGED', $this->request->user);
            } else {
                $this->request->login();
            }
            return $this->success();
        } else {
            $this->assign("config", gc("valid,index"));
            return $this->fetch();
        }
    }

    /**
     * 注销登录
     * @return mixed
     */
    public function logout()
    {
        if (IS_POST) {
            $this->logger('logs.user.logout', 'LOGOUT', $this->request->user);
            redis()->delete("usr.{$this->request->user['SESSION_ID']}");
            cookie('token', null);
        }

        return $this->success();
    }
    
    /**
     * 验证码
     *
     * @return void
     */
    public function captcha()
    {
        $builder = new CaptchaBuilder(null, new PhraseBuilder(4));
        $captcha = $builder->build()->inline();
        $key = substr(getToken('verify'), 8, 16);
        $expireTime = config('admin.user_vi_time', 1) * 60;
        redis()->tag("temp")->set("sys.captcha.{$key}", $builder->getPhrase(), $expireTime);

        return $this->success(compact('key', 'captcha'));
    }

    /**
     * AES解密CBC字串
     * @param [type] $str
     * @return void
     */
    protected function aesDecrypt($str, $key = '') 
    {
        // $decryptStr = openssl_decrypt(base64_decode($str), 'AES-128-CBC', substr(S0, 8, 16), true, substr($key, 0, 16));
        $rsa = $this->app->make(\mashroom\component\algorithm\Rsa::class);
        $decryptStr = $rsa->setPrivateKey()->decrypt($str);

        return $decryptStr;
    }

    /**
     * 行为验证
     * @param string
     * @return Boolean
     */
    protected function validateBehaviour($code)
    {
        $points = explode(',', $code);
        $commitTime = end($points);
        // 结构
        if (count($points) < 13) return '1';
        if (!is_numeric(current($points))) return '2';
        // 操作时间太快(一秒内)
        if ($commitTime - $points[10] < 800) return '3';
        // 时间验证(验证码超时)
        $commitTime = ceil($commitTime / 1000);
        if (TIMESTAMP < $commitTime) return '4';
        // 时间验证(验证码超时)
        if (TIMESTAMP - $commitTime > 20) return '5';

        return true;
    }
}