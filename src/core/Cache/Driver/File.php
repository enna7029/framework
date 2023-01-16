<?php
declare(strict_types=1);

namespace Enna\Framework\Cache\Driver;

use Enna\Framework\App;
use Enna\Framework\Cache\Driver;
use Enna\Framework\Pipeline;
use FilesystemIterator;

/**
 * 文件缓存驱动
 * Class File
 * @package Enna\Framework\Cache\Driver
 */
class File extends Driver
{
    protected $options = [
        //缓存有效期 0:永久
        'expire' => 0,
        //缓存子目录
        'cache_subdir' => true,
        //前缀
        'prefix' => '',
        //缓存目录
        'path' => '',
        //hash算法
        'hash_type' => 'md5',
        //数据压缩
        'data_compress' => false,
        //缓存标签前缀
        'tag_prefix' => 'tag:',
        //序列化机制
        'serialize' => []
    ];

    public function __construct(App $app, array $options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if (empty($this->options['path'])) {
            $this->options['path'] = $app->getRuntimePath() . 'cache';
        }
        if (substr($this->options['path'], -1) != DIRECTORY_SEPARATOR) {
            $this->options['path'] .= DIRECTORY_SEPARATOR;
        }
    }

    /**
     * Note: 写入缓存
     * Date: 2022-12-21
     * Time: 18:34
     * @param string $name 缓存变量名
     * @param mixed $value 缓存数据
     * @param int|\DateTime $expire
     * @return bool
     */
    public function set($name, $value, $expire = null)
    {
        $this->writeTimes++;

        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        $expire = $this->getExpireTime($expire);
        $filename = $this->getCacheKey($name);

        $dir = dirname($filename);
        if (!is_dir($dir)) {
            try {
                mkdir($dir, 0755, true);
            } catch (\Exception $e) {

            }
        }

        $data = $this->serialize($value);

        if ($this->options['data_compress'] && function_exists('gzcompress')) {
            $data = gzcompress($data, 3);
        }

        $data = "<?php\n//" . sprintf('%012d', $expire) . "\n exit();?>\n" . $data;
        $result = file_put_contents($filename, $data);

        if ($result) {
            clearstatcache();
            return true;
        }

        return false;
    }

    /**
     * Note: 判断缓存是否存在
     * Date: 2022-12-24
     * Time: 11:17
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has($name)
    {
        return $this->getRaw($name) !== null;
    }

    /**
     * Note: 读取缓存
     * Date: 2022-12-24
     * Time: 17:20
     * @param string $name 缓存变量名
     * @param mixed $default 默认值
     * @return bool|int|mixed|string|null
     */
    public function get($name, $default = null)
    {
        $this->readTimes++;

        $raw = $this->getRaw($name);

        return is_null($raw) ? $default : $this->unserialize($raw['content']);
    }

    /**
     * Note: 自增缓存(针对数值缓存)
     * Date: 2022-12-27
     * Time: 11:10
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int
     */
    public function inc($name, int $step = 1)
    {
        $raw = $this->getRaw($name);
        if ($raw) {
            $value = $this->unserialize($raw['content']) + $step;
            $expire = $raw['expire'];
        } else {
            $value = $step;
            $expire = 0;
        }

        return $this->set($name, $value, $expire) ? $value : false;
    }

    /**
     * Note: 自减缓存(针对数值缓存)
     * Date: 2022-12-27
     * Time: 11:17
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int
     */
    public function dec($name, int $step = 1)
    {
        return $this->inc($name, -$step);
    }

    /**
     * Note: 删除缓存
     * Date: 2022-12-27
     * Time: 11:20
     * @param string $name 缓存变量名
     * @return bool|void
     */
    public function delete($name)
    {
        $this->writeTimes++;

        $filename = $this->getCacheKey($name);
        return $this->unlink($filename);
    }

    /**
     * Note: 清除缓存
     * Date: 2022-12-27
     * Time: 11:34
     * @return bool|void
     */
    public function clear()
    {
        $this->writeTimes++;

        $dirname = $this->options['path'] . $this->options['prefix'];

        $this->rmdir($dirname);

        return true;
    }

    /**
     * Note: 删除缓存标签
     * Date: 2022-12-27
     * Time: 12:02
     * @param array $keys 缓存标签列表
     * @return void
     */
    public function clearTag(array $keys)
    {
        foreach ($keys as $key) {
            $this->unlink($key);
        }
    }

    /**
     * Note: 获取缓存数据
     * Date: 2022-12-24
     * Time: 17:03
     * @param string $name 缓存变量名
     * @return array|null
     */
    protected function getRaw(string $name)
    {
        $filename = $this->getCacheKey($name);

        if (!is_file($filename)) {
            return;
        }

        $content = @file_get_contents($filename);
        if ($content !== false) {
            $expire = substr($content, 8, 12);
            if ($expire != 0 && time() - $expire > filemtime($filename)) {
                $this->unlink($filename);
                return;
            }

            $content = substr($content, 32);

            if ($this->options['data_compress'] && function_exists('gzcompress')) {
                $content = gzuncompress($content);
            }

            return is_string($content) ? ['content' => $content, 'expire' => $expire] : null;
        }
    }

    /**
     * Note: 取得缓存变量的存储文件名
     * Date: 2022-12-24
     * Time: 16:02
     * @param string $name 缓存变量名
     * @return string
     */
    public function getCacheKey(string $name)
    {
        $name = hash($this->options['hash_type'], $name);

        if ($this->options['cache_subdir']) {
            $name = substr($name, 0, 2) . DIRECTORY_SEPARATOR . substr($name, 2);
        }
        if ($this->options['prefix']) {
            $name = $this->options['prefix'] . DIRECTORY_SEPARATOR . $name;
        }

        return $this->options['path'] . $name . '.php';
    }

    /**
     * Note: 删除文件
     * Date: 2022-12-24
     * Time: 17:10
     * @param string $filename 文件地址
     * @return bool
     */
    private function unlink(string $filename)
    {
        try {
            return is_file($filename) && unlink($filename);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Note: 删除文件夹
     * Date: 2022-12-27
     * Time: 11:36
     * @param string $dirname 文件夹地址
     * @return bool
     */
    private function rmdir($dirname)
    {
        if (!is_dir($dirname)) {
            return false;
        }

        $items = new FilesystemIterator($dirname);
        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                $this->rmdir($item->getPathname());
            } else {
                $this->unlink($item->getPathname());
            }
        }

        @rmdir($dirname);

        return true;
    }
}