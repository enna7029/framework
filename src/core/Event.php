<?php
declare(strict_types=1);

namespace Enna\Framework;

use Enna\Framework\Exception\ClassNotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * 事件管理类
 * Class Event
 * @package Enna\Framework
 */
class Event
{
    /**
     * 应用对象
     * @var App
     */
    protected $app;

    /**
     * 事件别名
     * @var string[]
     */
    protected $bind = [
        'AppInit' => Event\AppInit::class,
        'HttpRun' => Event\HttpRun::class,
        'HttpEnd' => Event\HttpEnd::class,
        'LogRecord' => Event\LogRecord::class,
        'LogWrite' => Event\LogWrite::class,
        'RouteLoaded' => Event\RouteLoaded::class,
    ];

    /**
     * 监听者
     * @var array
     */
    protected $listener = [];

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Note: 指定事件别名
     * Date: 2022-09-16
     * Time: 18:59
     * @param array $events 事件别名和事件类
     * @return $this
     */
    public function bind(array $events)
    {
        $this->bind = array_merge($this->bind, $events);

        return $this;
    }

    /**
     * Note: 注册监听事件
     * Date: 2022-09-17
     * Time: 14:38
     * @param string $event 事件名
     * @param array $listener 监听操作(或类名)
     * @param bool $first 是否优先执行
     * @return $this
     */
    public function listen(string $event, array $listener, bool $first = false)
    {
        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }

        if ($first && isset($this->listener[$event])) {
            array_unshift($this->listener[$event], $listener);
        } else {
            $this->listener[$event][] = $listener;
        }

        return $this;
    }

    /**
     * Note: 批量注册事件监听
     * Date: 2022-09-17
     * Time: 11:48
     * @param array $events 事件定义
     * @return $this
     */
    public function listenEvents(array $events)
    {
        foreach ($events as $event => $listener) {
            if (isset($this->bind[$event])) {
                $event = $this->bind[$event];
            }

            $this->listener[$event] = array_merge($this->listener[$event] ?? [], $listener);
        }

        return $this;
    }

    /**
     * Note: 是否存在事件监听
     * Date: 2023-07-07
     * Time: 16:32
     * @param string $event 事件名称
     * @return bool
     */
    public function hasListener(string $event)
    {
        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }

        return isset($this->listener[$event]);
    }

    /**
     * Note: 移除事件监听
     * Date: 2023-07-07
     * Time: 16:33
     * @param string $event 事件名称
     * @return void
     */
    public function remove(string $event)
    {
        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }

        unset($this->listener[$event]);
    }

    /**
     * Note: 注册事件订阅
     * Date: 2022-09-17
     * Time: 14:05
     * @param mixed $subscriber 订阅者
     * @return $this
     */
    public function subscribe($subscriber)
    {
        $subscribers = (array)$subscriber;

        foreach ($subscribers as $subscriber) {
            if (is_string($subscriber)) {
                $subscriber = $this->app->make($subscriber);
            }

            if (method_exists($subscriber, 'subscribe')) {
                $subscriber->subscribe($this);
            } else {
                $this->observe($subscriber);
            }
        }

        return $this;
    }

    /**
     * Note: 订阅
     * Date: 2022-09-17
     * Time: 14:17
     * @param string|object $subscriber 观察者
     * @param string $prefix 事件名前缀
     * @return $this
     */
    public function observe($subscriber, string $prefix = '')
    {
        if (is_string($subscriber)) {
            $subscriber = $this->app->make($subscriber);
        }

        try {
            $reflect = new ReflectionClass($subscriber);
        } catch (ReflectionException $e) {
            throw new ClassNotFoundException('class not exists: ' . $subscriber, $subscriber, $e);
        }
        $methods = $reflect->getMethods(ReflectionMethod::IS_PUBLIC);

        if (empty($prefix) && $reflect->hasProperty('eventPrefix')) {
            $reflectProperty = $reflect->hasProperty('eventPrefix');
            $reflectProperty->setAccessible(true);
            $prefix = $reflectProperty->getValue($observer);
        }

        foreach ($methods as $method) {
            $name = $method->getName();
            if (strpos($name, 'on') === 0) {
                $this->listen($prefix . substr($name, 2), [$subscriber, $name]);
            }
        }

        return $this;
    }

    /**
     * Note: 触发事件(只获取一个有效值)
     * Date: 2023-07-07
     * Time: 16:45
     * @param mixed $event 事件名
     * @param mixed $params 参数
     * @return mixed
     */
    public function until($event, $params = null)
    {
        return $this->trigger($event, $params, true);
    }

    /**
     * Note: 触发事件
     * Date: 2022-09-19
     * Time: 16:42
     * @param string|object $event 事件名
     * @param mixed $params 参数
     * @param bool $once 只获取一个有效返回值
     * @return mixed
     */
    public function trigger($event, $params = null, bool $once = false)
    {
        if (is_object($event)) {
            $params = $event;
            $event = get_class($event);
        }

        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }
        $result = [];
        $listeners = $this->listener[$event] ?? [];
        $listeners = array_unique($listeners, SORT_REGULAR);

        foreach ($listeners as $key => $listener) {
            $result[$key] = $this->dispatch($listeners, $params);

            if ($result[$key] === false || (!is_null($result[$key]) && $once)) {
                break;
            }
        }

        return $once ? end($result) : $result;
    }

    /**
     * Note: 执行事件调度
     * Date: 2022-09-19
     * Time: 17:44
     * @param mixed $event 事件
     * @param mixed $params 参数
     * @return mixed
     */
    protected function dispatch(string $event, $params = null)
    {
        $obj = $this->app->make($event);
        $call = [$obj, 'handle'];

        return $this->app->invoke($call, [$params]);
    }
}