<?php
namespace app\addons\member\admin\controller;
/**
 * 套餐资费管理
 * 
 * @version 1.0.0
 */
use app\controller\admin\Controller;
use app\addons\member\admin\model\MemberCombo;

class Combo extends Controller
{
    /**
     * 套餐列表
     *
     * @param MemberCombo $combo_model
     * @return void
     */
    public function lists(MemberCombo $combo_model)
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->params, [ ['keyword', ''] ]);
            $condition = [];
            if (!empty($filter['keyword'])) {
                $condition[] = [ 'combo', 'like', "%{$filter['keyword']}%" ];
            }
            [$page, $limit] = $this->getPaginator();
            $data = $combo_model->getList($page, $limit, $condition);
            foreach($data['list'] as &$row) {
                $row['bill_method_name'] = $combo_model->getMethodName($row['bill_method']);
            }
            return $this->success($data);
        } else {
            return $this->fetch();
        }
    }

    /**
     * 添加套餐
     *
     * @param MemberCombo $combo_model
     * @return void
     */
    public function add(MemberCombo $combo_model)
    {
        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'combo', 
                    ['bill_method', 'times'], 
                    ['combo_money', 0],
                    ['times', 0],
                    ['days', 0],
                    ['months', 0],
                    ['years', 0],
                    ['remark', '']
                ], true);
                $res = $combo_model->addCombo($data);
                $this->logger('logs.member.combo.create', 'CREATEED', $res);
                return $this->success($res);
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $bill_method_list = $combo_model->getBillMethodList();
            $this->assign('bill_method_list', $bill_method_list);
            return $this->fetch('combo/form');
        }
    }

    /**
     * 编辑套餐
     *
     * @param MemberCombo $combo_model
     * @return void
     */
    public function edit(MemberCombo $combo_model)
    {
        $id = (int) $this->params['id'] ?? 0;
        $combo = $combo_model->find($id);
        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'combo', 
                    ['bill_method', 'times'], 
                    ['combo_money', 0],
                    ['times', 0],
                    ['days', 0],
                    ['months', 0],
                    ['years', 0],
                    ['remark', '']
                ], true);
                $combo->save($data);
                $this->logger('logs.agent.combo.edit', 'UPDATED', $level);
                return $this->success($level);
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $bill_method_list = $combo_model->getBillMethodList();
            $this->assign('bill_method_list', $bill_method_list);
            $this->assign("item", $combo);
            return $this->fetch('combo/form');
        }
    }

    /**
     * 设置状态
     *
     * @param MemberCombo $combo_model
     * @return void
     */
    public function status(MemberCombo $combo_model)
    {
        if (IS_AJAX) {
            $id = (int) $this->params['id'] ?? 0;
            $combo = $combo_model->find($id);
            if (!empty($combo)) {
                $combo->status = (int) $this->params['status'] ?? 0;
                $combo->save();
            }
            return $this->success();
        }
    }


    /**
     * 删除套餐
     *
     * @param MemberCombo $combo_model
     * @return void
     */
    public function del(MemberCombo $combo_model)
    {
        if (IS_AJAX) {
            $id = (int) $this->params['id'] ?? 0;
            $combo = $combo_model->find($id);
            if (!empty($combo)) {
                $this->logger('logs.member.label.delete', 'DELETE', $combo->getData());
                $combo->delete();
            }
            return $this->success();
        }
    }
}