<?php
namespace app\controller\admin;
/**
 * 后台管理权限管理
 * @package admin.controller.index
 * @version 1.0.0
 */
use app\model\system\Rule as RuleModel;

class Rule extends Controller
{
    /**
     * 权限列表
     *
     * @param RuleModel $rule
     * @return void
     */
    public function lists(RuleModel $rule)
    {
        if (IS_AJAX) {
            [$page, $limit] = $this->getPaginator();
            $data = $rule->getTreeList($page, $limit, [ [ 'module', '=', MODULE ] ], '`rule_id` as id,`title` as `label`,parent_id,name,module,addon,rule,icon,is_page,is_show');
            return $this->success($data);
        }

        return $this->fetch();
    }

    /**
     * 添加权限
     *
     * @param RuleModel $rule
     * @return void
     */
    public function add(RuleModel $rule)
    {
        $id = (int)$this->request->get('id');
        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    ['parent_id', 0],
                    'name',
                    'title',
                    'module',
                    ['addon', ''],
                    ['icon', ''],
                    ['rule', ''],
                    ['is_page', 0],
                    ['is_show', 0],
                    ['sort', 0]
                ], true);
                $data['rule_id'] = $rule->insertGetId($data);
                $this->logger('logs.sys.rule.create', 'CREATEED', $data);
                return $this->success($data);
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $id = $id == 0 ? $rule->getParentId() : $id;
            $edit = ['parent_id' => $id];
            $edit['module'] = MODULE;
            $parentRule = [];
            if ($id != 0) {
                $parentRule = $rule->where('rule_id', $id)->find();
            }
            $this->assign('parentRule', $parentRule);
            $this->assign('edit', $edit);
        }

        return $this->fetch('Rule/form');
    }

    /**
     * 编辑权限
     *
     * @param RuleModel $rule
     * @return void
     */
    public function edit(RuleModel $rule)
    {
        $id = (int)$this->request->get('id');
        $edit = $rule->where([['rule_id', '=', $id]])->find();
        if (empty($edit)) {
            return $this->error('记录不存在!');
        }

        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'name',
                    'title',
                    'module',
                    ['addon', ''],
                    ['icon', ''],
                    ['rule', ''],
                    ['is_page', 0],
                    ['is_show', 0],
                    ['sort', 0]
                ], true);
                $data = array_merge($edit->toArray(), $data);
                $edit->update($data);
                $this->logger('logs.sys.rule.update', 'UPDATED', $data);
                return $this->success($data);
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            if ($id != 0) {
                $parentRule = $rule->find($edit['parent_id']);
            }
            $this->assign('parentRule', $parentRule);
            $this->assign('edit', $edit);
        }

        return $this->fetch('Rule/form');
    }

    /**
     * 删除权限
     *
     * @param RuleModel $rule
     * @return void
     */
    public function delete(RuleModel $rule)
    {
        $id = (int)$this->request->param('id');
        $rule = $rule->where('rule_id', (int)$id)->find();
        if (!$rule) {
            return $this->fail('记录不存在!');
        }

        $this->logger('logs.sys.rule.delete', 'DELETE', $rule);
        $rule->delete();
        return $this->success();
    }
}