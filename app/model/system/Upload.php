<?php
namespace app\model\system;
/**
 * 附件
 *
 * @package app.common.model
 * @author: k.
 * @date: 2021-05-10 20:19:31
 */
use app\Model;
use mashroom\component\Upload as UploadComponent;
use mashroom\exception\HttpException;

class Upload extends Model
{
    protected $name = 'sys_upload';
    protected $pk = 'id';
    protected $mime = [
        'image/jpeg'    => ['jpg','jpeg'],
        'image/pjpeg'   => ['jpg','jpeg'],
        'image/gif'     => ['gif'],
        'image/png'     => ['png'],
        'image/tiff'    => ['tif', 'tiff'],
        'image/bmp'     => ['bmp'],
        'image/vnd.microsoft.icon' => ['ico'],
        'image/webp'    => ['webp'],
        'image/svg+xml' => ['svg'],
        'text/plain'    => ['txt'],
        'text/csv'      => ['csv'],
        'text/x-log'    => ['log'],
        'text/x-markdown' => ['md'],
        'application/zip' => ['zip'],
        'application/x-rar-compressed' => ['rar'],
        'application/json' => ['json'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'application/x-7z-compressed' => ['7z'],
        'video/mpeg' => ['mpeg'],
        'video/x-msvideo' => ['avi'],
        'audio/wav' => ['wav'],
        'audio/mpeg' => ['mp3'],
        'audio/midi' => ['mid', 'midi'],
        'audio/x-midi' => ['mid', 'midi']
    ];

    /**
     * 权限明细
     * @collection relation.model
     */
    public function group() {
        return $this->hasOne(UploadGroup::class, 'group_id', 'id');
    }

    /**
     * 清理上传的临时文件
     * 
     * @access private
     * @return void
     */
    private function initialize(): void
    {
        // 一个小时之前上传的文件无归属
        $lasTime = TIMESTAMP - 3600;
        
        $query = self::where('user_id', '0')->where('create_time', '<', $lasTime);
        $tmps = $query->select();
        if ($tmps) {
            foreach($tmps as $tmp) {
                // 删除文件
                @unlink($tmp->filepath);
                // 记录日志
                $this->logger('log.template.attachment.delete', 'DELETE', $tmp->toArray());
                // 删除记录
                $tmp->delete();
            }
        }
    }

    /**
     * 返回文件类型
     *
     * @param string $type
     * @return void
     */
    private function getExtensions($type)
    {
        return !isset($this->mime[$type]) ? false : $this->mime[$type];
    }

    /**
     * 文件类型校验
     *
     * @param string $type
     * @param string $extensionName
     * @return void
     */
    private function validateExtension($type, $extensionName)
    {
        $types = $this->getExtensions($type);
        if (empty($types)) {
            return false;
        }

        return in_array($extensionName, $types);
    }

    /**
     * 返回列表
     * @return array
     */
    public function getList(int $page = 1, int $limit = 9999, array $filter = [])
    {
        $condition = [];
        if (!isSuperUser()) {
            $condition[] = ['user_id', '=', S1];
        }
        if (!empty($filter['album_id'])) {
            $condition[] = ['group_id', '=', $filter['album_id']];
        }
        if (!empty($filter['kw'])) {
            $condition[] = ['title', 'LIKE', "%{$filter['kw']}%"];
        }

        $query = self::where($condition)->order('id DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select()->withAttr('create_time', function($value) {
            return date('Y-m-d H:i', $value);
        })->withAttr('filepath', function($value) use($domain) {
            return $value;
        })->toArray();

        return compact('list', 'count');
    }

    /**
     * 上传文件
     * 
     * @param mixed $filed
     * @param string $group_id 分组
     * @param string $type 附件类型 image,text,video,audio,application
     * @return array
     */
    public function uploadFile($files, $group_id, $type = 'image', $user_id = S1)
    {
        // 初始化
        $this->initialize();
        $result = [];
        try {
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach($files as $file) {
                if (!($file instanceof \think\file\UploadedFile)) {
                    throw new \Exception("提交参数错误");
                }

                $mimeType = $file->getMime();
                $types = explode(",", $type);
                $validate = false;
                foreach($types as $type) {
                    if (in_array($type, ['image', 'video', 'audio', 'text'])) {
                        if (strpos($mimeType, $type) !== FALSE) {
                            $validate = true;
                            break;
                        }
                    }
                }

                if (!$validate) {
                    throw new \Exception("上传文件类型错误");
                }

                $orignalName = $file->getOriginalName();
                $extensionName = $file->getOriginalExtension();
                if (!$this->validateExtension($mimeType, $extensionName)) {
                    throw new \Exception("不允许上传此类型文件");
                }

                $filepath = str_replace("\\", "/", UploadComponent::put($file));
                $data = [
                    'type' => $type,
                    'user_id' => $user_id,
                    'group_id' => $group_id,
                    'title' => $orignalName,
                    'filepath' => $filepath,
                    'create_time' => TIMESTAMP
                ];
                $data['id'] = self::insert($data);
                $this->logger('attachment.upload.image', 'UPLOAD', $data);
                $result[] = $data;
            }
        } catch(\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode());
        }
        return $result;
    }

    /**
     * 为附件绑定用户
     *
     * @param int $user_id
     * @param string $filepath
     * @return void
     */
    public function bindUser($user_id, string $filepath)
    {
        $orignal = request()->domain(true);
        $filepath = substr($filepath, strlen($orignal));

        $attachment = self::where('filepath', $filepath)->find();
        if ($attachment) {
            $attachment->user_id = $user_id;
            $attachment->save();

            return true;
        }

        return false;
    }

    /**
     * 删除旧文件
     *
     * @param string $filepath
     * @return boolean
     */
    public function unlinkFile(string $filepath)
    {
        $orignal = request()->domain(true);
        $filepath = substr($filepath, strlen($orignal));
        $attachment = self::where('filepath', $filepath)->find();
        if ($attachment) {
            @unlink(".{$filepath}");
            $attachment->delete();

            return true;
        }

        return false;
    }
}
