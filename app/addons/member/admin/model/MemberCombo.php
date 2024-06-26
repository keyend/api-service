<?php
namespace app\addons\member\admin\model;
use app\Model;
use app\model\member\Combo;
/**
 * 会员套餐
 * 
 * @version 1.0.0
 */
class MemberCombo extends Combo
{
    private $bill_method_list = [
        'times' => [
            'name' => 'times',
            'title' => '按次收费'
        ],
        'day' => [
            'name' => 'day',
            'title' => '按天收费'
        ],
        'month' => [
            'name' => 'month',
            'title' => '按月收费'
        ],
        'year' => [
            'name' => 'year',
            'title' => '按年收费'
        ]
    ];

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
        $list = $query->page($page, $limit)->select()->toArray();

        return compact('count', 'list', 'sql');
    }

    /**
     * 返回计费方式
     *
     * @return void
     */
    public function getBillMethodList()
    {
        return $this->bill_method_list;
    }

    /**
     * 返回计费方式
     *
     * @return void
     */
    public function getMethodName($name = null)
    {
        $bill_method = $this->bill_method_list[$name === null ? $this->bill_method : $name] ?? [];
        return $bill_method['title'] ?? '';
    }

    /**
     * 返回套餐列表
     *
     * @return void
     */
    public function getComboList()
    {
        return self::where([['status', '=', 1]])->order('id DESC')->select();
    }

    /**
     * 获取到期时间
     *
     * @param integer $num
     * @param integer $startTime
     * @return void
     */
    public function getExpireTime($num = 0, $startTime = 0)
    {
        if ($startTime === 0) {
            $startTime = time();
        }

        if ($this->bill_method == 'day') {
            $interval = $num * $this->days;
        } elseif($this->bill_method == 'month') {
            $interval = $num * $this->months;
        } elseif($this->bill_method == 'year') {
            $interval = $num * $this->years;
        }

        $value = strtotime("+{$interval} {$this->bill_method}", $startTime);

        return $value;
    }

    /**
     * 添加套餐
     *
     * @param array $data
     * @return void
     */
    public function addCombo($data = [])
    {
        if ($this->where('combo', '=', $data['combo'])->field('id')->find()) {
            throw new \Exception("套餐记录已存在!");
        }
        
        $data = array_merge([
            'create_time' => time(),
            'update_time' => time()
        ], $data);
        $data['times'] = (int)$data['times'];
        $data['days'] = (int)$data['days'];
        $data['months'] = (int)$data['months'];
        $data['years'] = (int)$data['years'];

        return self::create($data);
    }
}