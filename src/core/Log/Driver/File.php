<?php

namespace Enna\Framework\Log\Driver;

use Enna\Framework\App;
use Enna\Framework\Contract\LogHandlerInterface;

class File implements LogHandlerInterface
{
    /**
     * 通道配置参数
     * @var array
     */
    protected $config = [
        //日期格式
        'time_format' => 'c',
        //单日志文件
        'single' => false,
        //文件大小
        'file_size' => 2097152,
        //路径
        'path' => '',
        //独立日志
        'apart_level' => [],
        //最多文件
        'max_files' => 0,
        //是否JSON格式
        'json' => false,
        //JSON格式二进制掩码
        'json_options' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        //日志格式
        'format' => '[%s][%s] %s'
    ];

    public function __construct(App $app, $config = [])
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }

        if (empty($this->config['format'])) {
            $this->config['format'] = '[%s][%s] %s';
        }

        if (empty($this->config['path'])) {
            $this->config['path'] = $app->getRuntimePath() . 'log';
        }

        if (substr($this->config['path'], -1) != DIRECTORY_SEPARATOR) {
            $this->config['path'] = $this->config['path'] . DIRECTORY_SEPARATOR;
        }
    }

    /**
     * Note: 日志写入
     * Date: 2022-12-08
     * Time: 18:00
     * @param array $log 日志信息
     * @return bool
     */
    public function save(array $log)
    {
        $destination = $this->getMasterLogFile();

        $path = dirname($destination);
        !is_dir($path) && mkdir($path, 0755, true);

        //$time = date('Y-m-d H:i:s');
        //$time = \DateTime::createFromFormat('0.u00 U', microtime())->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format($this->config['time_format']);
        $time = (new \DateTime())->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format('c');

        $info = [];
        foreach ($log as $type => $val) {
            $message = [];
            foreach ($val as $msg) {
                if (!is_string($msg)) {
                    $msg = var_export($msg, true);
                }

                $message[] = $this->config['json'] ? json_encode([$time, $type, $msg], $this->config['json_options']) : sprintf($this->config['format'], $time, $type, $msg);
            }

            if ($this->config['apart_level'] === true || in_array($type, $this->config['apart_level'])) {
                $filename = $this->getApartLevelFile($path, $type);
                $this->write($message, $filename);
                continue;
            }

            $info[$type] = $message;
        }

        if ($info) {
            return $this->write($info, $destination);
        }

        return true;
    }

    /**
     * Note: 日志写入
     * Date: 2022-12-09
     * Time: 13:51
     * @param array $message 日志信息
     * @param string $destination 日志文件
     * @return bool
     */
    protected function write(array $message, string $destination)
    {
        $this->checkLogSize($destination);

        $info = [];
        foreach ($message as $type => $msg) {
            $info[$type] = is_array($msg) ? implode(PHP_EOL, $msg) : $msg;
        }

        $message = implode(PHP_EOL, $info) . PHP_EOL;

        return error_log($message, 3, $destination);
    }

    /**
     * Note: 获取主日志文件:路径+文件名
     * Date: 2022-12-09
     * Time: 10:28
     * @return string
     */
    public function getMasterLogFile()
    {
        //设置max_files后,日志文件不会分日期子目录存放
        if ($this->config['max_files']) {
            $files = glob($this->config['path'] . '*.log');

            try {
                if (count($files) > $this->config['max_files']) {
                    unlink($files[0]);
                }
            } catch (\Exception $e) {

            }
        }

        //是否单文件日志
        if ($this->config['single']) {
            $name = is_string($this->config['single']) ? $this->config['single'] : 'sigle';
            $destination = $this->config['path'] . $name . '.log';
        } else {
            if ($this->config['max_files']) {
                $filename = date('Ymd') . '.log';
            } else {
                $filename = date('Ym') . DIRECTORY_SEPARATOR . date('d') . '.log';
            }
            $destination = $this->config['path'] . DIRECTORY_SEPARATOR . $filename;
        }

        return $destination;
    }

    /**
     * Note: 获取独立日志文件名
     * Date: 2022-12-09
     * Time: 14:01
     * @param string $path 日志文件路径
     * @param string $type 日志类型
     * @return string
     */
    protected function getApartLevelFile(string $path, string $type)
    {
        if ($this->config['max_files']) {
            $name = date('Ymd') . '_' . $type;
        } elseif ($this->config['single']) {
            $name = is_string($this->config['single']) ? $this->config['single'] : 'single';
            $name .= $name . '_' . $type;
        } else {
            $name = date('d') . '_' . $type;
        }

        return $path . DIRECTORY_SEPARATOR . $name . '.log';
    }

    /**
     * Note: 检查日志文件大小,并超过配置的日志文件,自动将备份
     * Date: 2022-12-09
     * Time: 14:11
     * @param string $destination 日志文件(包含路径)
     * @return void
     */
    public function checkLogSize(string $destination)
    {
        if (is_file($destination) && floor($this->config['file_size']) <= filesize($destination)) {
            try {
                rename($destination, dirname($destination) . DIRECTORY_SEPARATOR . time() . '-' . basename($destination));
            } catch (\Exception $e) {

            }
        }
    }
}