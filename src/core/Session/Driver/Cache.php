<?php
declare(strict_types=1);

namespace Enna\Framework\Session\Driver;

use Enna\Framework\Contract\SessionHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Enna\Framework\Cache as CacheDriver;

class Cache implements SessionHandlerInterface
{
    /**
     * 处理器
     * @var CacheInterface
     */
    protected $handler;

    /**
     * session过期时间
     * @var int
     */
    protected $expire;

    /**
     * session前缀
     * @var string
     */
    protected $prefix;

    public function __construct(CacheDriver $cache, array $config = [])
    {
        $driver = $config['store'] ?? 'file';
        $this->handler = $cache->store($driver);
        $this->expire = $config['expire'] ?: 1440;
        $this->prefix = $config['prefix'] ?: '';
    }

    /**
     * Note: 读取session
     * Date: 2023-03-09
     * Time: 10:48
     * @param string $sessionId 读取SESSION
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function read(string $sessionId): string
    {
        return (string)$this->handler->get($this->prefix . $sessionId);
    }

    /**
     * Note: 写入session
     * Date: 2023-03-09
     * Time: 10:48
     * @param string $sessionId
     * @param string $data
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function write(string $sessionId, string $data): bool
    {
        return $this->handler->set($this->prefix . $sessionId, $data, $this->prefix);
    }

    /**
     * Note: 删除session
     * Date: 2023-03-09
     * Time: 10:48
     * @param string $sessionId
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete(string $sessionId): bool
    {
        return $this->handler->delete($this->prefix, $sessionId);
    }

}