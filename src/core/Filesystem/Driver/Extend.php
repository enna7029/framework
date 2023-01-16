<?php
declare(strict_types=1);

namespace Enna\Framework\Filesystem\Driver;

use Enna\Framework\Filesystem\Driver;
use League\Flysystem\FilesystemAdapter;

class Extend extends Driver
{
    /**
     * Note: 创建适配器
     * Date: 2023-01-16
     * Time: 15:16
     * @param $adapter
     * @return FilesystemAdapter
     */
    protected function createAdapter(): FilesystemAdapter
    {
        return '';
    }
}