<?php
namespace app\addons\agent\admin\controller;
/**
 * 代理商
 * 
 * @version 1.0.0
 */
use app\controller\admin\Controller;
use app\addons\agent\admin\model\Agent as AgentModel;
use app\addons\agent\admin\model\AgentLevel;

class Agent extends Controller
{
    /**
     * 代理商列表
     *
     * @param AgentModel $agent_model
     * @return void
     */
    public function lists(AgentModel $agent_model)
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->params, [ ['keyword', ''] ]);
            $condition = [];
            [$page, $limit] = $this->getPaginator();
            $data = $agent_model->getList($page, $limit, $condition);
            return $this->success($data);
        } else {
            return $this->fetch();
        }
    }

    /**
     * 添加代理商
     *
     * @param AgentModel $agent_model
     * @return void
     */
    public function add(AgentModel $agent_model, AgentLevel $level_model)
    {
        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'level_id',
                    'member_id'
                ], true);
                $res = $agent_model->addAgent($data);
                $this->logger('logs.agent.create', 'CREATEED', $res);
                return $this->success($res);
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $level_list = $level_model->getLevelList();
            $this->assign("item", []);
            $this->assign("level_list", $level_list);
            return $this->fetch('agent/form');
        }
    }
}