<?php
namespace app\addons\member\admin\controller;
use app\controller\admin\Controller;
use app\addons\member\admin\model\MemberLevel;

class Level extends Controller
{
    /**
     * 会员等级列表
     *
     * @param MemberLevel $level_model
     * @return void
     */
    public function lists(MemberLevel $level_model) 
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->params, [ ['keyword', ''] ]);
            $condition = [];
            [$page, $limit] = $this->getPaginator();
            $data = $level_model->getList($page, $limit, $condition);
            $level_count = env('member.level_count', 3);
            $mapper = array_column($data['list'], null, 'level');
            $list = [];
            $i = 0;
            $next_id = false;
            while($i ++ < $level_count) {
                if (isset($mapper[$i])) {
                    $list[] = $mapper[$i];
                } else {
                    $list[] = [
                        'level_id' => 0,
                        'level' => $i,
                        'levelname' => '',
                        'total_num' => '',
                        'is_default' => 0,
                        'is_add' => $next_id ? 0 : 1
                    ];
                    if ($next_id === false) {
                        $next_id = true;
                    }
                }
            }
            $data['list'] = $list;
            return $this->success($data);
        } else {
            return $this->fetch();
        }
    }

    /**
     * 添加等级
     *
     * @param MemberLevel $level_model
     * @return void
     */
    public function add(MemberLevel $level_model)
    {
        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'level',
                    'levelname',
                    ['is_upgrade', 0],
                    ['is_default', 0],
                    ['growth', 0],
                    ['status', 1],
                    ['give_money', 0],
                    ['give_growth', 0],
                    ['remark', '']
                ], true);
                $res = $level_model->addLevel($data);
                $this->logger('logs.agent.level.create', 'CREATEED', $res);
                return $this->success($res);
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->assign("item", []);
            return $this->fetch('level/form');
        }
    }

    /**
     * 等级编辑
     *
     * @param MemberLevel $level_model
     * @return void
     */
    public function edit(MemberLevel $level_model)
    {
        $level_id = (int) $this->params['level'] ?? 0;
        $level = $level_model->where([['level', '=', $level_id]])->find();
        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'levelname',
                    ['is_upgrade', 0],
                    ['is_default', 0],
                    ['growth', 0],
                    ['status', 1],
                    ['give_money', 0],
                    ['give_growth', 0],
                    ['remark', '']
                ], true);
                $level->save($data);
                $this->logger('logs.agent.level.edit', 'UPDATED', $level);
                return $this->success($level);
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->assign("item", $level);
            return $this->fetch('level/form');
        }
    }

    /**
     * 设置状态
     *
     * @param MemberLevel $level_model
     * @return void
     */
    public function status(MemberLevel $level_model)
    {
        if (IS_AJAX) {
            $level_id = (int) $this->params['level'] ?? 0;
            $level = $level_model->where([['level', '=', $level_id]])->find();
            if (!empty($level)) {
                $level->status = (int) $this->params['status'] ?? 0;
                $level->save();
            }
            return $this->success();
        }
    }


    /**
     * 删除等级
     *
     * @param MemberLabel $member_label
     * @return void
     */
    public function del(MemberLevel $level_model)
    {
        if (IS_AJAX) {
            $level_id = (int)$this->params['level'] ?? 0;
            $level = $level_model->where([['level', '=', $level_id]])->find();
            if (!empty($level)) {
                $this->logger('logs.member.label.delete', 'DELETE', $level->getData());
                $level->delete();
            }
            return $this->success();
        }
    }
}