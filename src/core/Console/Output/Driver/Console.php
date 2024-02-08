<?php
declare(strict_types=1);

namespace Enna\Framework\Console\Output\Driver;

use Throwable;
use Enna\Framework\Console\Output;
use Enna\Framework\Console\Output\Formatter;

class Console
{
    /**
     * 输出对象
     * @var Output
     */
    private $output;

    /**
     * 格式化程序对象
     * @var Formatter
     */
    private $formatter;

    /**
     * 输出资源流
     * @var Resource
     */
    private $stdout;

    /**
     * 终端尺寸
     * @var array
     */
    private $terminalDimensions;

    public function __construct(Output $output)
    {
        $this->output = $output;
        $this->formatter = new Formatter();
        $this->stdout = $this->openOutputStream();
        //$decorated = $this->hasColorSupport($this->stdout);
        $decorated = true;
        $this->formatter->setDecorated($decorated);
    }

    /**
     * Note: 设置是否装饰
     * Date: 2024-01-25
     * Time: 9:31
     * @param $decorated
     */
    public function setDecorated($decorated)
    {
        $this->formatter->setDecorated($decorated);
    }

    /**
     * Note: 将消息写入到输出
     * Date: 2024-01-20
     * Time: 11:17
     * @param string|array $messages 消息
     * @param bool $newline 是否新行
     * @param int $type 输出格式
     * @param mixed $stream 输出流
     */
    public function write($messages, bool $newline = false, int $type = 0, $stream = null)
    {
        if (Output::VERBOSITY_QUIET === $this->output->getVerbosity()) {
            return;
        }
        $messages = (array)$messages;

        foreach ($messages as $message) {
            switch ($type) {
                case Output::OUTPUT_NORMAL;
                    $message = $this->formatter->format($message);
                    break;
                case Output::OUTPUT_RAW;
                    break;
                case Output::OUTPUT_PLAIN;
                    $message = strip_tags($this->formatter->format($message));
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Unknown output type given (%s)', $type));
            }

            $this->doWrite($message, $newline, $stream);
        }
    }

    /**
     * Note: 渲染异常
     * Date: 2024-01-20
     * Time: 11:19
     * @param Throwable $e
     */
    public function renderException(Throwable $e)
    {
        $stderr = $this->openErrorStream();
        $decorated = $this->hasColorSupport($stderr);
        $this->formatter->setDecorated($decorated);

        do {
            $title = sprintf(' [%s] ', get_class($e));

            $len = $this->stringWidth($title);

            $width = $this->getTerminalWidth() ? $this->getTerminalWidth() - 1 : PHP_INT_MAX;

            $lines = [];
            foreach (preg_split('/\r?\n/', $e->getMessage()) as $line) {
                foreach ($this->splitStringByWith($line, $width - 4) as $line) {
                    $lineLength = $this->stringWidth(preg_replace('/\[[^m]*m/', '', $line)) + 4;
                    $lines[] = [$line, $lineLength];

                    $len = max($lineLength, $len);
                }
            }

            $messages = ['', ''];
            $messages[] = $emptyLine = sprintf('<error>%s</error>', str_repeat(' ', $len));
            $messages[] = sprintf('<error>%s%s</error>', $title, str_repeat(' ', max(0, $len - $this->stringWidth($title))));
            foreach ($lines as $line) {
                $messages[] = sprintf('<error> %s %s</error>', $line[0], str_repeat(' ', $len - $line[1]));
            }
            $messages[] = $emptyLine;
            $messages[] = '';
            $messages[] = '';

            $this->write($messages, true, Output::OUTPUT_NORMAL, $stderr);

            if (Output::VERBOSITY_VERBOSE <= $this->output->getVerbosity()) {
                $this->write('<comment>Exception trace:</comment>', true, Output::OUTPUT_NORMAL, $stderr);

                $trace = $e->getTrace();

                array_unshift($trace, [
                    'function' => '',
                    'file' => $e->getFile() !== null ? $e->getFile() : 'n/a',
                    'line' => $e->getLine() !== null ? $e->getLine() : 'n/a',
                    'args' => [],
                ]);

                for ($i = 0, $count = count($trace); $i < $count; $i++) {
                    $class = isset($trace[$i]['class']) ? $trace[$i]['class'] : '';
                    $type = isset($trace[$i]['type']) ? $trace[$i]['type'] : '';
                    $function = $trace[$i]['function'];
                    $file = isset($trace[$i]['file']) ? $trace[$i]['file'] : 'n/a';
                    $line = isset($trace[$i]['line']) ? $trace[$i]['line'] : 'n/a';

                    $this->write(sprinf(' %s%s%s() at <info>%s:%s</info>', $class, $type, $function, $file, $line), true, Output::OUTPUT_NORMAL, $stderr);
                }

                $this->write('', true, Output::OUTPUT_NORMAL, $stderr);
                $this->write('', true, Output::OUTPUT_NORMAL, $stderr);
            }
        } while ($e = $e->getPrevious());
    }

    /**
     * Note: 打开标准输出流句柄
     * Date: 2024-01-20
     * Time: 11:14
     * @return false|resource
     */
    private function openOutputStream()
    {
        if (!$this->hasStdoutSupport()) {
            return fopen('php://output', 'w');
        }

        return @fopen('php://stdout', 'w') ?: fopen('php://output', 'w');
    }

    /**
     * Note: 打开标准输出句柄
     * Date: 2024-01-20
     * Time: 11:24
     * @return false|resource
     */
    private function openErrorStream()
    {
        return @fopen($this->hasStderrSupport() ? 'php://stderr' : 'php://output', 'w');
    }

