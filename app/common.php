<?php
// 应用公共文件
if (!function_exists('array_splice_value')) {
    /**
     * 删除数组中的一个元素
     * @author k.
     * @version 1.0.0
     * @return mixed
     */
    function array_splice_value(&$arr, $index = 0, $length = 1, $replace = []) {
        $pre = array_splice($arr, $index, $length, $replace);
        return current($pre);
    }
}

if (!function_exists('array_column_bind')) {
    /**
     * 数据集多列绑定到一行上
     * @param 数据集 $list
     * @param 字段名 $field
     * @param 字段值 $value
     * @return Array
     */
    function array_column_bind(&$list, $exten, $field, $value) {
        array_walk($list, function(&$arr) use($exten, $field, $value) {
            foreach ($arr[$exten] as $attr) {
                $arr[$attr[$field]] = $attr[$value];
            }
        
            unset($arr[$exten]);
        });

        return $list;
    }
}

if (!function_exists('extractTree')) {
    /**
     * 分解树形数组
     * @param array $data 数据源
     * @return Array
     */
    function extractTree($data, $sortField='', $sort=SORT_ASC) {
        $rows = [];
        array_walk($data, $parse = function($arr) use (&$rows, &$parse) {
            if (is_array($arr) && !empty($arr)) {
                if (isset($arr['children'])) {
                    array_walk($arr['children'], $parse);
                    unset($arr["children"]);
                }

                $rows[] = $arr;
            }
        });

        if ($sortField) {
            $volume = array_values(array_column($rows, $sortField));
            array_multisort($volume, $sort, $rows);
        }

        return $rows;
    }
}

if (!function_exists('conf')) {
    /**
     * 返回缓存配置
     *
     * @param string $name
     * @param string $default
     * @return void
     */
    function conf($name, $default = '', $prefix = "config") {
        $value = redis()->get("{$prefix}.{$name}");
        if (!$value) {
            $value = $default;
        }
        return $value;
    }
}

if (!function_exists('addCron')) {
    /**
     * 创建消息队列
     * addCron(__METHOD__, $argv)
     * @return mixed
     */
    function addCron($method, $params = [], $later = 1, $queue = 'JobManager') {
        app()->make(\app\model\system\Cron::class)->addCron($queue, [$method, $params], $later);
    }
}

if (!function_exists('parseTime')) {
    /**
     * 解析时间为字串
     * @return String
     */
    function parseTime($value) {
        if (!$value) return '';
        if (!is_numeric($value)) $value = strtotime($value);
        $language = \think\facade\Lang::getLangSet();
        $format = 'Y-m-d H:i';
        if ($language === 'ja') {
            $value += 3600;
            $format = 'H:i, m月d日,Y';
        } elseif($language === 'en') {
            $value -= 46800;
            $format = 'm/d/Y H:i';
        }
        return date($format, $value);
    }
}

if (!function_exists('getTime')) {
    /**
     * 获取时间片段
     *
     * @param string $value
     * @return void
     */
    function getTime($value = '') {
        if (empty($value)) return [];
        if (strpos($value, '~') !== false) {
            $values = explode('~', $value);
        } else {
            $values = [$value];
        }
        $result = [];
        foreach($values as $value) {
            $value = trim($value);
            $result[] = strtotime($value);
        }
        return $result;
    }
}

if (!function_exists('checkAccess')) {
    /**
     * 验证是否有权限
     *
     * @param string $rule
     * @return boolean
     */
    function checkAccess($rule) {
        static $user = null;
        if ($user === null) {
            $user = request()->user;
        }

        return app()->make(\app\model\system\Rule::class)->check($rule, $user);
    }
}

if (!function_exists('logger')) {
    /**
     * 记录日志
     *
     * @param [type] ...$args
     * @return void
     */
    function logger(...$args) {
        static $logger = null;
        if ($logger === null) {
            $logger = new \app\model\system\Logs();
        }
        call_user_func_array([$logger, "info"], [$args]);
    }
}

