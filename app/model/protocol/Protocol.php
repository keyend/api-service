<?php
namespace app\model\protocol;
use app\Model;

class Protocol extends Model
{
    protected $name = 'protocol';

    /**
     * 搜索匹配URI
     *
     * @param string $keyword
     * @return void
     */
    public static function search($keyword)
    {
        $query = new static;
        $res = $query->where([['uri', 'like', "%{$keyword}%"]])->find();

        return $res;
    }
}