    /**
     * Note: 当前环境是否支持写入控制台输出到stdout.
     * Date: 2024-01-20
     * Time: 11:15
     * @return bool
     */
    protected function hasStdoutSupport()
    {
        return $this->isRunningOS400() === false;
    }

    /**
     * Note: 当前环境是否支持写入控制台输出到stderr.
     * Date: 2024-01-20
     * Time: 11:17
     * @return bool
     */
    protected function hasStderrSupport()
    {
        return $this->isRunningOS400() === false;
    }

    /**
     * Note: 是否运行在OS400操作系统中
     * Date: 2024-01-20
     * Time: 11:15
     * @return bool
     */
    private function isRunningOS400()
    {
        $checks = [
            function_exists('php_uname') ? php_uname('s') : '',
            getenv('OSTYPE'),
            PHP_OS
        ];

        return stripos(implode(';', $checks), 'OS400') !== false;
    }

    /**
     * Note: 是否支持着色
     * Date: 2024-01-19
     * Time: 18:26
     * @param $stream
     * @return bool
     */
    public function hasColorSupport($stream)
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return '10.0.10586' === PHP_WINDOWS_VERSION_MAJOR . '.' . PHP_WINDOWS_VERSION_MINOR . '.' . PHP_WINDOWS_VERSION_BUILD
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM');
        }

        return function_exists('posix_isatty') && @posix_isatty($stream);
    }

    /**
     * Note: 将消息写入到输出
     * Date: 2024-01-20
     * Time: 10:53
     * @param string $message 消息
     * @param bool $newline 是否另起一行
     * @param mixed $stream 数据流
     */
    protected function doWrite($message, $newline, $stream = null)
    {
        if ($stream === null) {
            $stream = $this->stdout;
        }

        if (@fwrite($stream, $message . ($newline ? PHP_EOL : '')) === false) {
            throw new \RuntimeException('Unable to write output.');
        }

        fflush($stream);
    }

    /**
     * Note: 计算字符串的宽度
     * Date: 2024-01-20
     * Time: 14:42
     * @param string $string
     * @return false|int
     */
    private function stringWidth(string $string)
    {
        if (!function_exists('mb_strwidth')) {
            return strlen($string);
        }

        if ($encoding = mb_detect_encoding($string) === false) {
            return strlen($string);
        }

        return mb_strwidth($string, $encoding);
    }

    /**
     * Note: 获取终端宽度
     * Date: 2024-01-20
     * Time: 14:44
     * @return int|null
     */
    protected function getTerminalWidth()
    {
        $dimensions = $this->getTerminalDimensions();

        return $dimensions[0];
    }

    /**
     * Note: 获取终端高度
     * Date: 2024-01-20
     * Time: 14:48
     * @return mixed
     */
    protected function getTerminalHeight()
    {
        $dimensions = $this->getTerminalDimensions();

        return $dimensions[1];
    }

    /**
     * Note: 获取当前终端的尺寸
     * Date: 2024-01-20
     * Time: 17:08
     * @return array
     */
    public function getTerminalDimensions()
    {
        if ($this->terminalDimensions) {
            return $this->terminalDimensions;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            if (preg_match('/^(\d+)x(\d+)$/', $this->getMode(), $matches)) {
                return [$matches[1], $matches[2]];
            }
        }

        if ($sttyString = $this->getSttyColumns()) {
            if (preg_match('/rows.(\d+);.columns.(\d+);/i', $sttyString, $matches)) {
                return [(int)$matches[2], (int)$matches[1]];
            }
            if (preg_match('/;.(\d+).rows;.(\d+).columns/i', $sttyString, $matches)) {
                return [(int)$matches[2], (int)$matches[1]];
            }
        }

        return [null, null];
    }

    /**
     * Note: 获取终端数据
     * Date: 2024-01-20
     * Time: 17:13
     */
    private function getMode()
    {
        if (!function_exists('proc_open')) {
            return '';
        }

        $descriptorspec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open('mode CON', $descriptorspec, $pipes, null, null, ['suppress_errors' => true]);
        if (is_resource($process)) {
            $info = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            if (preg_match('/--------+\r?\n.+?(\d+)\r?\n.+?(\d+)\r?\n/', $info, $matches)) {
                return $matches[2] . 'x' . $matches[1];
            }
        }

        return '';
    }

    /**
     * Note: 获取stty列数
     * Date: 2024-01-24
     * Time: 16:16
     * @return false|string|void
     */
    private function getSttyColumns()
    {
        if (!function_exists('proc_open')) {
            return;
        }

        $descriptorspec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open('stty -a | grep columns', $descriptorspec, $pipes, null, null, ['suppress_errors' => true]);
        if (is_resource($process)) {
            $info = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            return $info;
        }
        return;
    }

    /**
     * Note: 根据指定的宽度拆分字符串
     * Date: 2024-01-24
     * Time: 16:52
     * @param string $string
     * @param $width
     * @return array
     */
    private function splitStringByWith(string $string, $width)
    {
        if (!function_exists('mb_strwidth')) {
            return str_split($string, $width);
        }

        if ($encoding = mb_detect_encoding($string) === false) {
            return str_split($string, $width);
        }

        $utf8String = mb_convert_encoding($string, 'utf8', $encoding);

        $lines = [];
        $line = '';
        foreach (preg_split('//u', $utf8String) as $char) {
            if (mb_strwidth($line . $charm, 'utf8') <= $width) {
                $line .= $char;
                continue;
            }
            $lines[] = str_pad($line, $width);
            $line = $char;
        }
        if (strlen($line)) {
            $lines[] = count($lines) ? str_pad($line, $width) : $line;
        }

        mb_convert_variables($encoding, 'utf8', $lines);

        return $lines;
    }

}