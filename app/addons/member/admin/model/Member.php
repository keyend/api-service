<?php
namespace app\addons\member\admin\model;
use app\Model;
use app\model\order\OrderCombo;
use mashroom\Virtual;
/**
 * 会员
 * 不收集会员信息，只是临时会员表
 * 
 * @version 1.0.0
 */
class Member extends \app\model\member\Member
{
    /**
     * 关联模型
     *
     * @return void
     */
    public function level()
    {
        return $this->hasOne(MemberLevel::class, 'level', 'level_id');
    }

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
    public function getListPage(int $page, int $limit, Array $condition = [], $field = '*', $joins = [])
    {
        $query = self::alias("um");
        if (!empty($joins)) {
            foreach($joins as $join) {
                $query->join($join);
            }
        } else {
            $query->with(['source', 'level']);
        }
        $query->where($condition);
        $count = $query->field($field)->count();
        $list = $query->page($page, $limit)->select()->toArray();
        $sql = $query->getLastSql();
        if (!empty($list)) {
            $label_ids = implode(',', array_values(array_column($list, 'label')));
            $label_ids = array_filter(array_values(array_unique(explode(',', $label_ids))));
            $labels = !empty($label_ids) ? MemberLabel::where('id', 'IN', $label_ids)->column("id,labelname", "id") : [];
            array_walk($list, function(&$item) use($labels) {
                $item['label_list'] = [];
                if (!empty($item['label']) && !empty($labels)) {
                    foreach(explode(",", $item['label']) as $label_id) {
                        if (isset($labels[$label_id])) {
                            $item['label_list'][] = $labels[$label_id];
                        }
                    }
                }
            });
        }

        return compact('count', 'list', 'sql');
    }

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
    public function getList(int $page, int $limit, Array $condition = [], $field = '*', $joins = [])
    {
        $query = self::alias("um");
        if (!empty($joins)) {
            foreach($joins as $join) {
                $query->join($join);
            }
        }
        $query->where($condition);
        $count = $query->field($field)->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return compact('count', 'list', 'sql');
    }

    /**
     * 修改登录密码
     *
     * @param string $pwd
     * @return void
     */
    public function changePassword($pwd = '')
    {
        [$password, $salt] = password_check($pwd, '', true);
        return $this->save([
            'password' => $password,
            'salt' => $salt,
            'member_id' => $this->getAttr('member_id')
        ]);
    }

    /**
     * 设置会员标签
     *
     * @param string $ids
     * @return void
     */
    public function changeLabel($ids = '')
    {
        return $this->save([
            'label' => $ids
        ]);
    }

    /**
     * 返回一个虚拟的模板会员信息
     *
     * @return void
     */
    public function getVirtualTemplate()
    {
        $res = [];
        $res['realname'] = $this->getVirtualUserName();
        $res['username'] = $res['realname'];
        $res['nickname'] = Virtual::nickname();
        $res['mobile'] = self::where('is_virtual', 1)->order('member_id DESC')->value('mobile');
        $res['mobile'] = floatval(empty($res['mobile']) ? '13000000000' : $res['mobile']);
        $res['mobile'] += 1;
        $res['mobile'] = $res['mobile'] . "";
        $res['is_virtual'] = 1;
        $res['password'] = '123456';

        return $res;
    }

    /**
     * 返回虚拟用户名
     *
     * @return void
     */
    private function getVirtualUserName()
    {
        $username = Virtual::nickname(2);
        $check = self::where([['username', '=', $username]])->value('member_id');
        while(!empty($check)) {
            $username = Virtual::nickname(2);
            $check = self::where([['username', '=', $username]])->value('member_id');
        }
        return $username;
    }

    /**
     * 购买套餐
     *
     * @param array $data
     * @return void
     */
    public function buyCombo($data = [])
    {
        $combo = MemberCombo::where('id', $data['combo_id'])->find();
        if (empty($combo)) {
            throw new \Exception("套餐不存在!");
        }

        $combo_money = floatval($combo['combo_money']);
        $num = intval($data['num']);
        $order_money = $combo_money * $num;
        if ($data['is_deduct'] == 1) {
            $balance_money = floatval($this->balance_money);
            if ($balance_money < $order_money) {
                throw new \Exception("余额不足!");
            }
        }

        $is_deduct = (int)$data['is_deduct'];
        $pay_type = $is_deduct == 0 ? 'DIRECT' : 'BALANCE';

        $order_model = new OrderCombo();
        $res = $order_model->create_order($num, $combo, $this, $pay_type);

        return $res;
    }

    /**
     * 获取可计费的套餐
     *
     * @return void
     */
    public function getCombo()
    {
        $order_model = new OrderCombo();
        $order = $order_model->getActiveOrder([['member_id', '=', $this->member_id]]);

        return $order;
    }
}