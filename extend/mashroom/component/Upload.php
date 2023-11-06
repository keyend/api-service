<?php
namespace mashroom\component;
/**
 * 上传组件
 * @version 1.0
 */
class Upload
{
    public function __construct() {}

    /**
     * 图片上传
     * @param String cat   目录信息
     * @param Stream file  图片流
     * @return String
     */
    public static function put($file)
    {
        $uploadPath = config('admin.upload_path', './uploads') . DIRECTORY_SEPARATOR . date('ym');
        if (substr($uploadPath, 0, 1) !== '.') $uploadPath = "." . $uploadPath;
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
        $extension = $file->extension();
        $fileName = md5(uniqid()) . "." . $extension;
        $target = $file->move($uploadPath, $fileName);
        $filepath = substr($uploadPath . DIRECTORY_SEPARATOR . $fileName, 1);

        return $filepath;
    }

    public function __call($method, $parameters)
    {
        return \think\facade\Filesystem::disk(config('upload.type'))->$method(...$parameters);
    }
}
