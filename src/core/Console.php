<?php
declare(strict_types=1);

namespace Enna\Framework;

use Closure;
use Enna\Framework\Console\Command;

class Console
{
    /**
     * app实例
     * @var App
     */
    protected $app;

    /**
     * 启动器
     * @var array
     */
    protected static $startCallbacks = [];

    public function __construct(App $app)
    {
        $this->app = $app;

        $this->initialize();

        $this->start();
    }

    protected function initialize()
    {

    }

    protected function start()
    {
        foreach (static::$startCallbacks as $callback) {
            $callback($this);
        }
    }

    /**
     * Note: 添加启动器
     * Date: 2023-03-10
     * Time: 13:47
     * @param Closure $callback 回调函数
     */
    public static function starting(Closure $callback)
    {
        static::$startCallbacks[] = $callback;
    }

    /**
     * Note: 清除启动器
     * Date: 2023-03-10
     * Time: 13:47
     */
    public static function flushStartCallbacks()
    {
        static::$startCallbacks = [];
    }

    /**
     * Note: 添加指令集
     * Date: 2023-03-10
     * Time: 13:48
     * @param array $commands 指令集
     */
    public function addCommands(array $commands)
    {
        foreach ($commands as $key => $command) {
            if (is_subclass_of($command, Command::class)) {
                $this->addCommand($command, is_numeric($key) ? '' : $key);
            }
        }
    }

    /**
     * Note: 添加一个指令
     * Date: 2023-03-10
     * Time: 13:48
     * @param string|Command $command 指令对象或指令类名
     * @param string $name 指令名,留空自动获取
     * @return Command|void
     */
    public function addCommand($command, string $name = '')
    {

    }
}