<?php
namespace app\addons\album\admin\controller;
/**
 * 相册控制器
 * 
 * @version 1.0.0
 */
use app\controller\admin\Controller;
use app\model\system\Upload as UploadModel;
use app\model\system\UploadGroup as UploadGroupModel;
use app\model\system\Config;

class Album extends Controller
{
    /**
     * 附件类型
     *
     * @var array
     */
    private $types = [ 'image', 'video' ];
    private $typesText = [ '图片', '视频' ];

    /**
     * 相册
     *
     * @param UploadGroupModel $group
     * @param UploadModel $upload
     * @return void
     */
    public function index(UploadGroupModel $group, UploadModel $upload)
    {
        if ($this->request->isAjax()) {
            $filter = array_keys_filter($this->params, [ ['album_id', 0], ['kw', ''] ]);
            [$page, $limit] = $this->getPaginator();
            $data = $upload->getList($page, $limit, $filter);
            return $this->success($data);
        } else {
            $uploadGroup = $group->getAlbumList();
            if ($uploadGroup['count'] == 0) {
                $uploadGroup = $group->getDefaultGroup();
            }
            $this->assign('uploadGroup', $uploadGroup['list']);
            return $this->fetch();
        }
    }

    /**
     * 图标
     *
     * @return void
     */
    public function icon()
    {
        return $this->fetch();
    }

    /**
     * 图片上传
     *
     * @param UploadGroupModel $group
     * @param UploadModel $upload
     * @return void
     */
    public function upload(UploadGroupModel $group, UploadModel $upload)
    {
        if (IS_POST) {
            $album_id = (int)$this->params['album_id'];
            $files = $this->request->file('file');
            $result = $upload->uploadFile($files, $album_id);
            $group->refresh([$album_id]);
            return $this->success($result);
        }
    }

    /**
     * 相册管理
     *
     * @param UploadGroupModel $group
     * @param UploadModel $upload
     * @return void
     */
    public function lists(UploadGroupModel $group, UploadModel $upload, Config $config)
    {
        $type = (int)input('type', 0);
        $typeText = $this->typesText[$type];
        $uploadGroup = $group->getAlbumList($type);
        if ($uploadGroup['count'] == 0) {
            $uploadGroup = $group->getDefaultGroup($type);
        }
        $this->assign('uploadGroup', $uploadGroup['list']);
        $this->assign('typeText', $typeText);
        $this->assign('type', $type);
        $this->assign('config', $config->getConfig('CONFIG_UPLOAD'));
        return $this->fetch();
    }

    /**
     * 相册管理
     *
     * @param UploadGroupModel $group
     * @param UploadModel $upload
     * @return void
     */
    public function video(UploadGroupModel $group, UploadModel $upload, Config $config)
    {
        $type = (int)input('type', 1);
        $uploadGroup = $group->getAlbumList($type);
        $typeText = $this->typesText[$type];
        if ($uploadGroup['count'] == 0) {
            $uploadGroup = $group->getDefaultGroup($type);
        }
        $this->assign('uploadGroup', $uploadGroup['list']);
        $this->assign('typeText', $typeText);
        $this->assign('type', $type);
        $this->assign('config', $config->getConfig('CONFIG_UPLOAD'));
        return $this->fetch('Album/lists');
    }

    /**
     * 更新附件
     *
     * @param UploadModel $upload
     * @param UploadGroupModel $group
     * @return void
     */
    public function update(UploadModel $upload, UploadGroupModel $group)
    {
        if (IS_AJAX) {
            $id = (int)$this->request->param('id');
            $group_id = (int)$this->request->param('group_id');
            $new_group_id = (int)$this->request->param('new_group_id');
            $edit = $upload->find($id);
            if (empty($edit) || $edit['group_id'] != $group_id) {
                return $this->error("更新失败：参数错误");
            } elseif(!super() && $edit['user_id'] != S1) {
                return $this->error("更新失败：参数错误");
            }
            $edit->group_id = $new_group_id;
            $edit->save();
            $group->refresh([$group_id, $new_group_id]);
            return $this->success();
        }
    }

    /**
     * 删除附件
     *
     * @param UploadModel $upload
     * @return void
     */
    public function delete(UploadModel $upload, UploadGroupModel $group)
    {
        if (IS_AJAX) {
            $ids = $this->params['ids'] ?? [$this->params['id']];
            foreach($ids as $id) {
                $edit = $upload->find($id);
                if (empty($edit)) {
                    return $this->error('记录不存在!');
                }
                $this->logger('logs.sys.attach.delete', 'DELETE', $edit);
                $group_id = $edit->group_id;
                $filepath = $this->app->getRootPath() . "public" . $edit->filepath;
                @unlink($filepath);
                $edit->delete();
                $group->refresh([$group_id]);
            }
            return $this->success();
        }
    }

    /**
     * 新增分组
     *
     * @param UploadGroupModel $group
     * @return void
     */
    public function add_group(UploadGroupModel $group)
    {
        if (IS_AJAX) {
            $type = (int)$this->params['type'] ?? 0;
            $group_name = $this->params['group_name'] ?? 0;
            if (empty($group_name)) {
                return $this->error("参数错误");
            }

            try {
                $result = $group->addGroup([ ['group_name', '=', $group_name], ['user_id', '=', S1], ['type', '=', $type] ], [
                    'type' => $type,
                    'group_name' => $group_name
                ]);
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }

            return $this->success($result);
        }
    }

    /**
     * 更新分组
     *
     * @param UploadGroupModel $group
     * @return void
     */
    public function update_group(UploadGroupModel $group)
    {
        if (IS_AJAX) {
            $group_id = (int)$this->params['id']??0;
            $group_name = $this->params['group_name']??'';
            $edit = $group->find($group_id);
            if (empty($edit)) {
                return $this->error("更新失败：分组不存在");
            } elseif(!super() && $edit['user_id'] != S1) {
                return $this->error("更新失败：参数错误");
            } elseif(empty($group_name)) {
                return $this->error("更新失败：分组名不能为空!");
            }
            $edit->group_name = $group_name;
            $edit->save();
            return $this->success();
        }
    }

    /**
     * 删除分组
     *
     * @param UploadGroupModel $group
     * @return void
     */
    public function del_group(UploadGroupModel $group)
    {
        if (IS_AJAX) {
            $group_id = (int)$this->params['id']??0;
            $edit = $group->find($group_id);
            if (empty($edit)) {
                return $this->error("删除失败：分组不存在");
            } elseif(!super() && $edit['user_id'] != S1) {
                return $this->error("删除失败：参数错误");
            } elseif($edit['filecount'] > 0) {
                return $this->error("删除失败：分组内不是空的");
            }

            $this->logger('logs.sys.attach-group.delete', 'DELETE', $edit);
            $edit->delete();
            return $this->success();
        }
    }
}