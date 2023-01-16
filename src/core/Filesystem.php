<?php
declare(strict_types=1);

namespace Enna\Framework;

use Enna\Framework\Manager;
use InvalidArgumentException;
use Enna\Framework\Filesystem\Driver;

class Filesystem extends Manager
{
    protected $namespace = '\\Enna\\Framework\\Filesystem\\Driver\\';

    /**
     * Note: 获取文件系统配置
     * Date: 2023-01-10
     * Time: 16:54
     * @param string|null $name 配置名
     * @param mixed $default 默认值
     * @return array|mixed|null
     */
    public function getConfig(string $name = null, $default = null)
    {
        if (!is_null($name)) {
            return $this->app->config->get('filesystem.' . $name, $default);
        }

        return $this->app->config->get('filesystem');
    }

    /**
     * Note: 获取磁盘配置
     * Date: 2023-01-10
     * Time: 17:22
     * @param string $disk 磁盘名
     * @param null $name 配置名称
     * @param null $default 配置默认值
     * @return array|mixed|null
     */
    public function getDiskConfig($disk, $name = null, $default = null)
    {
        $config = $this->getConfig('disks.' . $disk);

        if ($config) {
            if (!is_null($name)) {
                foreach ($config as $key => $item) {
                    if ($name == $key && !empty($item)) {
                        return $item;
                    } else {
                        return $default;
                    }
                }
            } else {
                return $config;
            }
        }

        throw new InvalidArgumentException('磁盘【' . $disk . '】未找到');
    }

    /**
     * Note: 获取默认驱动
     * Date: 2023-01-10
     * Time: 17:15
     * @return array|mixed|null
     */
    public function getDefaultDriver()
    {
        return $this->getConfig('default');
    }

    /**
     * Note: 获取驱动
     * Date: 2023-01-10
     * Time: 17:17
     * @param string|null $name
     * @return Driver
     */
    public function disk(string $name = null)
    {
        return $this->driver($name);
    }

    /**
     * Note: 获取配置类型
     * Date: 2023-01-10
     * Time: 17:24
     * @param string $name
     * @return array|mixed|null
     */
    public function resolveType(string $name)
    {
        return $this->getDiskConfig($name, 'type', 'local');
    }

    /**
     * Note: 获取配置
     * Date: 2023-01-10
     * Time: 17:24
     * @param string $name
     * @return array|mixed|null
     */
    public function resolveConfig(string $name)
    {
        return $this->getDiskConfig($name);
    }
}