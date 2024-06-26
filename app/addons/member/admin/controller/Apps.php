<?php
namespace app\addons\member\admin\controller;
/**
 * 会员应用管理
 * 
 * @version 1.0.0
 */
use app\controller\admin\Controller;
use app\addons\member\admin\model\Apps as AppsModel;

class Apps extends Controller
{
    /**
     * 应用列表
     *
     * @param AppsModel $apps_model
     * @return void
     */
    public function lists(AppsModel $apps_model)
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->params, [ ['keyword', ''] ]);
            $condition = [];
            $condition[] = ['member_id', '=', $this->params['member_id'] ?? 0];
            if (!empty($filter['keyword'])) {
                $condition[] = [ 'app_name', 'like', "%{$filter['keyword']}%" ];
            }
            [$page, $limit] = $this->getPaginator();
            $data = $apps_model->getList($page, $limit, $condition);
            return $this->success($data);
        } else {
            return $this->fetch();
        }
    }

    /**
     * 添加应用
     *
     * @param AppsModel $apps_model
     * @return void
     */
    public function add(AppsModel $apps_model)
    {
        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [ 'app_name', 'member_id', ['remark', ''] ], true);
                $res = $apps_model->addApp($data);
                $this->logger('logs.member.app.create', 'CREATEED', $res);
                return $this->success($res);
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('apps/form');
        }
    }

    /**
     * 编辑应用
     *
     * @param AppsModel $apps_model
     * @return void
     */
    public function edit(AppsModel $apps_model)
    {
        $app_id = (int)$this->params['id'] ?? 0;
        $item = $apps_model->where('id', $app_id)->find();
        if (empty($item)) {
            return $this->error("应用不存在!");
        }

        if (IS_POST) {
            try {
                $before = clone $item;
                $data = array_keys_filter($this->params, [ ['remark', ''] ], true);
                $item->editApp($data);
                $this->logger('logs.member.app.update', 'UPDATED', [$before, $edit]);
                return $this->success();
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->assign('item', $item);
            return $this->fetch('apps/form');
        }
    }

    /**
     * 删除应用
     *
     * @param AppsModel $apps_model
     * @return void
     */
    public function del(AppsModel $apps_model)
    {
        if (IS_AJAX) {
            $app_id = (int)$this->params['id'] ?? 0;
            $item = $apps_model->where('id', $app_id)->find();
            if (!empty($item)) {
                $this->logger('logs.member.apps.delete', 'DELETE', $item->getData());
                $item->delete();
            }
            return $this->success();
        }
    }
}