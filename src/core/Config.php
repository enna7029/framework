<?php
declare(strict_types=1);

namespace Enna\Framework;

/**
 * 配置管理类
 * Class Config
 * @package Enna\Framework
 */
class Config
{
    /**
     * 配置信息
     * @var array
     */
    protected $config = [];

    /**
     * 配置文件路径
     * @var string
     */
    protected $path;

    /**
     * 配置文件后缀
     * @var string
     */
    protected $ext;

    public function __construct(string $path = null, string $ext = '.php')
    {
        $this->path = $path ?: '';
        $this->ext = $ext;
    }

    public function __make(App $app)
    {
        $path = $app->getConfigPath();
        $ext = $app->getConfigExt();

        return new static($path, $ext);
    }

    /**
     * Note: 加载配置文件
     * Date: 2022-09-16
     * Time: 17:46
     * @param string $file 配置文件名
     * @param string $name 一级配置名
     * @return array
     */
    public function load(string $file, string $name = '')
    {
        if (is_file($file)) {
            $fileName = $file;
        } elseif ($this->path . $file . $this->ext) {
            $fileName = $this->path . $file . $this->ext;
        }

        if (isset($fileName)) {
            return $this->parse($fileName, $name);
        }

        return $this->config;
    }

    /**
     * Note: 解析配置文件
     * Date: 2022-09-16
     * Time: 17:47
     * @param string $file 配置文件
     * @param string $name 一级配置名
     * @return array
     */
    protected function parse(string $file, string $name)
    {
        $type = pathinfo($file, PATHINFO_EXTENSION);
        $config = [];
        switch ($type) {
            case 'php':
                $config = include $file;
                break;
            case 'yaml':
                if (function_exists('yaml_parse_file')) {
                    $config = yaml_parse_file($file);
                }
                break;
            case 'ini':
                $config = parse_ini_file($file, true, INI_SCANNER_TYPED) ?: [];
                break;
            case 'json':
                $config = json_decode(file_get_contents($file), true);
                break;
        }

        if (isset($config) && !empty($config)) {
            return $this->set($config, strtolower($name));
        } else {
            return [];
        }
    }

    /**
     * Note: 设置配置参数
     * Date: 2022-09-16
     * Time: 17:53
     * @param array $config 配置信息
     * @param string $name 配置名
     * @return array
     */
    public function set(array $config, string $name)
    {
        if (!empty($name)) {
            if (isset($this->config[$name])) {
                $result = array_merge($this->config[$name], $config);
            } else {
                $result = $config;
            }

            $this->config[$name] = $result;
        } else {
            $result = $this->config = array_merge($this->config, array_change_key_case($config));
        }

        return $result;
    }

    /**
     * Note: 获取配置参数,带默认值
     * Date: 2022-09-19
     * Time: 18:39
     * @param string $name 参数名
     * @param null $default 默认值
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        if (empty($name)) {
            return $this->config;
        }

        if (strpos($name, '.') === false) {
            return $this->pull($name);
        }

        $name = explode('.', $name);
        $name[0] = strtolower($name[0]);
        $config = $this->config;

        foreach ($name as $value) {
            if (isset($config[$value])) {
                $config = $config[$value];
            } else {
                return $default;
            }
        }

        return $config;
    }

    /**
     * Note: 获取一级配置
     * Date: 2022-09-19
     * Time: 18:45
     * @param string $name 一级配置名
     * @return array
     */
    public function pull(string $name)
    {
        $name = strtolower($name);

        return $this->config[$name] ?? [];
    }

    /**
     * Note: 检查配置是否存在
     * Date: 2023-07-05
     * Time: 18:38
     * @param string $name 配置参数名 (支持多级配置.号分割)
     * @return bool
     */
    public function has(string $name)
    {
        if (strpos($name, '.') === false && !isset($this->config[strtolower($name)])) {
            return false;
        }

        return !is_null($this->get($name));
    }
}