<?php
declare(strict_types=1);

namespace Enna\Framework\Filesystem\Driver;

use Enna\Framework\Filesystem\Driver;
use League\Flysystem\FilesystemAdapter;
use AlphaSnow\Flysystem\Aliyun\AliyunFactory;

class Aliyun extends Driver
{
    /**
     * Note: 创建适配器
     * Date: 2023-01-14
     * Time: 10:27
     * @return FilesystemAdapter
     */
    protected function createAdapter(): FilesystemAdapter
    {
        $aliyunFactory = new AliyunFactory();
        $adapter = $aliyunFactory->createAdapter($this->config);

        return $adapter;
    }
}