<?php
declare(strict_types=1);

namespace Enna\Framework;

use Enna\Framework\Session\Store;

/**
 * Class Session
 * @package Enna\Framework
 * @mixin Store
 */
class Session extends Manager
{
    protected $namespace = '\\Enna\\Framework\\Session\\Driver\\';

    /**
     * Note: 获取默认驱动
     * Date: 2023-03-01
     * Time: 16:08
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app->config->get('session.type', 'file');
    }

    /**
     * Note: 创建驱动实例
     * Date: 2023-03-01
     * Time: 16:10
     * @param string $name 驱动名称
     * @return Object
     */
    protected function createDriver(string $name)
    {
        $handler = parent::createDriver($name);

        return new Store($this->getConfig('name') ?: 'PHPSESSION', $handler, $this->getConfig('serialize'));
    }

    /**
     * Note: 获取session配置
     * Date: 2023-03-01
     * Time: 16:20
     * @param string|null $name
     * @param mixed $default
     * @return mixed
     */
    public function getConfig(string $name = null, $default = null)
    {
        if (!is_null($name)) {
            return $this->app->config->get('session.' . $name, $default);
        }

        return $this->app->config->get('session');
    }

    /**
     * Note: 解析配置
     * Date: 2023-03-01
     * Time: 16:17
     * @param string $name
     * @return mixed
     */
    public function resolveConfig(string $name)
    {
        $config = $this->app->config->get('session', []);

        return $config;
    }
}