<?php
namespace app\addons\agent\admin\event;
use app\controller\admin\Controller;
class AgentUpgradeCondition extends Controller
{
    public function handle($item = [])
    {
        $this->assign('list', []);
        $template = dirname(realpath(__DIR__)) . '/view/level/condition.html';
        return $this->fetch($template);
    }
}