<?php
declare(strict_types=1);

namespace Enna\Framework;

/**
 * 系统服务基础类
 * Class Service
 * @package Enna\Framework
 * @method void register()
 * @method void boot()
 */
abstract class Service
{
    /**
     * 应用实例
     * @var App
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Note: 添加指令
     * Date: 2023-05-11
     * Time: 14:47
     * @param $commands
     */
    protected function commands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        Console::starting(function (Console $console) use ($commands) {
            $console->addCommands($commands);
        });
    }
}