<?php
namespace app\addons\member\admin\controller;
/**
 * 会员标签管理
 * 
 * @version 1.0.0
 */
use app\controller\admin\Controller;
use app\addons\member\admin\model\MemberLabel;

class Label extends Controller
{
    /**
     * 标签列表
     *
     * @param MemberLabel $member_label
     * @return void
     */
    public function lists(MemberLabel $member_label)
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->params, [ ['keyword', ''] ]);
            $condition = [];
            if (!empty($filter['keyword'])) {
                $condition[] = [ 'labelname', 'like', "%{$filter['keyword']}%" ];
            }
            [$page, $limit] = $this->getPaginator();
            $data = $member_label->getList($page, $limit, $condition);
            return $this->success($data);
        } else {
            return $this->fetch();
        }
    }

    /**
     * 添加标签
     *
     * @param MemberLabel $member_label
     * @return void
     */
    public function add(MemberLabel $member_label)
    {
        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [ 'labelname', ['remark', ''] ], true);
                $res = $member_label->addLabel($data);
                $this->logger('logs.member.label.create', 'CREATEED', $res);
                return $this->success($res);
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('label/form');
        }
    }

    /**
     * 编辑标签
     *
     * @param MemberLabel $member_label
     * @return void
     */
    public function edit(MemberLabel $member_label)
    {
        $label_id = (int)$this->params['id'] ?? 0;
        $edit = $member_label->where('id', $label_id)->find();
        if (empty($edit)) {
            return $this->error("标签记录不存在!");
        }

        if (IS_POST) {
            try {
                $before = clone $edit;
                $data = array_keys_filter($this->params, [ 'labelname', ['remark', ''] ], true);
                $edit->editLabel($data);
                $this->logger('logs.member.label.update', 'UPDATED', [$before, $edit]);
                return $this->success();
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->assign('item', $edit);
            return $this->fetch('label/form');
        }
    }

    /**
     * 删除标签
     *
     * @param MemberLabel $member_label
     * @return void
     */
    public function delete(MemberLabel $member_label)
    {
        if (IS_AJAX) {
            $id = (int)$this->params['id'] ?? 0;
            $edit = $member_label->where("id", $id)->find();
            if (!empty($edit)) {
                $this->logger('logs.member.label.delete', 'DELETE', $edit->getData());
                $edit->delete();
            }
            return $this->success();
        }
    }
}