<?php
namespace app\addons\agent\admin\controller;
/**
 * 代理商
 * 
 * @version 1.0.0
 */
use app\controller\admin\Controller;
use app\addons\agent\admin\model\Agent as AgentModel;

class Agent extends Controller
{
    /**
     * 代理商列表
     *
     * @param AgentModel $agent
     * @return void
     */
    public function lists(AgentModel $agent)
    {
        if (IS_AJAX) {

        } else {
            return $this->fetch();
        }
    }
}