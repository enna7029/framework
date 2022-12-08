<?php

namespace Enna\Framework\Log\Driver;

use Enna\Framework\App;
use Enna\Framework\Contract\LogHandlerInterface;

class File implements LogHandlerInterface
{
    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        'time_format' => 'Y-m-d H:i:s',
        'single' => false,
        'file_size' => 2097152,
        'path' => '',
        'apart_level' => [],
        'max_files' => 0,
        'json' => false,
        'json_options' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        'format' => '[%s][%s] %s'
    ];

    public function __construct(App $app, $config = [])
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
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

    }
}