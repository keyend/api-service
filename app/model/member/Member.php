<?php
namespace app\model\member;
/**
 * 会员
 * @version 1.0.0
 */
use app\Model;
use app\model\system\Ip;
use app\model\order\OrderCombo;
use think\facade\Db;
use think\facade\Log;

class Member extends Model
{
    protected $name = 'member';
    protected $pk = 'member_id';

    /**
     * 推荐人
     * @collection relation.model
     */
    public function source()
    {
        return $this->hasOne(Member::class, 'member_id', 'parent_id');
    }

    /**
     * 关系图
     * @return void
     */
    public function relate_maps()
    {
        return $this->hasOne(Maps::class, 'member_id', 'member_id');
    }

    /**
     * 删除记录
     * @access public
     * @param mixed $data 表达式 true 表示强制删除
     * @return int
     * @throws Exception
     */
    public function delete($data = null): bool
    {
        event("MemberTrash", $this->getData());
        return parent::delete($data);
    }

    /**
     * 保存当前数据对象
     * @access public
     * @param array  $data     数据
     * @param string $sequence 自增序列名
     * @return bool
     */
    public function save(array $data = [], string $sequence = null): bool
    {
        $this->startTrans();
        try {
            $data['update_time'] = time();
            $before = $this->getData();
            $res = parent::save($data, $sequence);
            $after = $this->getData();
            event("MemberUpdate", [$before, $after]);
            $this->commit();
            $this->logger('logs.member.edit', 'UPDATED', [$before, $after]);
            return $res;
        } catch(\Exception $e) {
            Log::error("{$e->getFile()}:{$e->getLine()}");
            Log::error("保存失败: {$e->getMessage()}");
            $this->rollback();
            return false;
        }
    }

    /**
     * 获取会员
     *
     * @param array $filter
     * @return void
     */
    public function getMember($filter = [])
    {
        return $this->where($filter)->find();
    }

    /**
     * 添加新会员
     *
     * @param array $data
     * @return void
     */
    public function addMember($data = []) 
    {
        $data = array_keys_filter($data, [
            ['username', uniqid('u_')],
            'password',
            'nickname',
            'mobile',
            ['parent_id', 0],
            ['is_virtual', 0],
            ['avatar', ''],
            ['realname', ''],
            ['remark', '']
        ], true);

        if (!empty($this->where('username', $data['username'])->field("member_id")->find())) {
            throw new \Exception("用户名已存在!");
        } elseif (!empty($this->where('mobile', $data['mobile'])->field("member_id")->find())) {
            throw new \Exception("该手机号已注册!");
        } elseif (!empty($this->where('nickname', $data['nickname'])->field("member_id")->find())) {
            throw new \Exception("该呢称已使用!");
        }

        $this->startTrans();
        try {
            $data['create_time'] = TIMESTAMP;
            $res = password_check($data['password'], '', true);
            [$data['password'], $data['salt']] = password_check($data['password'], '', true);
    
            $address = (new Ip())->getAddress(request()->ip());
            if (!empty($address)) {
                $data['full_address'] = $address['address'];
                $data['province_id'] = $address['province_id'];
                $data['city_id'] = $address['city_id'];
                $data['district_id'] = $address['district_id'];
                $data['longitude'] = $address['longitude'];
                $data['latitude'] = $address['latitude'];
            }
            $data['h5_openid'] = base58_encode(uniqid("u_"));
            $data['member_id'] = $this->insertGetId($data);
            event("MemberRegister", $data);
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw new \Exception($e->getMessage());
        }

        return $data;
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
     * 修改推荐人
     *
     * @param integer $parent_id
     * @return void
     */
    public function changeParent($parent_id = 0)
    {
        return $this->save([
            'parent_id' => $parent_id
        ]);
    }

    /**
     * 叠加结构
     *
     * @param array $maps
     * @param boolean $superposition
     * @return void
     */
    public function dispatchMaps($maps = [], $superposition = 0)
    {
        $memberMaps = (new Maps())->getMaps([ ['member_id', '=', $this->getAttr('member_id')] ]);
        if ($superposition > 0) {
            $memberOriginMaps = explode(",", $memberMaps->maps);
            $updateMaps = array_filter(array_unique(array_merge($memberOriginMaps, $maps)));
            $memberMaps->companion = Db::raw("companion + {$superposition}");
        } else {
            $updateMaps = str_replace(implode(",", $maps), '', $memberMaps->maps);
            $updateMaps = array_filter(explode(",", $updateMaps));
            $memberMaps->companion = Db::raw("companion {$superposition}");
        }
        $memberMaps->maps = implode(",", $updateMaps);
        $memberMaps->save();
    }

    /**
     * 切换推荐人
     *
     * @param integer $member_id
     * @param integer $before_id
     * @param integer $after_id
     * @return void
     */
    public function switchReferrals($member_id = 0, $before_id = 0, $after_id = 0)
    {
        $maps = (new Maps())->getMaps([ ['member_id', '=', $member_id] ]);
        $memberMaps = !empty($maps['maps']) ? explode(",", $maps['maps']) : [];
        $memberMaps[] = $member_id;
        $length = count($memberMaps);
        if ($before_id != 0) {
            $length = 0 - $length;
            $maps->ascending_decrement($before_id, $length, $memberMaps);
        }

        if ($after_id != 0) {
            $maps->ascending_decrement($after_id, $length, $memberMaps);
        }
    }

    /**
     * 重建结构
     *
     * @return void
     */
    public function rebuildMaps() 
    {
        $maps = $this->getAttr('maps');
        $memberMaps = !empty($maps['maps']) ? explode(",", $maps['maps']) : [];
        $length = 0 - count($memberMaps);
        $parent_id = $this->getAttr('parent_id');
        $maps->ascending_decrement($parent_id, $length, $memberMaps);
        $memberMaps = $maps->rebuild();
        $length = count($memberMaps);
        $maps->ascending_decrement($parent_id, $length, $memberMaps);
    }

    /**
     * 验证是否有调用权限
     *
     * @return void
     */
    public function hasComboQuotaLimit()
    {
        $condition = [['member_id', '=', $this->member_id]];
        $combo_list = OrderCombo::where($condition)->order('id ASC')->select();
        $err_flag = 'NO_MATCH';
        if (!empty($combo_list)) {
            $timestamp = time();
            foreach ($combo_list as $combo) {
                if ($combo['combo_method'] == 'times') {
                    if ($combo['times'] > 0) {
                        return $combo;
                    }
                    $err_flag = 'NO_TIMES';
                } else {
                    if ($timestamp <= $combo['expire_time']) {
                        return $combo;
                    }
                    $err_flag = 'SH_EXPIRE';
                }
            }

            return $err_flag;
        }

        return null;
    }
}