<?php
namespace app\controller\admin;
/**
 * 区域管理
 * 
 * @version 1.0.0
 */
use app\model\system\Area as AreaModel;
use app\model\system\Zipcode;
use app\model\system\Ip as AreaIps;

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

            [$page, $limit] = $this->getPaginator();
            $res = $area->getList($page, $limit, $condition);
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
            try {
                $data = array_keys_filter($this->params, [
                    'areaname',
                    'shortname',
                    ['lat', 0],
                    ['lng', 0],
                    ['parentid', 0],
                    ['zipcode', ''],
                    ['areacode', 0]
                ], true);
                if (!empty($parent)) {
                    $data['parentid'] = $parent['id'];
                    $data['level'] = $parent['level'] + 1;
                    $data['position'] = "{$parent['position']} tr_{$parent['id']}";
                } else {
                    $data['level'] = 1;
                    $data['position'] = 'tr_0';
                }
                $res = $area->addArea($data);
                return $this->success($res);
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $edit = [];
            $edit['parent'] = $parent;
            $this->assign('edit', $edit);
            return $this->fetch('Area/form');
        }
    }

    /**
     * 编辑区域
     *
     * @param AreaModel $area
     * @return void
     */
    public function edit(AreaModel $area)
    {
        $id = (int)$this->params['id'] ?? 0;
        $edit = $area->where("id", $id)->find();
        if (empty($edit)) {
            return $this->error("记录不存在!");
        }

        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'areaname',
                    'shortname',
                    ['lat', 0],
                    ['lng', 0],
                ], true);
                $data['areacode'] = $this->params['areacode'] ?? 0;
                $res = $edit->editArea($data);
                return $this->success($res);
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $edit['parent'] = $area->where("id", $edit['parentid'])->find();
            $edit['zipcode'] = $edit->getZipcode();
            $this->assign('edit', $edit);
            return $this->fetch('Area/form');
        }
    }

    /**
     * 删除区域
     *
     * @param AreaModel $area
     * @return void
     */
    public function delete(AreaModel $area)
    {
        if (IS_AJAX) {
            $id = (int)$this->params['id'] ?? 0;
            $children = $area->where("parentid", $id)->field('id')->find();
            if (!empty($children)) {
                return $this->error("不能删除存在子区域的记录");
            }
            $edit = $area->where("id", $id)->find();
            if (!empty($edit)) {
                $this->logger('logs.sys.area.delete', 'DELETE', $edit->getData());
                $edit->delete();
            }
            return $this->success();
        }
    }

    /**
     * 邮编库
     *
     * @param Zipcode $zipcode
     * @return void
     */
    public function zipcode(Zipcode $zipcode)
    {
        if (IS_AJAX) {
            $condition = [];
            if (!empty($this->params['keyword'])) {
                $condition[] = [ 'area|code', 'LIKE', "%{$this->params['keyword']}%" ];
            }
            [$page, $limit] = $this->getPaginator();
            $data = $zipcode->getList($page, $limit, $condition);
            return $this->success($data);
        } else {
            return $this->fetch();
        }
    }

    /**
     * 添加邮编
     *
     * @param Zipcode $zipcode
     * @return void
     */
    public function zipcode_add(Zipcode $zipcode)
    {
        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'province',
                    'city',
                    'district',
                    'address',
                    'code'
                ], true);
                $res = $zipcode->addZipcode($data);
                $this->logger('logs.sys.zipcode.create', 'CREATEED', $res);
                return $this->success($res);
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->assign('edit', []);
            return $this->fetch('Area/zipcode_form');
        }
    }

    /**
     * 编辑邮编
     *
     * @param Zipcode $zipcode
     * @return void
     */
    public function zipcode_edit(Zipcode $zipcode)
    {
        $id = $this->params['id'] ?? 0;
        $edit = $zipcode->where('id', $id)->find();
        if (empty($edit)) {
            return $this->error("记录不存在");
        }

        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'province',
                    'city',
                    'district',
                    'address',
                    'code'
                ], true);
                $res = $edit->save($data);
                $this->logger('logs.sys.zipcode.create', 'UPDATED', $edit);
                return $this->success($res);
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->assign('edit', $edit);
            return $this->fetch('Area/zipcode_form');
        }
    }

    /**
     * 删除邮编
     *
     * @param Zipcode $zipcode
     * @return void
     */
    public function zipcode_delete(Zipcode $zipcode) 
    {
        if (IS_AJAX) {
            $id = (int)$this->params['id'] ?? 0;
            $edit = $zipcode->where("id", $id)->find();
            if (!empty($edit)) {
                $this->logger('logs.sys.zipcode.delete', 'DELETE', $edit->getData());
                $edit->delete();
            }
            return $this->success();
        }
    }

    /**
     * IP地址库
     *
     * @param AreaIps $ips
     * @return void
     */
    public function ips(AreaIps $ips)
    {
        if (IS_AJAX) {
            $condition = [];
            if (!empty($this->params['keyword'])) {
                $condition[] = [ 'province|city|ip', 'LIKE', "%{$this->params['keyword']}%" ];
            }
            [$page, $limit] = $this->getPaginator();
            $data = $ips->getList($page, $limit, $condition);
            return $this->success($data);
        } else {
            return $this->fetch();
        }
    }

    /**
     * 添加IP地址
     *
     * @param AreaIps $ip
     * @return void
     */
    public function ips_add(AreaIps $ip)
    {
        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'province',
                    'city',
                    'ip'
                ], true);
                $res = $ip->addIp($data);
                $this->logger('logs.sys.ip.create', 'CREATEED', $res);
                return $this->success($res);
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->assign('edit', []);
            return $this->fetch('Area/ips_form');
        } 
    }

    /**
     * 编辑IP地址
     *
     * @param AreaIps $ip
     * @return void
     */
    public function ips_edit(AreaIps $ip)
    {
        $id = $this->params['id'] ?? 0;
        $edit = $ip->where('id', $id)->find();
        if (empty($edit)) {
            return $this->error("记录不存在");
        }

        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'province',
                    'city',
                    'ip'
                ], true);
                $res = $edit->save($data);
                $this->logger('logs.sys.ip.update', 'UPDATED', $edit);
                return $this->success($res);
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->assign('edit', $edit);
            return $this->fetch('Area/ips_form');
        }
    }

    /**
     * 删除IP地址
     *
     * @param AreaIps $ip
     * @return void
     */
    public function ips_delete(AreaIps $ip)
    {
        if (IS_AJAX) {
            $id = (int)$this->params['id'] ?? 0;
            $edit = $ip->where("id", $id)->find();
            if (!empty($edit)) {
                $this->logger('logs.sys.ip.delete', 'DELETE', $edit->getData());
                $edit->delete();
            }
            return $this->success();
        }
    }
}