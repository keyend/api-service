<?php
/**
 * mashroom.SOCKET
 * 
 * @author k. <email@email.com>
 * @package mashroom.provicer.websocket
 * @version 1.0.0
 */
namespace mashroom\provider;
use mashroom\service\websocket\Response;

class Websocket extends \think\swoole\Websocket
{
    public function push($data)
    {
        if ($data instanceof Response) {
            $data = $data->toJSON();
        } elseif(is_array($data)) {
            $data = json_encode($data);
        }

        return parent::push($data);
    }
}
