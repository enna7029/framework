<?php
declare(strict_types=1);

namespace Enna\Framework\File;

use Enna\Framework\Exception\FileException;
use Enna\Framework\File;

/**
 * 上传文件类
 * Class UploadFile
 * @package Enna\Framework\File
 */
class UploadFile extends File
{

    /**
     * 原始文件名
     * @var string
     */
    private $originalName;

    /**
     * MIME类型
     * @var string
     */
    private $mimeType;

    /**
     * 错误编码 0:成功
     * @var int
     */
    private $error;

    /**
     * 是否测试
     * @var bool
     */
    private $test = false;

    public function __construct(string $path, string $originalName, string $mimeType, int $error = null, bool $test = false)
    {
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->error = $error ?: UPLOAD_ERR_OK;
        $this->test = $test;

        parent::__construct($path, UPLOAD_ERR_OK === $this->error);
    }

    /**
     * Note: 上传文件
     * Date: 2023-01-06
     * Time: 15:34
     * @param string $directory 文件路径
     * @param string|null $name
     */
    public function move(string $directory, string $name = null)
    {
        if ($this->isValid()) {
            if ($this->test) {
                return parent::move($directory, $name);
            }

            $target = $this->getTargetFile($directory, $name);

            set_error_handler(function ($errno, $msg) use (&$error) {
                $error = $msg;
            });
            $moved = move_uploaded_file($this->getPathname(), (string)$target);
            restore_error_handler();
            if (!$moved) {
                throw new FileException(sprintf('不能移动文件从"%s" 到 "%s" (%s)', $this->getPathname(), $target, strip_tags($error)));
            }

            @chmod((string)$target, 0666 & ~umask());

            return $target;
        }

        throw new FileException($this->getErrorMessage());
    }


    /**
     * Note: 验证上传的文件是否合法
     * Date: 2023-01-06
     * Time: 16:55
     * @return bool
     */
    public function isValid()
    {
        $isOk = UPLOAD_ERR_OK === $this->error;

        return $this->test ? $isOk : $isOk && is_uploaded_file($this->getPathname());
    }

    /**
     * Note: 获取错误信息
     * Date: 2023-01-06
     * Time: 17:49
     * @return string
     */
    protected function getErrorMessage()
    {
        switch ($this->error) {
            case 1:
                $message = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值';
                break;
            case 2:
                $message = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
                break;
            case 3:
                $message = '文件只有部分被上传';
                break;
            case 4:
                $message = '没有文件被上传';
                break;
            case 6:
                $message = '找不到临时文件夹';
                break;
            case 7:
                $message = '文件写入失败';
                break;
        }

        return $message;
    }

    /**
     * Note: 获取上传文件类型信息
     * Date: 2023-01-06
     * Time: 17:51
     * @return string
     */
    public function getOriginMime()
    {
        return $this->mimeType;
    }

    /**
     * Note: 获取文件名
     * Date: 2023-01-06
     * Time: 17:51
     * @return string
     */
    public function getOriginName()
    {
        return $this->originalName;
    }

    /**
     * Note: 获取上传文件扩展名
     * Date: 2023-01-06
     * Time: 17:55
     * @return string
     */
    public function getOriginExtension()
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    /**
     * Note: 获取文件扩展名
     * Date: 2023-01-13
     * Time: 19:08
     * @return string
     */
    public function extension(): string
    {
        return $this->getOriginExtension();
    }
}