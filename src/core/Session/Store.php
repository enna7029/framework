<?php

namespace Enna\Framework\Session;

use Enna\Framework\Contract\SessionHandlerInterface;
use Enna\Framework\Helper\Arr;

class Store
{
    /**
     * SESSION名称
     * @var string
     */
    protected $name = 'PHPSESSION';

    /**
     * 驱动器
     * @var SessionHandlerInterface
     */
    protected $handler;

    /**
     * 序列化
     * @var array
     */
    protected $serialize = [];

    /**
     * session id
     * @var string
     */
    protected $session_id;

    /**
     * 是否初始化
     * @var bool
     */
    protected $init = false;

    /**
     * SESSION数据
     * @var array
     */
    protected $data = [];

    public function __construct($name, SessionHandlerInterface $handler, array $serialize = null)
    {
        $this->name = $name;
        $this->handler = $handler;
        if (!is_null($serialize)) {
            $this->serialize = $serialize;
        }

        $this->setSessionId();
    }

    /**
     * Note: 创建session_id
     * Date: 2023-03-01
     * Time: 16:35
     * @param string $session_id session_id
     * @return void
     */
    public function setSessionId($session_id = null)
    {
        $this->session_id = is_string($session_id) && strlen($session_id) === 32 && ctype_alnum($session_id) ? $session_id : md5(microtime(true) . session_create_id());
    }

    /**
     * Note: 获取session_id
     * Date: 2023-03-01
     * Time: 17:23
     * @return string
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

    /**
     * Note: 设置数据
     * Date: 2023-03-07
     * Time: 10:35
     * @param array $data 数据源
     * @return void
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Note: Session初始化
     * Date: 2023-03-01
     * Time: 17:24
     * @return void
     */
    public function init()
    {
        $data = $this->handler->read($this->getSessionId());

        if (!empty($data)) {
            $this->data = array_merge($this->data, $this->unserialize($data));
        }

        $this->init = true;
    }

    /**
     * Note: 设置session名称
     * Date: 2023-03-01
     * Time: 17:31
     * @param string $name session名称
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Note: 获取session名称
     * Date: 2023-03-01
     * Time: 17:32
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Note: session设置
     * Date: 2023-03-01
     * Time: 18:52
     * @param string $name session名称
     * @param mixed $value 值
     * @return void
     */
    public function set(string $name, $value = null)
    {
        Arr::set($this->data, $name, $value);
    }

    /**
     * Note: 判断session是否有值
     * Date: 2023-03-02
     * Time: 11:38
     * @param string $name session名称
     * @return bool
     */
    public function has(string $name)
    {
        return Arr::has($this->data, $name);
    }

    /**
     * Note: 获取session数据
     * Date: 2023-03-02
     * Time: 11:48
     * @param string $name session名称
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        return Arr::get($this->data, $name, $default);
    }

    /**
     * Note: 获取所有session数据
     * Date: 2023-03-02
     * Time: 11:49
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Note: 清空SESSION数据
     * Date: 2023-03-02
     * Time: 14:20
     * @return void
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * Note: 删除session数据
     * Date: 2023-03-02
     * Time: 11:51
     * @param string $name session名称
     * @return void
     */
    public function delete(string $name)
    {
        Arr::delete($this->data, $name);
    }

    /**
     * Note: 获取session数据并删除
     * Date: 2023-03-02
     * Time: 14:22
     * @param string $name session名称
     * @return mixed
     */
    public function pull(string $name)
    {
        return Arr::pull($this->data, $name);
    }

    /**
     * Note: session设置,下一次请求有效
     * Date: 2023-03-07
     * Time: 11:40
     * @param string $name session名称
     * @param mixed $value session值
     * @return void
     */
    public function flash(string $name, $value)
    {
        $this->set($name, $value);
        $this->push('__flash__.__next__', $name);
        $this->set('__flash__.__current__', Arr::except($this->get('__flash__.__current__', []), $name));
    }

    /**
     * Note: 将本次闪存数据推迟到下次请求
     * Date: 2023-08-21
     * Time: 17:31
     * @return void
     */
    public function reflash(): void
    {
        $keys = $this->get('__flash__.__current__', []);
        $values = array_unique(array_merge($this->get('__flash__.__next__', []), $keys));
        $this->set('__flash__.__next__', $values);
        $this->set('__flash__.__current__', []);
    }

    /**
     * Note: 请求当前请求的flash数据
     * Date: 2023-03-07
     * Time: 11:46
     * @return void
     */
    public function clearFlashData()
    {
        Arr::delete($this->data, $this->get('__flash__.__current__', []));
        if (!empty($next = $this->get('__flash__.__next__', []))) {
            $this->set('__flash__.__current__', $next);
        } else {
            $this->delete('__flash__.__current__');
        }

        $this->delete('__flash__.__next__');
    }

    /**
     * Note: 添加数据到一个SESSION数组
     * Date: 2023-03-07
     * Time: 10:14
     * @param string $name session名称
     * @param mixed $value session数据
     * @return void
     */
    public function push(string $name, $value)
    {
        $array = $this->get($name, []);

        $array[] = $value;

        $this->set($name, $array);
    }

    /**
     * Note: 保存SESSION数据
     * Date: 2023-03-02
     * Time: 14:41
     * @return void
     */
    public function save()
    {
        $this->clearFlashData();

        $session_id = $this->getSessionId();

        if (!empty($this->data)) {
            $data = $this->serialize($this->data);

            $this->handler->write($session_id, $data);
        } else {
            $this->handler->delete($session_id);
        }

        $this->init = false;
    }

    /**
     * Note: 销毁SESSION
     * Date: 2023-03-02
     * Time: 14:43
     * @return void
     */
    public function destroy()
    {
        $this->clear();

        $this->regenerate(true);
    }

    /**
     * Note: 重新生成session_id
     * Date: 2023-03-02
     * Time: 14:44
     * @param bool $destory 是否销毁session数据
     * @return void
     */
    public function regenerate(bool $destory = false)
    {
        if ($destory) {
            $this->handler->delete($this->getSessionId());
        }

        $this->setSessionId();
    }

    /**
     * Note: 序列化数据
     * Date: 2023-03-01
     * Time: 17:25
     * @param array $data 数据
     * @return string
     */
    protected function serialize($data)
    {
        $serialize = $this->serialize[0] ?? 'serialize';

        return $serialize($data);
    }

    /**
     * Note: 反序列化数据
     * Date: 2023-03-01
     * Time: 17:27
     * @param string $data 数据
     * @return array
     */
    protected function unserialize($data)
    {
        $unserialize = $this->serialize[1] ?? 'unserialize';

        return (array)$unserialize($data);
    }
}