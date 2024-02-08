<?php
declare(strict_types=1);

namespace Enna\Framework\Helper;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Enna\Framework\Contract\Jsonable;
use Enna\Framework\Contract\Arrayable;
use ArrayIterator;

/**
 * 数据集管理
 * Class Collection
 * @package Enna\Framework\Helper
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Jsonable, Arrayable
{
    /**
     * 数据集数据
     * @var array
     */
    protected $items = [];

    public function __construct($items = [])
    {
        $this->items = $this->convertToArray($items);
    }

    public static function make($items = [])
    {
        return new static($items);
    }

    /**
     * Note: 是否为空
     * Date: 2023-04-13
     * Time: 10:25
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Note: 获取所有数据
     * Date: 2023-04-11
     * Time: 11:52
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Note: 合并其它数据
     * Date: 2023-04-13
     * Time: 11:31
     * @param mixed $items 数据
     * @return static
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $this->convertToArray($items)));
    }

    /**
     * Note: 按指定的键整理数据
     * Date: 2023-04-13
     * Time: 15:35
     * @param mixed $items 数据
     * @param string $indexKey 键名
     * @return array
     */
    public function dictionary($items = null, string &$indexKey = null)
    {
        if ($items instanceof self) {
            $items = $items->all();
        }
        $items = is_null($items) ? $this->items : $items;

        if ($items && empty($indexKey)) {
            $indexKey = is_array($items[0]) ? 'id' : $items[0]->getPk();
        }

        if (isset($indexKey) && is_string($indexKey)) {
            return array_column($items, null, $indexKey);
        }

        return $items;
    }

    /**
     * Note: 比较数组,返回差集
     * Date: 2023-04-13
     * Time: 11:33
     * @param mixed $items 数据
     * @param string|null $indexKey 指定比较的键名
     * @return static
     */
    public function diff($items, string $indexKey = null)
    {
        if ($this->isEmpty() || is_scalar($this->$items[0])) {
            return new static(array_diff($this->items, $this->convertToArray($items)));
        }

        $diff = [];
        $dictionary = $this->dictionary($items, $indexKey);

        if (is_string($indexKey)) {
            foreach ($this->items as $item) {
                if (!isset($dictionary[$item[$indexKey]])) {
                    $diff[] = $item;
                }
            }
        }

        return new static($diff);
    }

    /**
     * Note: 比较数组,返回交集
     * Date: 2023-04-13
     * Time: 15:46
     * @param mixed $items 数据
     * @param string|null $indexKey 指定比较的键名
     * @return static
     */
    public function intersect($items, string $indexKey = null)
    {
        if ($this->isEmpty() || is_scalar($this->$items[0])) {
            return new static(array_diff($this->items, $this->convertToArray($items)));
        }

        $intersect = [];
        $dictionary = $this->dictionary($items, $indexKey);

        if (is_string($indexKey)) {
            foreach ($this->items as $item) {
                if (isset($dictionary[$item[$indexKey]])) {
                    $intersect[] = $item;
                }
            }
        }

        return new static($intersect);
    }

    /**
     * Note: 交换数组中键和值
     * Date: 2023-04-13
     * Time: 15:49
     * @return static
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * Note: 返回数组中的所有键名
     * Date: 2023-04-13
     * Time: 16:18
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * Note: 返回数组中的所有值
     * Date: 2023-04-13
     * Time: 16:19
     * @return static
     */
    public function values()
    {
        return new static(array_valus($this->items));
    }

    /**
     * Note: 删除数组中的最后一个元素
     * Date: 2023-04-13
     * Time: 16:25
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Note: 通过使用用户自定义函数，以字符串返回数组
     * Date: 2023-04-13
     * Time: 16:27
     * @param callable $callback 调用方法
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Note: 数据倒序重排
     * Date: 2023-04-13
     * Time: 16:29
     * @return static
     */
    public function reverse()
    {
        return new static(array_reverse($this->items));
    }

    /**
     * Note: 在数组开头插入一个元素
     * Date: 2023-04-13
     * Time: 16:40
     * @param mixed $value 元素
     * @param string|null $key key
     * @return $this
     */
    public function unshift($value, string $key = null)
    {
        if (is_null($key)) {
            array_unshift($this->items, $value);
        } else {
            $this->items = [$key => $value] + $this->items;
        }

        return $this;
    }

    /**
     * Note: 在数组最后插入一个元素
     * Date: 2023-04-13
     * Time: 16:30
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Note: 删除数组中的最后一个元素
     * Date: 2023-04-13
     * Time: 16:47
     * @param mixed $value 元素
     * @param string|null $key key
     * @return $this
     */
    public function push($value, string $key = null)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }

        return $this;
    }

    /**
     * Note: 把一个数组分割为新的数组快
     * Date: 2023-04-13
     * Time: 17:17
     * @param int $size 块大小
     * @param bool $preserveKeys 是否包含key
     * @return static
     */
    public function chunk(int $size, bool $preserveKeys = false)
    {
        $chunks = [];

        foreach (array_chunk($this->items, $size, $preserveKeys) as $chunk) {
            $chunks[] = $chunk;
        }

        return new static($chunks);
    }

    /**
     * Note: 给每个元素执行个回调
     * Date: 2023-04-13
     * Time: 17:20
     * @param callable $callback 回调
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            $result = $callback($item, $key);

            if ($result === false) {
                continue;
            } elseif (!is_object($item)) {
                $this->items[$key] = $result;
            }
        }

        return $this;
    }

    /**
     * Note: 用回调函数处理数组中的元素
     * Date: 2023-04-13
     * Time: 17:27
     * @param callable $callback 回调
     * @return static
     */
    public function map(callable $callback)
    {
        return new static(array_map($callback, $this->items));
    }

    /**
     * Note: 用回调函数过滤数组中的元素
     * Date: 2023-04-13
     * Time: 17:32
     * @param callable|null $callback 回调
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback));
        }

        return new static(array_filter($this->items));
    }

    /**
     * Note: 返回数据中指定的一列
     * Date: 2023-04-13
     * Time: 17:41
     * @param string $columnKey 键名
     * @param string $indexKey 作为索引的列
     * @return array
     */
    public function column(string $columnKey, string $indexKey)
    {
        return array_column($this->items, $columnKey, $indexKey);
    }

    /**
     * Note: 对数据排序
     * Date: 2023-04-13
     * Time: 18:22
     * @param callable $callback
     * @return static
     */
    public function sort(callable $callback)
    {
        $items = $this->items;

        $callback = $callback ?: function ($a, $b) {
            return $a == $b ? 0 : (($a < $b) ? -1 : 1);
        };

        uasort($items, $callback);

        return new static($items);
    }

    /**
     * Note: 指定字段排序
     * Date: 2023-04-13
     * Time: 18:26
     * @param string $field 排序字段
     * @param string $order 排序方式
     * @return static
     */
    public function order(string $field, string $order = 'asc')
    {
        return $this->sort(function ($a, $b) use ($field, $order) {
            $fieldA = $a[$field] ?? null;
            $fieldB = $b[$field] ?? null;

            return strolower($order) === 'desc' ? intval($fieldB > $fieldA) : intval($fieldB < $fieldA);
        });
    }

    /**
     * Note: 打乱数组
     * Date: 2023-04-13
     * Time: 18:36
     * @return static
     */
    public function shuffle()
    {
        $items = $this->items;

        shuffle($items);

        return new static($items);
    }

    /**
     * Note: 截取数组
     * Date: 2023-04-13
     * Time: 18:38
     * @param int $offset 起始位置
     * @param int|null $length 截取长度
     * @param bool $preserveKeys 是否包含key
     * @return static
     */
    public function slice(int $offset, int $length = null, bool $preserveKeys = false)
    {
        return new static(array_slice($this->items, $offset, $length, $preserveKeys));
    }

    /**
     * Note: 获取第一个单元数据
     * Date: 2023-04-14
     * Time: 9:47
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        return Arr::first($this->items, $callback, $default);
    }

    /**
     * Note: 获取最后一个单元数据
     * Date: 2023-04-14
     * Time: 9:47
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        return Arr::last($this->items, $callback, $default);
    }

    /**
     * Note: 根据字段条件过滤数组中的元素
     * Date: 2023-04-14
     * Time: 10:52
     * @param string $field 字段名
     * @param mixed $operator 操作符
     * @param mixed $value 数据
     * @return static
     */
    public function where(string $field, $operator, $value = null)
    {
        if (is_null($value)) {
            $value = $operator;
            $operator = '=';
        }

        return $this->fliter(function ($data) use ($field, $oerator, $value) {
            if (strpos($field, '.')) {
                [$field, $relation] = explode('.', $field);

                $result = $data[$field][$relation] ?? null;
            } else {
                $result = $data[$field] ?? null;
            }

            switch (strolower($operator)) {
                case '===':
                    return $result === $value;
                case '!==':
                    return $result !== $value;
                case '!=':
                case '<>':
                    return $result != $value;
                case '>':
                    return $result > $value;
                case '>=':
                    return $result >= $value;
                case '<':
                    return $result < $value;
                case '<=':
                    return $result <= $value;
                case 'like':
                    return is_string($result) && strpos($result, $value) !== false;
                case 'not like':
                    return is_string($result) && strpos($result, $value) === false;
                case 'in':
                    return is_scalar($result) && in_array($result, $value, true);
                case 'not in':
                    return is_scalar($result) && !in_array($result, $value, true);
                case 'between':
                    [$min, $max] = is_string($value) ? explode(',', $value) : $value;
                    return is_scalar($result) && $result >= $min && $result <= $max;
                case 'not between':
                    [$min, $max] = is_string($value) ? explode(',', $value) : $value;
                    return is_scalar($result) && $result < $min || $result > $max;
                case '==':
                case '=':
                default:
                    return $result == $value;
            }
        });
    }

    /**
     * Note: LIKE过滤
     * Date: 2023-04-14
     * Time: 14:07
     * @param string $field 字段名
     * @param string $value 数据
     * @return static
     */
    public function whereLike(string $field, string $value)
    {
        return $this->where($field, 'like', $value);
    }

    /**
     * Note: NOT LIKE过滤
     * Date: 2023-04-14
     * Time: 14:12
     * @param string $field 字段名
     * @param string $value 数据
     * @return static
     */
    public function whereNotLike(string $field, string $value)
    {
        return $this->where($field, 'not like', $value);
    }

    /**
     * Note: IN过滤
     * Date: 2023-04-14
     * Time: 14:21
     * @param string $field 字段名
     * @param array $value 数据
     * @return static
     */
    public function whereIn(string $field, array $value)
    {
        return $this->where($field, 'in', $value);
    }

    /**
     * Note: NOT IN过滤
     * Date: 2023-04-14
     * Time: 14:21
     * @param string $field 字段名
     * @param array $value 数据
     * @return static
     */
    public function whereNotIn(string $field, array $value)
    {
        return $this->where($field, 'not in', $value);
    }

    /**
     * Note: BETWEEN过滤
     * Date: 2023-04-14
     * Time: 14:22
     * @param string $field 字段名
     * @param mixed $value 数据
     * @return static
     */
    public function whereBetween(string $field, $value)
    {
        return $this->where($field, 'between', $value);
    }

    /**
     * Note: NOT BETWEEN过滤
     * Date: 2023-04-14
     * Time: 14:23
     * @param string $field 字段名
     * @param mixed $value 数据
     * @return static
     */
    public function whereNotBetween(string $field, $value)
    {
        return $this->where($field, 'not between', $value);
    }

    /**
     * Note: 转换为数组
     * Date: 2023-04-11
     * Time: 11:50
     * @param mixed $items 数据
     * @return array
     */
    protected function convertToArray($items)
    {
        if ($items instanceof self) {
            return $items->all();
        }

        return (array)$items;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    public function count()
    {
        return count($this->items);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Note: 转换为数组
     * Date: 2023-04-13
     * Time: 10:25
     * @return array
     */
    public function toArray(): array
    {
        return array_map(function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * Note: 转换为JSON字符串
     * Date: 2023-04-14
     * Time: 10:30
     * @return string
     */
    public function toJson(int $options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->toArray(), $options);
    }

    public function __toString()
    {
        return $this->toJson();
    }
}