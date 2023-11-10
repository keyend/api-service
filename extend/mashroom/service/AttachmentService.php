<?php
namespace mashroom\service;
/**
 * mashroom.Attachment
 * @author k. <email@email.com>
 * @version 1.0.0
 */
use app\api\model\Logs as LogsModel;
use app\model\AttachmentModel;
use mashroom\component\Upload;
use mashroom\exception\HttpException;

class AttachmentService extends App
{
    // 初始化
    private function initialize()
    {
        $this->nearTmpClean();
    }

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * 清理上传的临时文件
     * 
     * @access private
     * @return void
     */
    private function nearTmpClean()
    {
        // 一个小时之前上传的文件无归属
        $lasTime = TIMESTAMP - 3600;
        
        $query = AttachmentModel::where('user_id', '0')->where('create_time', '<', $lasTime);
        $tmpFiles = $query->select();

        if ($tmpFiles) {
            foreach($tmpFiles as $tmp) {
                // 删除文件
                @unlink($tmp->filepath);
                // 记录日志
                app()->make(LogsModel::class)->info('log.template.attachment.delete', 'DELETE', $tmp->toArray());
                // 删除记录
                $tmp->delete();
            }
        }
    }

    /**
     * 获取最后上传的文件
     *
     * @return void
     */
    public function get_last()
    {
        return AttachmentModel::where('user_id', S1)->order('id DESC')->find();
    }

    /**
     * 上传文件
     * 
     * @param mixed $files
     * @param string $type 附件类型
     * @param string $category 类型
     * @param array $params
     * @return array
     */
    public function uploadFile($files, $category = '', $user_id = 0, string $type = 'image')
    {
        try {
            if (!is_array($files)) {
                $files = [$files];
            }

            if (!class_exists('\think\file\UploadedFile')) {
                throw new \Exception('think\UploadedFile is not implemented');
            }

            foreach($files as $file) {
                // 是否为文件流
                if ($file instanceof \think\file\UploadedFile) {
                    $type = $file->getMime();
                    $filename = $file->getOriginalName();
                    $path = str_replace("\\", "/", Upload::put($file));
                }

                $data = [
                    'type' => $type,
                    'user_id' => $user_id,
                    'title' => $filename,
                    'filepath' => $path,
                    'category' => $category,
                    'create_time' => TIMESTAMP
                ];

                // 创建上传历史
                AttachmentModel::create($data);
                // 记录日志
                app()->make(LogsModel::class)->info('attachment.upload.image', 'UPLOAD', array_merge($data, [
                    'user_id' => S1
                ]));
                // 返回路径加工
                $data['filepath'] = request()->domain(true) . $data['filepath'];

                return $data;
            }
        } catch(\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 上传图片
     * 
     * @param mixed $files
     * @param string $type 附件类型
     * @param string $category 类型
     * @param array $params
     * @return array
     */
    public function uploadImage($files, $category = '', $user_id = 0)
    {
        return $this->uploadFile($files, $category, $user_id);
    }
}