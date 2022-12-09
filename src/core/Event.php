<?php
declare(strict_types=1);

namespace Enna\Framework;

use Enna\Framework\Exception\ClassNotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

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
        'AppInit' => event\AppInit::class,
        'HttpRun' => event\HttpRun::class,
        'LogWrite' => event\LogWrite::class,
        'LogRecord' => event\LogRecord::class,
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
     * Note: 注册事件监听
     * Date: 2022-09-17
     * Time: 11:48
     * @param array $events 监听者
     */
    public function listenEvents(array $events)
    {
        foreach ($events as $event => $listener) {
            if (isset($this->bind[$event])) {
                $event = $this->bind[$event];
            }

            $this->listener[$event] = array_merge($this->listener[$event] ?: [], $listener);
        }

        return $this;
    }

    /**
     * Note: 注册事件订阅
     * Date: 2022-09-17
     * Time: 14:05
     * @param array $events 订阅者
     */
    public function subscribe(array $events)
    {
        foreach ($events as $subscriber) {
            if (is_string($subscriber)) {
                $subscriber = $this->app->make($subscriber);
            }

            $this->observe($subscriber);
        }
    }

    /**
     * Note: 订阅
     * Date: 2022-09-17
     * Time: 14:17
     * @param $subscriber
     */
    public function observe($subscriber)
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

        foreach ($methods as $method) {
            $name = $method->getName();
            if (strpos($name, 'on') === 0) {
                $this->listen(substr($name, 2), [$subscriber, $name]);
            }
        }

        return $this;
    }

    /**
     * Note: 注册监听事件
     * Date: 2022-09-17
     * Time: 14:38
     * @param string $event 时间名
     * @param array $listener 监听操作
     */
    public function listen(string $event, array $listener)
    {
        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }

        $this->listener[$event][] = $listener;

        return $this;
    }

    /**
     * Note: 触发事件
     * Date: 2022-09-19
     * Time: 16:42
     * @param string|object $event
     * @return mixed
     */
    public function trigger($event)
    {
        if (is_object($event)) {
            $event  = get_class($event);
        }

        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }
        $result = [];
        $listeners = $this->listener[$event] ?? [];

        foreach ($listeners as $key => $listener) {
            $result[$key] = $this->dispatch($listeners);
        }

        return $result;
    }

    /**
     * Note: 执行事件调度
     * Date: 2022-09-19
     * Time: 17:44
     * @param string $event 事件
     */
    protected function dispatch(string $event)
    {
        $obj = $this->app->make($event);
        $call = [$obj, 'handle'];

        return $this->app->invoke($call);
    }

}