if (!function_exists('base58_encode')) {
    
    /**
     * BASE58编码
     * @access protected
     * @return String
     */
    function base58_encode($string)
    {
        $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $base = strlen($alphabet);
        if (is_string($string) === false) {
            return false;
        }
        if (strlen($string) === 0) {
            return '';
        }
        $bytes = array_values(unpack('C*', $string));
        $decimal = $bytes[0];
        for ($i = 1, $l = count($bytes); $i < $l; $i++) {
            $decimal = bcmul($decimal, 256);
            $decimal = bcadd($decimal, $bytes[$i]);
        }
        $output = '';
        while ($decimal >= $base) {
            $div = bcdiv($decimal, $base, 0);
            $mod = bcmod($decimal, $base);
            $output .= $alphabet[$mod];
            $decimal = $div;
        }
        if ($decimal > 0) {
            $output .= $alphabet[$decimal];
        }
        $output = strrev($output);
        foreach ($bytes as $byte) {
            if ($byte === 0) {
                $output = $alphabet[0] . $output;
                continue;
            }
            break;
        }
        return (string) $output;
    }
}

if (!function_exists('async')) {
    /**
     * 异步事件
     *
     * @param array $called
     * @param array $params
     * @return void
     */
    function async($called = [], $params = [], $delay = 0)
    {
        $length = count($called);
        $data = [ "require" => $called[0] ];
        if ($length === 3) {
            $data["params"] = $called[1];
            $data["method"] = $called[2];
        } else {
            $data["method"] = $called[1];
        }

        if ($delay > 0) {
            $data["delay"] = $delay;
        }

        if (!empty($params)) {
            $data["argv"] = $params;
        }

        if (defined("S0")) {
            $data["token"] = S0;
        }

        \app\job\JobManager::push($data);
    }
}

if (!function_exists('password_check')) {
    /**
     * 密码校验
     * @param String $ori
     * @param String $salt
     * @param String $verify
     * @return String
     */
    function password_check($ori = '', $salt = '', $verify = null) {
        if ($verify === null) {
            return md5(md5($ori) . $salt);
        } else {
            if ($salt === '') {
                $salt = uniqid();
                return [md5(md5($ori) . $salt), $salt];
            }

            return md5(md5($verify) . $salt) == $ori;
        }
    }
}

if (!function_exists('getToken')) {
    /**
     * 获取TOKEN
     * @param String $prepare
     * @return String
     */
    function getToken($prepare = '') {
        return md5(uniqid() . "$" . $prepare);
    }
}

if (!function_exists('rand_string')) {
    /**
     * 获取随机字串
     * @param String $prepare
     * @return String
     */
    function rand_string($length=5, $indent=0) {
        $dict = array(
            '_0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '0123456789',
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'in:simplified',
            'in:traditional'
        );
        if(substr($dict[$indent] , 0 , 3) == 'in:'){
            require_once(dirname(__FILE__).'/functions/Tradition/'.substr($dict[$indent] ,3).'.php');
            $_t = tradition.'_'.substr($dict[$indent],3);
            $dict[$indent] = $t();
        }
        $result = '';
        while($length-- > 0)$result .= substr($dict[$indent] ,mt_rand(0 ,strlen($dict[$indent])-1) ,1);
        return $result;
    }
}

if (!function_exists('redis')) {
    /**
     * 应用Redis存储
     * @return std
     */
    function redis() {
        return \think\facade\Cache::store('redis');
    }
}

