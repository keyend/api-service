<?php
namespace app\addons\member\admin\model;
use app\Model;
/**
 * 会员
 * 不收集会员信息，只是临时会员表
 * 
 * @version 1.0.0
 */
class Member extends \app\model\member\Member
{
    /**
     * 返回会员列表
     *
     * @param integer $page
     * @param integer $limit
     * @param array $condition
     * @param string $field
     * @param array $join
     * @return void
     */
    public function getList(int $page, int $limit, Array $condition = [], $field = '*', $join = [])
    {}
}