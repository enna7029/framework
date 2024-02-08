<?php
declare(strict_types=1);

namespace Enna\Framework\Console;

use Enna\Framework\Console\Output\Descriptor;
use Enna\Framework\Console\Output\Driver\Console;
use Throwable;
use Exception;

/**
 * Class Output
 * @package Enna\Framework\Console\Output\Console::setDecorated
 * @see
 * @method void setDecorated($decorated)
 */
class Output
{
    /**
     * 不显示信息(静默)
     */
    const VERBOSITY_QUIET = 0;

    /**
     * 正常信息
     */
    const VERBOSITY_NORMAL = 1;

    /**
     * 详细信息
     */
    const VERBOSITY_VERBOSE = 2;

    /**
     * 非常详细的信息
     */
    const VERBOSITY_VERY_VERBOSE = 3;

    /**
     * 调试信息
     */
    const VERBOSITY_DEBUG = 4;

    /**
     * 输出格式
     */
    const OUTPUT_NORMAL = 0;
    const OUTPUT_RAW = 1;
    const OUTPUT_PLAIN = 2;

    /**
     * 驱动
     * @var Console
     */
    private $handle = null;

    /**
     * 输出信息级别
     * @var int
     */
    private $verbosity = self::VERBOSITY_NORMAL;

    protected $styles = [
        'info',
        'error',
        'comment',
        'question',
        'highlight',
        'warning',
    ];

    public function __construct($driver = 'console')
    {
        $class = '\\Enna\\Framework\\Console\\Output\\Driver\\' . ucwords($driver);

        $this->handle = new $class($this);
    }

    /**
     * Note: 输出信息并换行
     * Date: 2023-12-21
     * Time: 10:52
     * @param string $style
     * @param string $message
     */
    protected function block(string $style, string $message)
    {
        $this->writeln("<{$style}>{$message}</{$style}>");
    }

    /**
     * Note: 输出信息并换行
     * Date: 2023-12-21
     * Time: 11:13
     * @param string $message
     * @param int $type
     */
    public function writeln(string $message, int $type = 0)
    {
        $this->write($message, true, $type);
    }

    /**
     * Note: 输出空行
     * Date: 2024-01-19
     * Time: 17:08
     * @param int $count
     */
    public function newLine(int $count = 1)
    {
        $this->write(str_repeat(PHP_EOL, $count));
    }

    /**
     * Note: 输出信息
     * Date: 2024-01-19
     * Time: 17:09
     * @param string $messages
     * @param bool $newline
     * @param int $type
     */
    public function write(string $messages, bool $newline = false, int $type = 0)
    {
        $this->handle->write($messages, $newline, $type);
    }

    /**
     * Note: 渲染异常
     * Date: 2023-12-21
     * Time: 9:48
     * @param Throwable $e
     */
    public function renderException(Throwable $e)
    {
        $this->handle->renderException($e);
    }

    /**
     * Note: 设置输出信息的级别
     * Date: 2023-12-22
     * Time: 15:15
     * @param int $level
     */
    public function setVerbosity(int $level)
    {
        $this->verbosity = $level;
    }

    /**
     * Note: 获取输出信息级别
     * Date: 2023-12-22
     * Time: 15:16
     * @return int
     */
    public function getVerbosity()
    {
        return $this->verbosity;
    }

    /**
     * Note: 是否静默
     * Date: 2024-01-19
     * Time: 17:15
     * @return bool
     */
    public function isQuiet()
    {
        return self::VERBOSITY_QUIET === $this->verbosity;
    }

    /**
     * Note: 详细信息
     * Date: 2024-01-19
     * Time: 17:24
     * @return bool
     */
    public function isVerbose()
    {
        return self::VERBOSITY_VERBOSE === $this->verbosity;
    }

    /**
     * Note: 非常详细的信息
     * Date: 2024-01-19
     * Time: 17:24
     * @return bool
     */
    public function isVeryVerbose()
    {
        return self::VERBOSITY_VERY_VERBOSE === $this->verbosity;
    }

    /**
     * Note: 调试信息
     * Date: 2024-01-19
     * Time: 17:25
     * @return bool
     */
    public function isDebug()
    {
        return self::VERBOSITY_DEBUG === $this->verbosity;
    }

    /**
     * Note: 描述
     * Date: 2024-01-19
     * Time: 17:23
     * @param $object
     * @param array $options
     */
    public function describe($object, array $options = [])
    {
        $descriptor = new Descriptor();
        $options = array_merge([
            'raw_text' => false,
        ], $options);
      
        $descriptor->describe($this, $object, $options);
    }

    public function __call($method, $args)
    {
        if (in_array($method, $this->styles)) {
            array_unshift($args, $method);

            return call_user_func_array([$this, 'block'], $args);
        }

        if ($this->handle && method_exists($this->handle, $method)) {
            return call_user_func_array([$this->handle, $method], $args);
        } else {
            throw new Exception('method not exists:' . __CLASS__ . '->' . $method);
        }
    }
}