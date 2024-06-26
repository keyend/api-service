<?php
/**
 * Api控制器基础类
 * 
 * @version 1.0.0
 */
namespace app\controller\api;
use app\model\system\Area as AreaModel;

class Area extends ApiBase 
{
    /**
     * 显示区域列表
     *
     * @param AreaModel $area
     * @return void
     */
    public function list(AreaModel $area)
    {
        if (IS_AJAX) {
            $pname = $this->params['pname'] ?? '';
            if (!empty($pname)) {
                $this->params['pid'] = $area->where('areaname', $pname)->value('id');
            }
            $condition = [];
            $condition[] = [ 'parentid', '=', $this->params['pid'] ?? 0 ];
            $res = $area->getList(1, 9999, $condition);
            $res['list'] = array_values($res['list']);
            return $this->success($res);
        }
    }
}