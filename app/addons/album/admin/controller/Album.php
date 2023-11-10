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

class Album extends Controller
{
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
            $filter = array_keys_filter($this->params, [ ['album_id', 0],['kw', ''] ]);
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
     * 相册管理
     *
     * @param UploadGroupModel $group
     * @param UploadModel $upload
     * @return void
     */
    public function lists(UploadGroupModel $group, UploadModel $upload)
    {
        return $this->fetch();
    }
}