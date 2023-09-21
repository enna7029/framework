<?php
declare(strict_types=1);

namespace Enna\Framework;

use ArrayAccess;

/**
 * Env管理类
 * Class Env
 * @package Enna\Framework
 */
class Env implements ArrayAccess
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
     * @param string $file 环境变量定义文件
     * @return void
     */
    public function load(string $file)
    {
        $env = parse_ini_file($file, true, INI_SCANNER_RAW) ?: [];
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
     * @param mixed $default 默认值
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

    /**
     * Note: 检查是否存在环境变量
     * Date: 2023-07-06
     * Time: 10:35
     * @param string $name
     * @return bool
     */
    public function has(string $name)
    {
        return !is_null($this->get($name));
    }

    /**
     * Note: 设置环境变量
     * Date: 2023-07-06
     * Time: 10:36
     * @param string $name 参数名
     * @param mixed $value 值
     */
    public function __set(string $name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Note: 获取环境变量
     * Date: 2023-07-06
     * Time: 10:38
     * @param string $name
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * Note: 检查是否存在环境变量
     * Date: 2023-07-06
     * Time: 10:38
     * @param string $name 参数名
     * @return bool
     */
    public function __isset(string $name)
    {
        return $this->has($name);
    }

    public function offsetSet($name, $value): void
    {
        $this->set($name, $value);
    }

    public function offsetExists($name): bool
    {
        return $this->__isset($name);
    }

    public function offsetUnset($name)
    {
        throw new Exception('not support: unset');
    }

    public function offsetGet($name)
    {
        return $this->get($name);
    }

}