<?php
declare(strict_types=1);

namespace Enna\Framework;

use \Closure;
use SplFileInfo;
use Enna\Framework\Exception\FileException;

/**
 * 上传文件基础类
 * Class File
 * @package Enna\Framework
 */
class File extends SplFileInfo
{
    /**
     * 文件hash规则
     * @var array
     */
    protected $hash = [];

    /**
     * hash文件名
     * @var string
     */
    protected $hashName;

    public function __construct(string $path, bool $checkPath = true)
    {
        if ($checkPath && !is_file($path)) {
            throw new FileException($path . '文件不存在');
        }

        parent::__construct($path);
    }

    /**
     * Note: 移动文件
     * Date: 2023-01-09
     * Time: 18:21
     * @param string $directory 保存路径
     * @param string|null $name 保存的文件名
     * @return File
     */
    public function move(string $directory, string $name = null)
    {
        $target = $this->getTargetFile($directory, $name);

        set_error_handler(function ($errno, $msg) use (&$error) {
            $error = $msg;
        });

        $renamed = rename($this->getPathname(), (string)$target);
        restore_error_handler();
        if (!$renamed) {
            throw new FileException('不能移动文件从' . $this->getPathname() . '到' . $target . '(' . $error . ')');
        }

        @chmod($target, 0666);

        return $target;
    }

    /**
     * Note: 实例化一个新文件
     * Date: 2023-01-06
     * Time: 17:05
     * @param string $directory 路径
     * @param string|null $name 文件名
     * @return File
     */
    public function getTargetFile(string $directory, string $name = null)
    {
        if (!is_dir($directory)) {
            $chmod = @mkdir($directory, 0777, true);
            if ($chmod === false && !is_dir($directory)) {
                throw new FileException('不能创建文件夹' . $directory);
            }
        } elseif (!is_writable($directory)) {
            throw new FileException('不能写入' . $directory . '文件');
        }

        $target = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . ($name === null ? $this->getBasename() : $this->getName($name));

        return new self($target, false);
    }

    /**
     * Note: 获取文件名
     * Date: 2023-01-06
     * Time: 17:18
     * @param string $name 文件名
     * @return string
     */
    public function getName(string $name)
    {
        $filename = str_replace('\\', '/', $name);
        $pos = strrpos($filename, '/');

        $originName = $pos === false ? $filename : substr($filename, $pos + 1);

        return $originName;
    }

    /**
     * Note: 获取文件的哈希散列值
     * Date: 2023-01-06
     * Time: 18:10
     * @param string $type 算法
     * @return string
     */
    public function hash(string $type = 'sha1')
    {
        if (!isset($this->hash[$type])) {
            $this->hash[$type] = hash_file($type, $this->getPathname());
        }

        return $this->hash[$type];
    }

    /**
     * Note: 获取文件的SHA1值
     * Date: 2023-01-09
     * Time: 18:11
     * @return string
     */
    public function sha1()
    {
        return $this->hash('sha1');
    }

    /**
     * Note: 获取文件的MD5值
     * Date: 2023-01-09
     * Time: 18:12
     * @return string
     */
    public function md5()
    {
        return $this->hash('md5');
    }

    /**
     * Note: 获取文件类型信息
     * Date: 2023-01-09
     * Time: 18:20
     * @return mixed
     */
    public function getMime()
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return finfo_file($finfo, $this->getPathname());
    }

    /**
     * Note: 文件扩展名
     * Date: 2023-01-09
     * Time: 18:23
     * @return string
     */
    public function extension()
    {
        return $this->getExtension();
    }

    /**
     * Note: 生成hash文件名
     * Date: 2023-01-09
     * Time: 18:34
     * @param string|Closure $rule
     * @return string
     */
    public function hashName($rule = '')
    {
        if (!$this->hashName) {
            if ($rule instanceof Closure) {
                $this->hashName = call_user_func_array($rule, [$this]);
            } else {
                switch (true) {
                    case in_array($rule, hash_algos());
                        $hash = $this->hash($rule);
                        $this->hashName = substr($hash, 0, 2) . DIRECTORY_SEPARATOR . substr($hash, 2);
                        break;
                    case is_callable($rule);
                        $this->hashName = call_user_func($rule);
                        break;
                    default:
                        $this->hashName = date('Ymd') . DIRECTORY_SEPARATOR . md5((string)microtime(true));
                        break;
                }
            }
        }

        return $this->hashName . '.' . $this->extension();
    }

}