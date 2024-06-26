<?php
namespace app\addons\member\admin\controller;
/**
 * 会员管理
 * 
 * @version 1.0.0
 */
use app\controller\admin\Controller;
use app\addons\member\admin\model\Member as MemberModel;
use app\addons\member\admin\model\MemberAccount;
use app\addons\member\admin\model\MemberLabel;
use app\addons\member\admin\model\MemberLevel;
use app\addons\member\admin\model\MemberCombo;

class Member extends Controller
{
    /**
     * 会员列表
     *
     * @param MemberModel $member
     * @param MemberCombo $combo_model
     * @param MemberLevel $level_model
     * @return void
     */
    public function lists(MemberModel $member, MemberCombo $combo_model, MemberLevel $level_model)
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->params, [
                ['keyword', ''], 
                ['level_id', ''],
                ['regtime', ' - '] 
            ]);
            $condition = [];
            if (!empty($filter['keyword'])) {
                $condition[] = [ 'um.username|um.nickname|um.mobile|um.realname|um.email', 'like', "%{$filter['keyword']}%" ];
            }
            if (!empty($filter['level_id'])) {
                $condition[] = [ 'um.level_id', '=', $filter['level_id'] ];
            }
            range_date($filter['regtime'], function($date) use(&$condition) {
                if (!empty($date[0]) && !empty($date[1])) {
                    $condition[] = [ 'um.create_time', 'BETWEEN', $date ];
                } elseif(!empty($date[0])) {
                    $condition[] = [ 'um.create_time', '>' , $date[0] ];
                } elseif(!empty($date[1])) {
                    $condition[] = [ 'um.create_time', '<' , $date[1] ];
                }
            });
            [$page, $limit] = $this->getPaginator();
            $data = $member->getListPage($page, $limit, $condition);
            return $this->success($data);
        } else {
            $combo_list = $combo_model->getComboList();
            $this->assign('combo_list', $combo_list);
            $level_list = $level_model->getLevelList();
            $this->assign("level_list", $level_list);
            return $this->fetch();
        }
    }

    /**
     * 添加新会员
     *
     * @param MemberModel $member
     * @return void
     */
    public function add(MemberModel $member)
    {
        if (IS_POST) {
            try {
                $res = $member->addMember($this->params);
                $this->logger('logs.member.create', 'CREATEED', $res);
                return $this->success($res);
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $default = $member->getVirtualTemplate();
            if (IS_AJAX) {
                return $this->success([
                    'default' => $default,
                    'item' => []
                ]);
            } else {
                $this->assign('default', $default);
                $this->assign('item', []);
                return $this->fetch();
            }
        }
    }

    /**
     * 编辑会员
     *
     * @param MemberModel $member
     * @param MemberLevel $level_model
     * @return void
     */
    public function edit(MemberModel $member, MemberLevel $level_model)
    {
        $id = (int)$this->params['id'] ?? 0;
        $edit = $member->where("member_id", $id)->find();
        if (IS_POST) {
            try {
                $data = [];
                $data[$this->params['name']] = $this->params['value'];
                $data = array_keys_filter($data, [ 
                    'avatar',
                    'username',
                    'mobile',
                    'nickname', 
                    'realname', 
                    'remark', 
                    'province_id',
                    'is_virtual', 
                    'city_id',
                    'street_id',
                    'district_id',
                    'level_id',
                    'longitude',
                    'latitude',
                    'address',
                    'full_address'
                ]);

                if (isset($data['level_id']) && $data['level_id'] != $edit->level_id) {
                    $level = $level_model->where('level', $data['level_id'])->find();
                    $data['levelname'] = $level->levelname;
                }

                if (!empty($data)) {
                    $edit->save($data);
                }
                return $this->success();
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $edit = $edit->toArray();
            $edit['label_list'] = !empty($edit['label']) ? MemberLabel::where('id', 'IN', $edit['label'])->column("id,labelname", "id") : [];
            $level_list = $level_model->getLevelList();
            $this->assign("level_list", $level_list);
            $this->assign("item", $edit);
            return $this->fetch();
        }
    }

    /**
     * 删除会员
     *
     * @param MemberModel $member
     * @return void
     */
    public function delete(MemberModel $member)
    {
        if (IS_AJAX) {
            try {
                $id = (int)$this->params['id'] ?? 0;
                $edit = $member->where("member_id", $id)->find();
                if (!empty($edit)) {
                    $this->logger('logs.member.delete', 'DELETE', $edit->getData());
                    $edit->delete();
                }
                return $this->success();
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        }
    }

    /**
     * 修改登录密码
     *
     * @param MemberModel $member
     * @return void
     */
    public function change_pwd(MemberModel $member)
    {
        if (IS_AJAX) {
            try {
                $data = array_keys_filter($this->params, [ 'id', 'password' ], true);
                $member_info = $member->where('member_id', '=', $data['id'])->find();
                if (empty($member_info)) {
                    return $this->error("会员不存在");
                }
                $member_info->changePassword($data['password']);
                return $this->success();
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        }
    }

    /**
     * 会员账户充值
     *
     * @param MemberModel $member
     * @param Account $account
     * @return void
     */
    public function recharge(MemberModel $member, MemberAccount $account)
    {
        if (IS_AJAX) {
            try {
                array_keys_filter($this->params, ['id', 'amount'], true);
                $data = array_keys_filter($this->params, [ ['member_id', $this->params['id'] ?? 0], ['value', $this->params['amount'] ?? 0], ['remark', ''] ]);
                $member_info = $member->where('member_id', '=', $data['member_id'])->find();
                if (empty($member_info)) {
                    return $this->error("会员不存在");
                }
                $data['value'] = (float)$data['value'];
                if ($data['value'] > 0) {
                    $data['account_name'] = '充值';
                    $data['account_title'] = '后台充值';
                    $res = $account->addAccount($data, $member_info);
                    if (empty($res)) {
                        return $this->error("操作失败！");
                    }
                }
                return $this->success($res);
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        }
    }

    /**
     * 推荐人变更
     *
     * @param MemberModel $member
     * @return void
     */
    public function modify_parent(MemberModel $member)
    {
        $member_id = (int)$this->params['id']??0;
        $member_info = $member->where("member_id", $member_id)->find();
        $member_info['parent'] = [];
        if (empty($member_info)) {
            return $this->error("会员不存在!");
        } elseif($member_info['parent_id'] != 0) {
            $member_info['parent'] = $member->where('member_id', $member_info['parent_id'])->find();
        }

        if (IS_POST) {
            $unlink = (int)$this->params['unlink'] ?? 0;
            $parent_id = $unlink === 1 ? 0 : intval($this->params['parent_id']);
            if (false !== $member_info->changeParent($parent_id)) {
                return $this->success();
            } else {
                return $this->error("更新失败!");
            }
        } elseif (IS_AJAX) {
            $filter = array_keys_filter($this->params, [ ['keyword', ''] ]);
            $condition = [];
            $condition[] = ['um.member_id', '<>', $member_info['member_id']];
            if (!empty($filter['keyword'])) {
                $condition[] = [ 'um.username|um.nickname|um.mobile|um.realname|um.email', 'like', "%{$filter['keyword']}%" ];
            } elseif(!empty($member_info['parent'])) {
                $condition[] = [ 'um.member_id', '=', $member_info['parent']['member_id']];
            } else {
                $condition[] = [ 'um.member_id', '=', '-1'];
            }
            [$page, $limit] = $this->getPaginator();
            $data = $member->getList($page, $limit, $condition);
            return $this->success($data);
        } else {
            $this->assign('info', $member_info);
            return $this->fetch('member/recomment');
        }
    }

    /**
     * 获取团队关系图
     *
     * @param MemberModel $member
     * @return void
     */
    public function tree(MemberModel $member)
    {
        $member_id = $this->params['id'] ?? 0;
        $member_info = $member->getMember([ ['member_id', '=', $member_id] ]);
        if (IS_POST) {
            $member_info->rebuildMaps();
            return $this->success();
        } elseif(IS_AJAX) {
            $memberMaps = $member_info->maps['maps'];
            $memberMaps.= ",{$member_id}";
            $memberMaps = array_filter(explode(',', $memberMaps));
            $data = $member->getList(1, 999999, [ ['um.member_id', 'IN', $memberMaps] ], 'um.member_id,um.username,um.nickname,um.mobile,um.email,um.parent_id');
            if ($data['count'] > 1) {
                $data['list'] = parseTree($data["list"], 'member_id', 'parent_id', 'children');
            }
            return $this->success($data['list']);
        }
    }

    /**
     * 设置会员标签
     *
     * @param MemberModel $member
     * @return void
     */
    public function label(MemberModel $member)
    {
        if (IS_AJAX) {
            try {
                $data = array_keys_filter($this->params, [ 'id', ['label_ids', ''] ]);
                $member_id = $this->params['id'] ?? 0;
                $member_info = $member->getMember([ ['member_id', '=', $member_id] ]);
                if (empty($member_info)) {
                    return $this->error("会员不存在!");
                }
                $member_info->changeLabel($data['label_ids']);
                return $this->success();
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        }
    }

    /**
     * 会员选择器
     *
     * @return void
     */
    public function select(MemberModel $member)
    {
        $member_id = intval($this->params['id'] ?? 0);
        if (IS_AJAX) {
            $filter = array_keys_filter($this->params, [ ['keyword', ''], ['member_id', 0] ]);
            $condition = [];
            if ($filter['member_id'] !== 0) {
                $condition[] = [ 'um.member_id', '<>', $filter['member_id'] ];
            }
            if (!empty($filter['keyword'])) {
                $condition[] = [ 'um.username|um.nickname|um.mobile|um.realname|um.email', 'like', "%{$filter['keyword']}%" ];
            }
            [$page, $limit] = $this->getPaginator();
            $data = $member->getListPage($page, $limit, $condition);
            if (!empty($data['list'])) {
                foreach($data['list'] as &$row) {
                    if ($row['member_id'] == $member_id) {
                        $row['LAY_CHECKED'] = true;
                    }
                }
            }
            return $this->success($data);
        } else {
            $this->assign('member_id', $member_id);
            return $this->fetch();
        }
    }

    /**
     * 设置状态
     *
     * @param MemberModel $member
     * @return void
     */
    public function status(MemberModel $member)
    {
        if (IS_AJAX) {
            $id = (int) $this->params['id'] ?? 0;
            $member = $member->find($id);
            if (!empty($member)) {
                $member->status = (int) $this->params['status'] ?? 0;
                $member->save();
            }
            return $this->success();
        }
    }

    /**
     * 购买套餐
     *
     * @param MemberModel $member_model
     * @return void
     */
    public function buy_combo(MemberModel $member_model)
    {
        if (IS_POST) {
            $data = array_keys_filter($this->params, [
                'combo_id',
                'num',
                ['is_deduct', 0]
            ], true);
            $member_info = $member_model->getMember([ ['member_id', '=', $this->params['member_id']] ]);
            if (empty($member_info)) {
                return $this->error("会员不存在!");
            }
            $res = $member_info->buyCombo($data);
            return $this->success($res);
        }
    }
}