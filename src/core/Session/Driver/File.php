<?php
declare(strict_types=1);

namespace Enna\Framework\Session\Driver;

use Enna\Framework\Contract\SessionHandlerInterface;
use Enna\Framework\App;
use SplFileInfo;
use FilesystemIterator;
use Generator;
use Closure;
use Exception;

class File implements SessionHandlerInterface
{
    protected $config = [
        'path' => '',
        'expire' => 1440,
        'prefix' => '',
        'data_compress' => false,
    ];

    public function __construct(App $app, array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        if (empty($this->config['path'])) {
            $this->config['path'] = $app->getRunTimePath() . 'session' . DIRECTORY_SEPARATOR;
        }
        if (substr($this->config['path'], -1) != DIRECTORY_SEPARATOR) {
            $this->config['path'] .= DIRECTORY_SEPARATOR;
        }

        $this->init();
    }

    /**
     * Note: 初始化session目录:创建或清除过期数据
     * Date: 2023-03-07
     * Time: 14:13
     */
    protected function init()
    {
        try {
            if (!is_dir($this->config['path'])) {
                mkdir($this->config['path'], 0755, true);
            }
        } catch (Exception $e) {

        }

        if (random_int(1, 100) <= 1) {
            $this->clearFiles();
        }
    }

    /**
     * Note: 清除过期的文件
     * Date: 2023-03-07
     * Time: 15:33
     * @return void
     */
    protected function clearFiles()
    {
        $expire = $this->config['expire'];
        $now = time();

        $files = $this->findFiles($this->config['path'], function (SplFileInfo $item) use ($expire, $now) {
            return $now - $expire > $item->getMTime();
        });

        foreach ($files as $file) {
            $this->unlink($file->getPathname());
        }
    }

    /**
     * Note: 查找文件
     * Date: 2023-03-07
     * Time: 15:39
     * @param string $path 文件路径
     * @param Closure $filter 过滤闭包
     * @return Generator
     */
    protected function findFiles(string $path, Closure $filter)
    {
        $files = new FilesystemIterator($path);

        foreach ($files as $file) {
            if ($file->isDir() && !$file->isLink()) {
                yield from $this->findFiles($file->getPathname(), $filter);
            } else {
                if ($filter($file)) {
                    yield $file;
                }
            }
        }
    }

    /**
     * Note: 删除文件
     * Date: 2023-03-07
     * Time: 15:38
     * @param string $file 文件
     * @return bool
     */
    protected function unlink(string $file)
    {
        return is_file($file) && unlink($file);
    }

    /**
     * Note: 取得变量的存储文件名
     * Date: 2023-03-07
     * Time: 15:44
     * @param string $name
     * @param bool $auto
     */
    protected function getFileName(string $name, bool $auto = false)
    {
        if ($this->config['prefix']) {
            $name = $this->config['prefix'] . DIRECTORY_SEPARATOR . 'session_' . $name;
        } else {
            $name = 'session_' . $name;
        }

        $filename = $this->config['path'] . $name;
        $dir = dirname($filename);

        if ($auto && !is_dir($dir)) {
            try {
                mkdir($dir, 0755, true);
            } catch (Exception $e) {

            }
        }

        return $filename;
    }

    /**
     * Note: 读取文件内容(加锁)
     * Date: 2023-03-07
     * Time: 16:26
     * @param string $path 文件
     * @return string
     */
    protected function readFile($path)
    {
        $content = '';
        $handle = fopen($path, 'rb');
        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $content = fread($handle, filesize($path));

                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $content;
    }

    /**
     * Note: 将数据写入到文件中(加锁)
     * Date: 2023-03-07
     * Time: 15:49
     * @param string $path 文件
     * @param mixed $content 要写入的数据
     * @return bool
     */
    protected function writeFile(string $path, $content)
    {
        return (bool)file_put_contents($path, $content, LOCK_EX);
    }

    /**
     * Note: 读取session
     * Date: 2023-03-07
     * Time: 16:21
     * @param string $sessionId session_id
     * @return string
     */
    public function read(string $sessionId): string
    {
        $filename = $this->getFileName($sessionId);

        if (is_file($filename) && filemtime($filename) >= time() - $this->config['expire']) {
            $content = $this->readFile($filename);

            if ($this->config['data_compress'] && function_exists('gzuncompress')) {
                $content = gzuncompress($content);
            }

            return $content;
        }

        return '';
    }

    /**
     * Note: 删除session
     * Date: 2023-03-07
     * Time: 16:33
     * @param string $sessionId
     * @return bool
     */
    public function delete(string $sessionId): bool
    {
        try {
            return $this->unlink($this->getFileName($sessionId));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Note: 写入session
     * Date: 2023-03-07
     * Time: 16:20
     * @param string $sessionId session_id
     * @param string $data session数据
     * @return bool
     */
    public function write(string $sessionId, string $data): bool
    {
        $filename = $this->getFileName($sessionId, true);

        if ($this->config['data_compress'] && function_exists('gzcompress')) {
            $data = gzcompress($data, 3);
        }

        return $this->writeFile($filename, $data);
    }
}