if (!function_exists('array_keys_filter')) {
    /**
     * 数组键名过滤
     * @param array $stack
     * @param array $filters
     * @param bool $force
     * @description
     * 
     * array_keys_filter([], [
     *      'a',
     *      ['b', []],
     *      ['c', function($value) { ... }],
     *      ['d' => function($value) { ... }],
     *      'e.name'
     * ], true)
     * @return array
     */
    function array_keys_filter($stack = [], $filters, $force = false) {
        if (is_string($filters)) {
            $filters = explode(",", $filters);
        }

        foreach($stack as $key => $value) {
            if (preg_match('/[\w\-]+\[[\w\-]+\]/', $key)) {
                $keys = explode('[', $key);
                if (!isset($stack[$keys[0]])) {
                    $stack[$keys[0]] = [];
                }

                $keys[1] = substr($keys[1], 0, strlen($keys[1]) - 1);
                $stack[$keys[0]][$keys[1]] = $value;

                unset($stack[$key]);
            }
        }

        $res = [];

        foreach($filters as $filter) {
            if (is_array($filter)) {
                if (is_string($filter[0])) {
                    if (isset($stack[$filter[0]])) {
                        if (is_callable($filter[1])) {
                            $res[$filter[0]] = $filter[1]($stack[$filter[0]]);
                        } else {
                            $res[$filter[0]] = $stack[$filter[0]];
                        }
                    } else {
                        if ($force && !isset($filter[1])) {
                            throw new \Exception("参数{$filter[0]}不能为空!");
                        } else {
                            $res[$filter[0]] = $filter[1];
                        }
                    }
                } elseif(is_callable($filter[0])) {
                    foreach($filter as $key => $val) {
                        if (isset($stack[$key])) {
                            $res[$key] = $val($stack[$key]);
                        } elseif ($force) {
                            throw new \Exception("参数{$key}不能为空!");
                        }
                    }
                } else {
                    throw new \Exception("过滤器" . json_encode($filter) . "错误!");
                }
            } elseif (is_string($filter)) {
                if (isset($stack[$filter])) {
                    $res[$filter] = $stack[$filter];
                } elseif($force) {
                    throw new \Exception("参数{$filter}不能为空!");
                }
            } elseif (is_callable($filter)) {
                $ret = $filter($stack);
                if (!empty($ret)) {
                    $res = array_merge($res, $ret);
                }
            } else {
                throw new \Exception("过滤器" . json_encode($filter) . "错误!");
            }
        }

        return $res;
    }
}

if (!function_exists('parseTree')) {
    /**
     * 无线级分解
     * 
     * @param array $data 数据源
     * @param string $id 主键
     * @param string $parentId 父级
     * @param string $children 子类
     * @return Array
     */
    function parseTree(Array $data, $id = "id", $parentId = 'parent_id', $children = 'children')
    {
        $rows = $res = [];
        foreach ($data as $row)
            $rows[$row[$id]] = $row;

        foreach ($rows as $row) {
            if (isset($rows[$row[$parentId]])) {
                $rows[$row[$parentId]][$children][] = &$rows[$row[$id]];
            } else if($row[$parentId] == 0){
                $res[] = &$rows[$row[$id]];
            }
        }

        return $res;
    }
}

if (!function_exists('forMapIds')) {
    /**
     * 无限向下遍历树形
     *
     * @param collect $model
     * @param string  $pk
     * @return array [1,2,3,4,5,6]
     */
    function forMapIds($m, $value, $pk = 'id', $pid = 'parent_id') {
        // 当前所有IDS
        $ids = $m->where($pid, $value)->value("GROUP_CONCAT(`{$pk}`)");

        if (!empty($ids)) {
            $cids = $ids;

            while(!empty($cids)) {
                $cids = $m->where($pid, 'IN', $cids)->value("GROUP_CONCAT(`{$pk}`)");
                if (!empty($cids)) {
                    $ids .= ",{$cids}";
                }
            }
        }

        return explode(",", $ids);
    }
}

if (!function_exists('getLocationByIp')) {
    /**
     * 根据ip定位
     * @param $ip
     * @param $type
     * @return string | array
     * @throws Exception
     */
    function getLocationByIp($ip, $type = 1)
    {
        $ip2region = new \Ip2Region();
        $info = $ip2region->btreeSearch($ip);
        $info = explode('|', $info['region']);
        $address = '';
        foreach($info as $vo) {
            if('0' !== $vo) {
                $address .= $vo . '-';
            }
        }

        if (2 == $type) {
            return ['province' => $info['2'], 'city' => $info['3']];
        }

        return rtrim($address, '-');
    }
}
