<?php
namespace app\addons\protocol\admin\controller;
/**
 * 接口产品中心
 * 
 * @version 1.0.0
 */
use app\controller\admin\Controller;
use app\addons\protocol\admin\model\Protocol as ProtocolModel;

class Protocol extends Controller
{
    /**
     * 接口列表
     *
     * @param ProtocolModel $protocol_model
     * @return void
     */
    public function lists(ProtocolModel $protocol_model)
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->params, [ ['keyword', ''] ]);
            $condition = [];
            if (!empty($filter['keyword'])) {
                $condition[] = [ 'labelname', 'like', "%{$filter['keyword']}%" ];
            }
            [$page, $limit] = $this->getPaginator();
            $data = $protocol_model->getList($page, $limit, $condition);
            return $this->success($data);
        } else {
            return $this->fetch();
        }
    }

    /**
     * 添加等级
     *
     * @param ProtocolModel $protocol_model
     * @return void
     */
    public function add(ProtocolModel $protocol_model)
    {
        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'protocol',
                    'method',
                    'uri',
                    ['param', []],
                    ['remark', '']
                ], true);
                $data['param'] = $data['param'] ?? [];
                $res = $protocol_model->addProtocol($data);
                $this->logger('logs.protocol.create', 'CREATEED', $res);
                return $this->success($res);
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->assign("item", []);
            return $this->fetch('protocol/form');
        }
    }

    /**
     * 接口编辑
     *
     * @param ProtocolModel $protocol_model
     * @return void
     */
    public function edit(ProtocolModel $protocol_model)
    {
        $id = (int) $this->params['id'] ?? 0;
        $protocol = $protocol_model->where('id', $id)->find();
        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'protocol',
                    'method',
                    'uri',
                    ['param', []],
                    ['remark', '']
                ], true);
                $protocol->save($data);
                $this->logger('logs.protocol.edit', 'UPDATED', $protocol);
                return $this->success($protocol);
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->assign("item", $protocol);
            return $this->fetch('protocol/form');
        }
    }

    /**
     * 设置状态
     *
     * @param AgentLevel $level_model
     * @return void
     */
    public function status(AgentLevel $level_model)
    {
        if (IS_AJAX) {
            $level_id = (int) $this->params['level'] ?? 0;
            $level = $level_model->where([['level', '=', $level_id]])->find();
            if (!empty($level)) {
                $level->status = (int) $this->params['status'] ?? 0;
                $level->save();
            }
            return $this->success();
        }
    }
}