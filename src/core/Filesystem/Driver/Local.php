<?php
declare(strict_types=1);

namespace Enna\Framework\Filesystem\Driver;

use Enna\Framework\Filesystem\Driver;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\Flysystem\FilesystemAdapter;

class Local extends Driver
{
    /**
     * Note: 创建适配器
     * Date: 2023-01-13
     * Time: 15:17
     * @return FilesystemAdapter
     */
    protected function createAdapter(): FilesystemAdapter
    {
        $visibility = PortableVisibilityConverter::fromArray(
            $this->config['permissions'] ?? [],
            $this->config['directory_visibility'] ?? $this->config['visibility'] ?? Visibility::PRIVATE
        );

        $links = ($this->config['links'] ?? null) === 'skip' ? LocalFilesystemAdapter::SKIP_LINKS : LocalFilesystemAdapter::DISALLOW_LINKS;

        $adapter = new LocalFilesystemAdapter($this->config['root'], $visibility, $this->config['lock'] ?? LOCK_EX, $links);

        return $adapter;
    }
}