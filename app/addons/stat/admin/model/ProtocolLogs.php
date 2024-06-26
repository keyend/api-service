<?php
namespace app\addons\stat\admin\model;
use app\Model;
use app\model\protocol\Protocol;
/**
 * 接口调用记录
 * 
 * @version 1.0.0
 */
class ProtocolLogs extends \app\model\protocol\Logs
{
    /**
     * 返回列表
     *
     * @param integer $page
     * @param integer $limit
     * @param array $condition
     * @param string $field
     * @param array $join
     * @return void
     */
    public function getList(int $page, int $limit, Array $condition = [])
    {
        $query = $this->order('id DESC');
        $query->where($condition);
        $count = $query->field($field)->count();
        $list = $query->page($page, $limit)->select();

        return compact('count', 'list', 'sql');
    }

    /**
     * 返回接口列表
     *
     * @return void
     */
    public function getProtocolList()
    {
        return Protocol::where('status', 1)->field('id,protocol')->select();
    }

    /**
     * 返回明细
     *
     * @param integer $id
     * @return void
     */
    public function getDetail($id = 0)
    {
        $ret = self::find($id);

        return $ret;
    }

    /**
     * 返回数据记录
     *
     * @param array $member_ids
     * @param integer $beginTime
     * @return void
     */
    private function getShapeDataBetween($member_ids = [], $beginTime = 0)
    {
        $lastTime = strtotime('+1 month', $beginTime) - 1;
        $result = [];
        $search_list = self::where([['member_id', 'IN', $member_ids], ['create_time', 'BETWEEN', [ $beginTime, $lastTime ]]])
            ->fieldRaw('member_id,COUNT(*) as usage1')
            ->group('member_id')
            ->select();

        if (!empty($search_list)) {
            foreach($search_list as $value) {
                $result[$value['member_id']] = (int) $value['usage1'];
            }
        }

        foreach($member_ids as $member_id) {
            if (!isset($result[$member_id])) {
                $result[$member_id] = 0;
            }
        }

        return array_values($result);
    }

    /**
     * 返回仪表盘柱形数据
     *
     * @return void
     */
    public function getShapeData()
    {
        $currentMonthlyBegin = strtotime(date('Y-m-1'));
        $previousMonthlyBegin = strtotime('-1 month', $currentMonthlyBegin);
        $member_list = self::where([['create_time', '>', $previousMonthlyBegin]])->field('member_id,username')->group('member_id')->select();
        $sql = $this->getLastSql();
        if (!empty($member_list)) {
            $member_list = $member_list->toArray();
        }
        $customer = array_values(array_column($member_list, 'username'));
        $member_ids = array_values(array_column($member_list, 'member_id'));
        $monthly_list = [$previousMonthlyBegin, $currentMonthlyBegin];
        $list = [];
        foreach($monthly_list as $monthly) {
            $list[] = [
                'name' => date('m', $monthly) . "月份",
                'type' => 'bar',
                'data' => $this->getShapeDataBetween($member_ids, $monthly)
            ];
        }

        return compact('customer', 'list');
    }

    /**
     * 返回仪表盘饼形数据
     *
     * @return void
     */
    public function getPieData()
    {
        $member_list = self::where([['id', '>', 0]])->field('member_id,username')->group('member_id')->select();
        if (!empty($member_list)) {
            $member_list = $member_list->toArray();
        }

        $result = [];
        $customer = array_values(array_column($member_list, 'username'));
        $customer_mapper = array_column($member_list, 'username', 'member_id');
        $member_ids = array_values(array_column($member_list, 'member_id'));
        $search_list = self::where([['member_id', 'IN', $member_ids] ])
            ->fieldRaw('member_id,username,COUNT(*) as usage1')
            ->group('member_id')
            ->select();

        if (!empty($search_list)) {
            foreach($search_list as $value) {
                $result[$value['member_id']] = [
                    'name' => $value['username'],
                    'value' => $value['usage1']
                ];
            }
        }

        foreach($member_ids as $member_id) {
            if (!isset($result[$member_id])) {
                $result[$member_id] = [
                    'name' => $customer_mapper[$member_id],
                    'value' => 0
                ];
            }
        }

        $list = array_values($result);

        return compact('customer', 'list');
    }
}