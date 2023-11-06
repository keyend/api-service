<?php
namespace mashroom\service;
/**
 * mashroom.mushroom
 * @author k. <email@email.com>
 * @version 1.0.0
 * 
 * define('mi', $this->app->get('mushroom'));
 * mi->get('user.username');
 * mi->user->username;
 * mi->user['username'];
 */
use think\Container;

class MushroomService extends Container
{
    /**
     * 返回对象
     *
     * @param string $name
     * @return void
     */
    public function get($name = '')
    {
        if (strpos($name, '.') !== FALSE) {
            $names = explode('.', $name);
            foreach($names as $n) {
                if (!isset($value)) {
                    if (!parent::offsetExists(n)) {
                        break;
                    }

                    $value = parent::offsetGet(n);
                } else {
                    if (isset($value[$n])) {
                        $value = $value[$n];
                    } else {
                        return null;
                    }
                }
            }

            if (!isset($value)) {
                if (!parent::offsetExists($name)) {
                    return null;
                }
            }

            return $value;
        }

        return parent::__get($name);
    }
}