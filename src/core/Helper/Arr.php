<?php
declare(strict_types=1);

namespace Enna\Framework\Helper;

use ArrayAccess;

class Arr
{
    /**
     * Note: 是否可以数组访问
     * Date: 2023-03-02
     * Time: 10:03
     * @param mixed $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Note: 在数组中设置值
     * Date: 2023-03-02
     * Time: 10:07
     * @param array $data 数据源
     * @param string $name 键(对多个键,使用.符号分隔)
     * @param mixed $value 值
     * @return array
     */
    public static function set(&$data, $name, $value)
    {
        if (!empty($name) && !empty($value)) {
            $keys = explode('.', $name);

            while (count($keys) > 1) {
                $key = array_shift($keys);

                if (!isset($data[$key]) || !is_array($data[$key])) {
                    $data[$key] = [];
                }

                $data =& $data[$key];
            }

            $data[array_shift($keys)] = $value;
        }

        return $data;
    }

    /**
     * Note: 查看数组中是否有值
     * Date: 2023-03-02
     * Time: 10:22
     * @param array $data 数据源
     * @param string $name 键(对多个键,使用.符号分隔)
     * @return bool
     */
    public static function has($data, $name)
    {
        $name = (array)$name;

        if (!$data || $name = []) {
            return false;
        }

        foreach ($name as $item) {
            $subData = $data;

            if (array_key_exists($item, $data)) {
                continue;
            }

            foreach (explode('.', $item) as $sub_name) {
                if (static::accessible($subData) && array_key_exists($sub_name, $subData)) {
                    $subData = $subData[$sub_name];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Note: 获取数据
     * Date: 2023-03-02
     * Time: 11:41
     * @param array $data 数据源
     * @param string $name 键(对多个键,使用.符号分隔)
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get($data, $name, $default = null)
    {
        if (empty($name)) {
            return $data;
        }

        if (array_key_exists($name, $data)) {
            return $data[$name];
        }

        if (strpos($name, '.') === false) {
            return $default;
        }

        foreach (explode('.', $name) as $item) {
            if (static::accessible($data) && array_key_exists($item, $data)) {
                $data = $data[$item];
            } else {
                return $default;
            }
        }

        return $data;
    }

    /**
     * Note: 获取指定的数据
     * Date: 2023-03-02
     * Time: 11:52
     * @param array $data 数据源
     * @param string $name 键(对多个键,使用.符号分隔)
     * @return void
     */
    public static function delete(&$data, $name)
    {
        $name = (array)$name;

        if (count($name) === 0) {
            return;
        }

        foreach ($name as $item) {
            if (array_key_exists($item, $data)) {
                unset($data[$item]);
                continue;
            }

            $parts = explode('.', $item);

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($data[$part]) && is_array($data[$part])) {
                    $data =& $data[$part];
                } else {
                    continue 2;
                }
            }

            unset($data[array_shift($parts)]);
        }
    }

    /**
     * Note: 获取数据并删除
     * Date: 2023-03-02
     * Time: 14:23
     * @param array $data 数据源
     * @param string $name 键(对多个键,使用.符号分隔)
     * @param mix $default 默认值
     * @return mixed
     */
    public static function pull(&$data, $name, $default = null)
    {
        $value = static::get($data, $name, $default);

        static::delete($data, $name);

        return $value;
    }

    /**
     * Note: 去除指定的数据
     * Date: 2023-03-07
     * Time: 11:58
     * @param array $data 数据源
     * @param string 键(对多个键,使用.符号分隔)
     * @return array
     */
    public static function except($data, $name)
    {
        static::delete($data, $name);

        return $data;
    }
}