<?php
declare(strict_types=1);

namespace Enna\Framework\Filesystem\Driver;

use Enna\Framework\Filesystem\Driver;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\PathNormalizer;
use League\Flysystem\WhitespacePathNormalizer;

class Local extends Driver
{
    /**
     * @var PathPrefixer
     */
    protected $prefixer;

    /**
     * @var PathNormalizer
     */
    protected $normalizer;

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
            $this->config['visibility'] ?? $this->config['visibility'] ?? Visibility::PRIVATE
        );

        $links = ($this->config['links'] ?? null) === 'skip'
            ? LocalFilesystemAdapter::SKIP_LINKS
            : LocalFilesystemAdapter::DISALLOW_LINKS;

        $adapter = new LocalFilesystemAdapter(
            $this->config['root'],
            $visibility,
            $this->config['lock'] ?? LOCK_EX,
            $links);

        return $adapter;
    }

    /**
     * Note: 获取文件完整路径
     * Date: 2023-09-21
     * Time: 17:38
     * @param string $path
     * @return string|void
     */
    public function path(string $path)
    {
        $this->prefixer()->prefixPath($path);
    }

    /**
     * Note: 获取文件访问地址
     * Date: 2023-09-21
     * Time: 18:03
     * @param string $path
     * @return string|void
     */
    public function url(string $path)
    {
        $path = $this->normalizer()->normalizePath($path);

        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $path);
        }

        return parent::url($path);
    }

    protected function normalizer()
    {
        if (!$this->normalizer) {
            $this->normalizer = new WhitespacePathNormalizer();
        }

        return $this->normalizer;
    }

    protected function prefixer()
    {
        if (!$this->prefixer) {
            $this->prefixer = new PathPrefixer($this->config['root'], DIRECTORY_SEPARATOR);
        }
    }
}