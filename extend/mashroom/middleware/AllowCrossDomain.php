<?php
namespace mashroom\middleware;

use app\Request;
use think\App;
use think\Config;

class AllowCrossDomain extends \think\middleware\AllowCrossDomain
{
    public function __construct(Config $config)
    {
        if ($config->get('app.show_error_msg', 0)) {
            foreach($this->header as $name => $value) {
                header("{$name}: {$value}");
            }

            header("Access-Control-Allow-Origin: *");
        }

        parent::__construct($config);
    }
}