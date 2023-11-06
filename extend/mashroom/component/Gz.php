<?php
namespace mashroom\component;
/**
 * Gz文件操作
 * 
 * Gz::destination('/backup/20200431.bak)
 * Gz::path('./..') 类似TP6 ./public/index.php 根目录为./..
 * Gz::denyFolder(['view', 'runtime']) 排除目录
 * Gz::denyExtension(['log', 'bak']) 排除文件
 * Gz::backup() // 执行备份
 * Gz::filepath() // 返回备份的文件
 * 
 * @date    2021-05-10 20:19:31
 * @version 1.0
 * @author  easyzf.cn
 */
class Gz
{
    // 参数
    protected $options = [];
    // 目录列表
    protected $folders = [];
    // 已排队的目录
    protected $denyFolders = [];
    // 已排队的文件
    protected $denyExtensions = [];
    // 指针
    protected $buffer = null;

    // 写入模式
    const WRITE_MODE = 'wb';
    // 压缩级别
    const COMPRESS_LEVEL = 9;

    /**
     * 扫描目录
     *
     * @param string $path
     * @return void
     */
    protected function scan(string $path)
    {
        return glob($path . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
    }

    /**
     * 扫描目录
     *
     * @param string $path
     * @return void
     */
    protected function getFolders(string $path)
    {
        $folders = glob($path . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

        foreach($folders as $folder) {
            if (!in_array($folder, $this->denyFolders)) {
                $this->folders[] = $folder;
                $this->getFolders($folder);
            }
        }
    }

    /**
     * 解析路径
     *
     * @param string $value
     * @return string
     */
    protected function parsePath(string $value)
    {
        $value = str_replace("\\", DIRECTORY_SEPARATOR, $value);
        $value = str_replace("/", DIRECTORY_SEPARATOR, $value);

        return $value;
    }

    /**
     * 返回ZipArchive指针
     *
     * @return object
     */
    protected function buffer()
    {
        if ($this->buffer === null) {
            try {
                $this->buffer = new \ZipArchive();
            } catch(\Exception $e) {
                throw new \Exception("Uncaught Error: Class 'ZipArchive' not found");
            }

            $this->buffer->open($this->option['destination'], \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        }

        return $this->buffer;
    }

    /**
     * 备份目录
     *
     * @param string $folder
     * @return void
     */
    protected function compressFolder(string $folder, int $flag = 0)
    {
        $matchs = ['*.*', '.*', '*'];
        $files = glob($folder . DIRECTORY_SEPARATOR . $matchs[$flag], GLOB_NOSORT);
        $isFilted = false;
        // 创建目录
        $this->compressFile($folder, 'dir');

        foreach($files as $i => $file) {
            $filetype = filetype($file);

            if ($filetype !== 'dir') {
                $ext = pathinfo($file, PATHINFO_EXTENSION);

                if (in_array($ext, $this->denyExtensions)) {
                    $isFilted = true;
                    unset($files[$i]);
                }
            } else {
                // 将本目录加入队列
                $this->folders[] = $file;

                $isFilted = true;
                unset($files[$i]);
            }
        }

        if (!empty($files)) {
            if (!$isFilted) {
                $this->buffer()->addGlob($folder . DIRECTORY_SEPARATOR . '*.*', GLOB_NOSORT, [
                    'remove_path' => $this->options['path']
                ]);
            } else {
                foreach($files as $i => $file) {
                    $this->compressFile($file);
                }
            }
        }
    }

    /**
     * 备份文件
     *
     * @param string $file
     * @param string $type
     * @return void
     */
    protected function compressFile(string $file, string $type = 'file')
    {
        try {
            $filepath = str_replace($this->options['path'] . DIRECTORY_SEPARATOR, '', $file);

            if ($type !== 'dir') {
                $this->buffer()->addFile($file, $filepath);
            } elseif($filepath !== $this->options['path']) {
                $this->buffer()->addEmptyDir($filepath);
            }
        } catch(\Exception $e) {
            \think\facade\Log::write($e->getMessage());
        }
    }

    /**
     * 构造函数
     *
     * @param array $options 参数配置
     */
    public function __construct($options = [])
    {
        if (!isset($options['path'])) {
            $options['path'] = '.';
        }

        if (!isset($options['destination'])) {
            $this->destination($options['path'] . DIRECTORY_SEPARATOR . uniqid() . ".gz");
        }

        $this->options = $options;
    }

    /**
     * 设置目录
     *
     * @param string $str
     * @return object
     */
    public function path(string $str)
    {
        $this->options['path'] = $str;

        return $this;
    }
  
    /**
     * 设置排除的目录
     *
     * @param array | string $denys
     * @return object
     */
    public function denyFolder($values)
    {
        if (is_array($values)) {
            foreach($values as $value)
                $this->denyFolders[] = $this->options['path'] . DIRECTORY_SEPARATOR . $this->parsePath($value);
        } else {
            $this->denyFolders[] = $this->options['path'] . DIRECTORY_SEPARATOR . $this->parsePath($values);
        }

        return $this;
    }

    /**
     * 设置排队的文件
     *
     * @param array | string $denys
     * @return object
     */
    public function denyExtension($values)
    {
        if (is_array($values)) {
            $this->denyExtensions = array_merge($this->denyExtensions, $values);
        } else {
            $this->denyExtensions[] = $values;
        }

        return $this;
    }

    /**
     * 返回扫描目录列表
     *
     * @return array
     */
    public function getFolderList()
    {
        return $this->folders;
    }

    /**
     * 文件名
     *
     * @return string
     */
    public function filename()
    {
        return basename($this->option['destination']);
    }

    /**
     * 文件名
     *
     * @return string
     */
    public function filepath()
    {
        return $this->option['destination'];
    }

    /**
     * 备份地址
     *
     * @param string $str
     * @return object
     */
    public function destination(string $str = '')
    {
        if ($str === '') {
            return $this->option['destination'];
        }

        $this->option['destination'] = $str;

        return $this;
    }

    /**
     * 创建备份
     *
     * @param string $path
     * @return object
     */
    public function backup(string $path = '')
    {
        if ($path !== '') {
            $this->destination($path);
        }
        // 加入主目录
        $this->folders[] = $this->options['path'];
        // 扫描子目录
        $this->getFolders($this->options['path']);

        foreach($this->folders as $i => $folder) {
            if ($i === 0) {
                // 加入隐藏文件
                $this->compressFolder($folder, 1);
                // 加入无扩展文件
                $this->compressFolder($folder, 2);
            }

            $this->compressFolder($folder);
        }

        $this->buffer()->close();

        return $this;
    }
}
