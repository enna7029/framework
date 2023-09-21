<?php
declare(strict_types=1);

namespace Enna\Framework\Filesystem;

use http\Exception\RuntimeException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnableToSetVisibility;
use Enna\Framework\File;

abstract class Driver
{
    /**
     * League\Flysystem\Filesystem:文件系统
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * 配置参数
     * @var array
     */
    protected $config = [];

    public function __construct(array $config, $adapter = false)
    {
        $this->config = array_merge($this->config, $config);

        if (!$adapter) {
            $adapter = $this->createAdapter();
        }

        $this->filesystem = $this->createFilesystem($adapter);
    }

    //创建适配器
    abstract protected function createAdapter();

    /**
     * Note: 返回Filesystem
     * Date: 2023-01-13
     * Time: 17:41
     * @return Filesystem
     */
    public function createFilesystem(FilesystemAdapter $adapter)
    {
        $config = array_intersect_key($this->config, array_flip(['visibility', 'disable_asserts', 'url']));

        return new Filesystem($adapter, $config);
    }

    /**
     * Note: 保存文件
     * Date: 2023-01-13
     * Time: 17:49
     * @param string $path 路径
     * @param File $file 文件
     * @param null|strin|\Closure $rule 文件名规则
     * @param array $options 参数
     * @return bool|string
     */
    public function putFile(string $path, File $file, $rule = null, array $options = [])
    {
        return $this->putFileAs($path, $file, $file->hashName($rule), $options);
    }

    /**
     * Note: 指定文件名保存文件
     * Date: 2023-01-13
     * Time: 17:49
     * @param string $path 路径
     * @param File $file 文件
     * @param null|string|\Closure $rule 文件名规则
     * @param array $options 参数
     * @return bool|string
     */
    public function putFileAs(string $path, File $file, string $name, array $options = [])
    {
        $stream = fopen($file->getRealPath(), 'r');
        $path = trim($path . '/' . $name, '/');

        $result = $this->put($path, $stream, $options);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $result ? $path : false;
    }

    protected function put(string $path, $contents, array $options = [])
    {
        try {
            $this->filesystem->writeStream($path, $contents, $options);
        } catch (UnableToWriteFile | UnableToSetVisibility  $e) {
            return false;
        }
        return true;
    }

    /**
     * Note: 获取文件完整路径
     * Date: 2023-09-21
     * Time: 17:38
     * @param string $path
     * @return string
     */
    public function path(string $path)
    {
        return $path;
    }

    /**
     * Note: 获取文件访问地址
     * Date: 2023-09-21
     * Time: 17:40
     * @param string $path
     * @return string
     * @throws RuntimeException
     */
    public function url(string $path)
    {
        throw new RuntimeException('This driver does not support retrieving URL');
    }

    protected function concatPathToUrl($url, $path)
    {
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }

    public function __call($method, $paramsters)
    {
        return $this->filesystem->$method(...$paramsters);
    }

}