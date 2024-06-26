<?php
namespace app\controller\admin;
/**
 * 日志管理
 * 
 * @version 1.0.0
 */
use app\model\system\Logs as LogsModel;
use app\model\system\LogsTemplate;

class Logs extends Controller
{
    /**
     * 日志列表
     *
     * @param LogsModel $logs
     * @return void
     */
    public function index(LogsModel $logs)
    {
        if (IS_AJAX) {
            $labels = [
                'login' => ['LOGGED', 'REFRESH', 'LOGOUT'],
                'operator' => ['CREATEED', 'UPDATED', 'DELETE', 'UPDATED']
            ];
            $filter = array_keys_filter($this->request->param(), [
                ['type', 'operator'],
                ['date', ''],
                ['keyword', 0]
            ]);
            $filter['labels'] = $labels[$filter['type']];
            [$page, $limit] = $this->getPaginator();
            $data = $logs->getList($page, $limit, $filter);
            return $this->success($data);
        } elseif(checkAccess('sysLogsOperator')) {
            return redirect(url("sysLogsOperator")->build());
        } elseif(checkAccess('sysLogsLogin')) {
            return redirect(url("sysLogsLogin")->build());
        } elseif(checkAccess('sysLogsTemplate')) {
            return redirect(url("sysLogsTemplate")->build());
        }
    }

    /**
     * 操作日志
     *
     * @return void
     */
    public function lists()
    {
        $this->assign('type', 'operator');
        return $this->fetch('Logs/index');
    }

    /**
     * 登录日志
     *
     * @return void
     */
    public function login()
    {
        $this->assign('type', __FUNCTION__);
        return $this->fetch('Logs/index');
    }

    /**
     * 清空日志
     *
     * @return void
     */
    public function clear(LogsModel $logs)
    {
        if (IS_AJAX) {
            $logs->clear();
            return $this->success();
        }
    }

    /**
     * 日志模板
     *
     * @return void
     */
    public function template(LogsTemplate $logs_template_model)
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->request->param(), [
                ['keyword', 0]
            ]);
            [$page, $limit] = $this->getPaginator();
            $data = $logs_template_model->getList($page, $limit, $filter);
            return $this->success($data);
        } else {
            return $this->fetch('Logs/template');
        }
    }

    /**
     * 添加日志模板
     *
     * @param LogsTemplate $logs_template_model
     * @return void
     */
    public function template_add(LogsTemplate $logs_template_model)
    {
        if($this->request->isPost()) {
			$data = input('post.');
			$result = $this->validate($data,[
                'name|模板标识' => 'require|length:1,128',
                'title|模板标题' => 'require'
            ]);
            if($result !== true) {
                return $this->fail($result);
            }
            try {
				$logs_template_model->insert(array_keys_filter($data, [
                    'name',
                    'title',
                    ['content', '']
                ]));
                $this->logger('logs.logTemplate.add', 'CREATEED', $data);
			} catch (Exception $e) {
                return $this->fail($e->getMessage());
			}
            return $this->success();
        } else {
            $this->assign('info', [
                'name' => input('name', ''),
                'params' => input('params', '')
            ]);
            return $this->fetch('Logs/template_form');
        }
    }

    /**
     * 编辑日志模板
     *
     * @param LogsTemplate $logs_template_model
     * @return void
     */
    public function template_edit(LogsTemplate $logs_template_model)
    {
        $id = (int)input('get.id');
		$info = $logs_template_model->find($id);
		if (empty($info)) {
			$this->error('数据不存在');
		}
        if($this->request->isPost()) {
			$data = input('post.');
            $info->content = $data["content"];
            $info->title = $data["title"];
            $info->name = $data["name"];
            $info->save();
            $this->logger('logs.logTemplate.edit', 'UPDATED', $info);
            return $this->success();
        } else {
			$this->assign('info',$info);
            return $this->fetch('Logs/template_form');
        }
    }

    /**
     * 删除日志模板
     *
     * @param LogsTemplate $logs_template_model
     * @return void
     */
    public function template_del(LogsTemplate $logs_template_model)
    {
        if (IS_AJAX) {
            $ids = $this->request->post('id');
            if (!is_array($ids)) {
                $ids = [ intval($ids) ];
            }

            $infos = $logs_template_model->where([["id", "IN", $ids]])->select();
            if (!empty($infos)) {
                foreach($infos as $info) {
                    $this->logger('logs.logTemplate.delete', 'DELETE', $info);
                    $info->delete();
                }
            }
        }
        return $this->success();
    }

    /**
     * 更新日志模板
     *
     * @param LogsTemplate $logs_template_model
     * @return void
     */
    public function template_update(LogsTemplate $logs_template_model)
    {
        if (IS_AJAX) {
            $id = (int)input('get.id');
            $info = $logs_template_model->find($id);
            if (empty($info)) {
                return $this->fail('数据不存在');
            }
            $data = input("post.");
            if(isset($data["field"])) {
                $info->setAttr($data["field"], $data["value"]);
            }
            $info->save();
            $this->logger('logs.logTemplate.update', 'UPDATED', $info);
        }
        return $this->success();
    }
}