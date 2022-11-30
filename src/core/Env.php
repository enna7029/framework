<?php
declare(strict_types=1);

namespace Enna\Framework;

class Env
{
    /**
     * 环境变量数据
     * @var array
     */
    protected $data;

    protected $convert = [
        'true' => true,
        'false' => false,
        'on' => true,
        'off' => false
    ];

    public function __construct()
    {
        $this->data = $_ENV;
    }

    /**
     * Note: 读取环境变量文件
     * Date: 2022-09-15
     * Time: 18:05
     * @param string $file
     */
    public function load(string $file)
    {
        $env = parse_ini_file($file, true, INI_SCANNER_RAW);
        $this->set($env);
    }

    /**
     * Note: 设置环境变量
     * Date: 2022-09-15
     * Time: 18:12
     * @param string|array $env 环境变量
     * @param null $value 值
     * @return void
     */
    public function set($env, $value = null)
    {
        if (is_array($env)) {
            $env = array_change_key_case($env, CASE_UPPER);

            foreach ($env as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $this->data[$key . '_' . strtoupper($k)] = $v;
                    }
                } else {
                    $this->data[$key] = $value;
                }
            }
        } else {
            $key = strtoupper(str_replace('.', '_', $env));
            $this->data[$key] = $value;
        }
    }

    /**
     * Note: 获取环境变量
     * Date: 2022-09-15
     * Time: 18:22
     * @param string $name 环境变量名
     * @param null $default 默认值
     * @return mixed
     */
    public function get(string $name = null, $default = null)
    {
        if (is_null($name)) {
            return $this->data;
        }

        $name = strtoupper(str_replace('.', '_', $name));
        if (isset($this->data[$name])) {
            $result = $this->data[$name];

            if (is_string($result) && isset($this->convert[$result])) {
                return $this->convert[$result];
            }

            return $result;
        }

        return $default;
    }
}