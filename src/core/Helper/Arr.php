<?php
declare(strict_types=1);

namespace Enna\Framework\Helper;

use ArrayAccess;
use Closure;

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
        if (!static::accessible($data)) {
            return $default;
        }

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
     * Note: 从给定数组中删除一个或多个数组项
     * Date: 2023-03-02
     * Time: 11:52
     * @param array $data 数据源
     * @param string|array $keys 键(对多个键,使用.符号分隔)
     * @return void
     */
    public static function delete(&$array, $keys)
    {
        $original = &$array;

        $keys = (array)$keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
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

    /**
     * Note: 返回数组中通过给定真值测试的第一个元素
     * Date: 2023-04-14
     * Time: 9:33
     * @param array $array 数据
     * @param callable|null $callback 回调
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function first($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return $default instanceof Closure ? $default() : $default;
            }

            foreach ($array as $item) {
                return $item;
            }
        }

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }

        return $default instanceof Closure ? $default() : $default;
    }

    /**
     * Note: 返回数组中通过给定真值测试的最后一个元素
     * Date: 2023-04-14
     * Time: 9:41
     * @param aray $array 数据
     * @param callable|null $callback 回调
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function last($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($array) ? ($default instanceof Closure ? $default() : $default) : end($array);
        }

        return static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Note: 查看数组中是否存在给定的key
     * Date: 2023-08-22
     * Time: 10:53
     * @param array|\ArrayAccess $array
     * @param string|int $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }
}