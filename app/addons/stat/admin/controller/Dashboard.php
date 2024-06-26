<?php
namespace app\addons\stat\admin\controller;
/**
 * 仪表盘
 * 
 * @version 1.0.0
 */
use app\controller\admin\Controller;
use app\addons\stat\admin\model\MemberLabel;
use app\addons\stat\admin\model\Order;
use app\addons\stat\admin\model\ProtocolLogs;

class Dashboard extends Controller
{
    /**
     * 仪表盘
     *
     * @return void
     */
    public function index()
    {
        if (request()->isAjax()) {
            $patch = input("patch", "Dashboard");
            $method = "get" . ucfirst($patch);
            $res = $this->$method();
            return $this->success($res);
        } else {
            return $this->fetch();
        }
    }

    /**
     * 数据概览
     *
     * @return void
     */
    private function getDashboard()
    {
        return (new Order())->getDashboardData();
    }

    /**
     * 柱形图
     *
     * @return void
     */
    private function getShape()
    {
        return (new ProtocolLogs())->getShapeData();
    }

    /**
     * 饼形图
     *
     * @return void
     */
    private function getPie()
    {
        return (new ProtocolLogs())->getPieData();
    }

    /**
     * 业务时间线
     *
     * @return void
     */
    private function getOrder()
    {
        return (new Order())->getOrderLineData();
    }
}