<?php
namespace mashroom\service;
/**
 * mashroom.SOCKET
 * @author k. <email@email.com>
 * @version 1.0.0
 */

class Websocket extends \think\swoole\Websocket
{
    public function emit(string $event, ...$args): bool
    {
        $data = [];
        $length = count($args);
        foreach($args as $arg) {
            if (is_array($arg)) {
                $data = array_merge($data, $arg);
            } elseif($length === 1 && is_string($arg)) {
                $data = $arg;
            }
        }

        return $this->push($this->encode([
            'type' => $event,
            'data' => $data,
        ]));
    }
}
