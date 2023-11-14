<?php
namespace app\controller\admin;
/**
 * 区域管理
 * 
 * @version 1.0.0
 */
use app\model\system\Area as AreaModel;
use app\model\system\AreaZipCode;
use app\model\system\AreaIps;

class Area extends Controller
{
    /**
     * 地区管理
     *
     * @param AreaModel $area
     * @return void
     */
    public function lists(AreaModel $area)
    {
        if (IS_AJAX) {
            $keyword = $this->params['keyword'] ?? '';
            $condition = [];
            $parent_id = (int)$this->params['parent_id'] ?? 0;
            $level = (int)$this->params['level'] ?? 0;
            $level += 1;
            if (!empty($keyword)) {
                $condition[] = [ 'shortname|areaname', 'like', "%{$keyword}%" ];
            } else {
                $condition[] = ['parentid', '=', $parent_id];
                $condition[] = ['level', '=', $level];
            }

            $res = $area->getList(1, 999999, $condition);
            if (!empty($res['list'])) {
                if (empty($keyword)) {
                    if ($level === 3) {
                        $pids = array_values(array_column($res['list'], "id"));
                        $parents = $area->where('parentid', 'IN', $pids)->column('id', 'parentid');
                    }
                    foreach($res['list'] as &$row) {
                        if ($level > 3) {
                            $row['isParent'] = false;
                        } elseif ($level === 3) {
                            $row['isParent'] = isset($parents[$row['id']]);
                        } else {
                            $row['isParent'] = true;
                        }
                    }
                    $res['list'] = array_values($res['list']);
                } else {
                    $res['list'] = array_values($res['list']);
                }
            }
            return $this->success($res);
        } else {
            return $this->fetch();
        }
    }

    /**
     * 新增区域
     *
     * @param AreaModel $area
     * @return void
     */
    public function add(AreaModel $area)
    {
        $parent_id = (int)$this->params['id'] ?? 0;
        $parent = $parent_id !== 0 ? $area->where('id', $parent_id)->find() : [];
        if (IS_POST) {
            $data = $this->params;
            if (!empty($parent)) {
                $data['parentid'] = $parent['id'];
                $data['level'] = $parent['level'] + 1;
                $data['position'] .= " tr_{$parent['id']}";
            }
        } else {
            $edit = [];
            $edit['parent'] = $parent;
            $edit['zipcode'] = [];
            $this->assign('edit', $edit);
            return $this->fetch('Area/form');
        }
    }
}