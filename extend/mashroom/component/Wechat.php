<?php
namespace mashroom\component;
/**
 * 微信
 * 
 * @date    2021-05-10 20:19:31
 * @version 1.0
 * @author  easyzf.cn
 */
use EasyWeChat\Factory;

class Wechat
{
    /**
     * 获取根目录
     *
     * @return void
     */
    private static function getRootPath()
    {
        $rootPath = app()->getRootPath();
        $currentPath = realpath('.');
        $depths = explode(DIRECTORY_SEPARATOR, substr($currentPath, strlen($rootPath)));
        
        foreach($depths as $i => &$depth) {
            if ($depth !== '') {
                $depth = '..';
            } else {
                unset($depths[$i]);
            }
        }

        if (!empty($depths)) {
            $currentPath = '.' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $depths);
        } else {
            $currentPath = '.';
        }

        return $currentPath;
    }

    /**
     * 返回接入应用
     *
     * @return object
     */
    public static function official()
    {
        $rootPath = self::getRootPath();
        $datePath = date('Ym');
        $config = [
            'app_id' => config('common.wechat_appId'),
            'secret' => config('common.wechat_secret'),
            'token'  => config('common.wechat_token'),
            'aes_key'=> config('common.wechat_encodingaes'),
            /**
            * 日志配置
            *
            * level: 日志级别, 可选为：
            *         debug/info/notice/warning/error/critical/alert/emergency
            * path：日志文件位置(绝对路径!!!)，要求可写权限
            *
            * 名称	描述
            *
            * stack	复合型，可以包含下面多种驱动的混合模式
            * single	基于 StreamHandler 的单一文件日志，参数有 path，level
            * daily	基于 RotatingFileHandler 按日期生成日志文件，参数有 path，level，days(默认 7 天)
            * slack	基于 SlackWebhookHandler 的 Slack 组件，参数请参考源码：LogManager.php
            * syslog	基于 SyslogHandler Monolog 驱动，参数有 facility 默认为 LOG_USER，level
            * errorlog	记录日志到系统错误日志，基于 ErrorLogHandler，参数有 type，默认为 ErrorLogHandler::OPERATING_SYSTEM
            */
           'log' => [
               'default' => 'dev', // 默认使用的 channel，生产环境可以改为下面的 prod
               'channels' => [
                   // 测试环境
                   'dev' => [
                       'driver' => 'daily',
                       'path' => $rootPath . '/runtime/log/' . $datePath . '/easywechat.log',
                       'level' => 'debug',
                   ],
                   // 生产环境
                   'prod' => [
                       'driver' => 'daily',
                       'path' => $rootPath . '/runtime/log/' . $datePath . '/easywechat.log',
                       'level' => 'info',
                   ],
               ],
           ],
           /**
             * 接口请求相关配置，超时时间等，具体可用参数请参考：
             * http://docs.guzzlephp.org/en/stable/request-config.html
             *
             * - retries: 重试次数，默认 1，指定当 http 请求失败时重试的次数。
             * - retry_delay: 重试延迟间隔（单位：ms），默认 500
             * - log_template: 指定 HTTP 日志模板，请参考：https://github.com/guzzle/guzzle/blob/master/src/MessageFormatter.php
             */
            'http' => [
                'verify' => false,
                'max_retries' => 1,
                'retry_delay' => 500,
                'timeout' => 5.0,
                // 'base_uri' => 'https://api.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
            ],
            /**
             * OAuth 配置
             *
             * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
             * callback：OAuth授权完成后的回调页地址
             */
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/api/wechat/oauth/fallback',
            ]
        ];

        $app = Factory::officialAccount($config);

        return $app;
    }
}
