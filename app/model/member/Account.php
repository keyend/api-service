<?php
namespace app\model\member;
/**
 * 会员账户
 * @version 1.0.0
 */
use app\Model;
use app\model\system\Ip;
use think\facade\Db;

class Account extends Model
{
    protected $name = 'member_account';

    /**
     * 添加账户
     *
     * @param array $data
     * @param mixed $member_info
     * @param boolean $flag 是否更新账户
     * @return void
     */
    public function addAccount($data = [], $member_info = null, $flag = true)
    {
        $this->startTrans();
        try {
            $data['member_id'] = $data['member_id'] ?? $member_info['member_id'];
            $data['source_member_id'] = $data['source_member_id'] ?? $member_info['member_id'];
            if (empty($member_info) || !($member_info instanceof \app\Model)) {
                $member_info = Member::where('member_id', $data['member_id'])->find();
            }

            $data['username'] = $data['username'] ?? $member_info['username'] ?? '';
            $data['mobile'] = $data['mobile'] ?? $member_info['mobile'] ?? '';
            $data['nickname'] = $data['nickname'] ?? $member_info['nickname'] ?? '';
            $data['address'] = $data['address'] ?? $member_info['full_address'] ?? '';
            $data['trade_no'] = $data['trade_no'] ?? make_trade_no();
            $data['account_type'] = $data['account_type'] ?? 'balance_money';
            $data['account_name'] = $data['account_name'] ?? '消费';
            $data['account_title'] = $data['account_title'] ?? '消费';
            $data['create_time'] = time();
            $data['id'] = $this->insertGetId($data);

            if ($flag) {
                $member_data = ['member_id' => $data['member_id']];
                $member_data[$data['account_type']] = Db::raw("{$data['account_type']} + {$data['value']}");
                $member_info->save($member_data);
            }
    
            $this->logger('member.account.create', 'CREATEED', $data);
            event("MemberAccountChange", $data);

            $this->commit();
        } catch(\Exception $e) {
            Log::error("{$e->getFile()}: {$e->getLine()}");
            $this->rollback();
            throw new \Exception("添加账户失败：{$e->getMessage()}");
        }

        return $data;
    }
}