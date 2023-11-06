<?php
namespace app\event;
/**
 * 全局变量声明
 *
 * @param array $param
 * @return void
 * @version 1.0.0
 */
use think\App;
use think\Response;

class Constant
{
    public function handle(App $app)
    {
        if (!isset($_SERVER['REQUEST_TIME'])) {
            $_SERVER['REQUEST_TIME'] = time();
        }

        define('TIMESTAMP', $_SERVER['REQUEST_TIME']);
        define('MODULE',    $app->request->module());
        define('CONTROLLER',$app->request->controller());
        define('ACTION',    $app->request->action());
        define('IS_POST',   $app->request->isPost());
        define('IS_PUT',    $app->request->isPut());
        define('IS_DELETE', $app->request->isDelete());
        define('IS_GET',    $app->request->isGet());
        define('IS_AJAX',   $app->request->isAjax());

        $accept = explode(',', $app->request->header('accept'));
        define('CONTENT_TYPE', empty($accept) ? 'text/html' : $accept[0]);
        define('IS_JSON', strpos(CONTENT_TYPE, 'json') !== FALSE ? true: false);

        if ($app->request->method(true) == 'OPTIONS') {
            throw new \think\Response\HttpResponseException(Response::create()->code(200));
        }

        if ($app->config->get("site.domain", "") === "") {
            $site = [ "domain" => $app->request->domain() ];
            $app->config->set([ "site" => $site ]);
            file_put_contents($app->getAppPath() . "config" . DIRECTORY_SEPARATOR . "/site.php", "<?php\r\nreturn " . var_export($site, true) . ";\r\n");
        }
    }
}