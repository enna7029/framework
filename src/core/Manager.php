<?php
declare(strict_types=1);

namespace Enna\Framework;

use http\Exception\InvalidArgumentException;

abstract class Manager
{
    /**
     * 应用程序实例
     * @var App
     */
    protected $app;

    /**
     * 驱动
     * @var array
     */
    protected $drivers = [];

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Note: 默认驱动
     * Date: 2022-12-06
     * Time: 17:57
     * @return mixed
     */
    abstract public function getDefaultDriver();

    /**
     * Note: 获取驱动类型
     * Date: 2022-12-07
     * Time: 10:09
     * @param string $name 驱动名称
     * @return mixed
     */
    abstract function resolveType(string $name);

    /**
     * Note: 获取驱动配置
     * Date: 2022-12-07
     * Time: 10:10
     * @param string $name 驱动名称
     * @return mixed
     */
    abstract function resolveConfig(string $name);

    /**
     * Note: 获取驱动类
     * Date: 2022-12-07
     * Time: 12:12
     * @param string $type 驱动名称
     * @return string
     * @throws InvalidArgumentException
     */
    public function resolveClass(string $type)
    {
        if ($this->namespace || strpos($type, '\\') !== false) {
            $class = strpos($type, '\\') !== false ? $type : $this->namespace . ucfirst($type);

            if (class_exists($class)) {
                return $class;
            }
        }

        throw new InvalidArgumentException('驱动【' . $type . '】未找到');
    }

    /**
     * Note: 获取驱动实例
     * Date: 2022-12-06
     * Time: 17:48
     * @param string|null $name 驱动名称
     * @return mixed
     */
    public function driver(string $name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        if (is_null($name)) {
            throw new InvalidArgumentException(sprintf('无法解析的NULL驱动程序:[%s]', static::class));
        }

        return $this->drivers[$name] = $this->getDriver($name);
    }

    /**
     * Note: 获取驱动实例
     * Date: 2022-12-06
     * Time: 17:59
     * @param string|null $name 驱动名称
     * @return mixed
     */
    public function getDriver(string $name = null)
    {
        return $this->drivers[$name] ?? $this->createDriver($name);
    }

    /**
     * Note: 创建驱动
     * Date: 2022-12-06
     * Time: 18:42
     * @param string $name 驱动名称
     * @return mixed
     */
    protected function createDriver(string $name)
    {
        //获取驱动类型
        $type = $this->resolveType($name);
        //获取驱动class
        $class = $this->resolveClass($type);
        //获取驱动配置
        $config = $this->resolveConfig($name);
        //实例化驱动
        return $this->app->invokeClass($class, [$config]);
    }

}