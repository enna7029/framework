<?php
declare(strict_types=1);

namespace Enna\Framework\Exception;

use Enna\Framework\Exception;

class ErrorException extends Exception
{
    /**
     * 异常的级别
     * @var int
     */
    protected $severity;

    /**
     * ErrorException constructor.
     * @param int $serverity 级别
     * @param string $message 信息
     * @param string $file 文件
     * @param int $line 行数
     */
    public function __construct(int $serverity, string $message, string $file, int $line)
    {
        $this->severity = $serverity;
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        $this->code = 0;
    }

    /*
     * 获取错误级别
     */
    final public function getSeverity()
    {
        return $this->severity;
    }
}