<?php

namespace Enna\Framework\Console\Output\Descriptor;

use Enna\Framework\Console as FrameworkConsole;

class Console
{
    const GLOBAL_NAMESPACE = '_global';

    /**
     * 指令对象
     * @var FrameworkConsole
     */
    private $console;

    /**
     * "命名空间"符串
     * @var mixed|null
     */
    private $namespace;

    /**
     * @var array
     */
    private $namespaces;

    /**
     * 对象集合
     * @var array
     */
    private $commands;

    /**
     * 对象别名集合
     * @var array
     */
    private $alias;

    public function __construct(FrameworkConsole $console, $namespace = null)
    {
        $this->console = $console;
        $this->namespace = $namespace;
    }

    /**
     * Note: 获取命名空间的指令
     * Date: 2024-01-29
     * Time: 16:54
     * @return array
     */
    public function getNamespaces()
    {
        if ($this->namespaces === null) {
            $this->inspectConsole();
        }

        return $this->namespaces;
    }

    /**
     * Note: 获取指令
     * Date: 2024-01-29
     * Time: 16:55
     * @return array
     */
    public function getCommands()
    {
        if ($this->commands === null) {
            $this->inspectConsole();
        }

        return $this->commands;
    }

    /**
     * Note: 获取指定的指令
     * Date: 2024-01-30
     * Time: 9:33
     * @param string $name
     * @return FrameworkConsole\Command
     * @throws \InvalidArgumentException
     */
    public function getCommand(string $name)
    {
        if (!isset($this->commands[$name]) && !isset($this->alias[$name])) {
            throw new \InvalidArgumentException(sprintf('Command %s does not exist.', $name));
        }

        return $this->commands[$name] ?? $this->alias[$name];
    }

    /**
     * Note: 检查所有指令
     * Date: 2024-01-29
     * Time: 16:54
     */
    private function inspectConsole()
    {
        $this->commands = [];
        $this->namespaces = [];

        $all = $this->console->all($this->namespace ? $this->console->findNamespace($this->namespace) : null);
        foreach ($this->sortCommands($all) as $namespace => $commands) {
            $names = [];

            foreach ($commands as $name => $command) {
                if (is_string($command)) {
                    $command = new $command();
                }

                if (!$command->getName()) {
                    continue;
                }

                if ($command->getName() === $name) {
                    $this->commands[$name] = $command;
                } else {
                    $this->alias[$name] = $command;
                }

                $names[] = $name;
            }

            $this->namespaces[$namespace] = ['id' => $namespace, 'commands' => $names];
        }
    }

    /**
     * Note: 排序所有指令
     * Date: 2024-01-30
     * Time: 9:33
     * @param array $commands
     * @return array
     */
    private function sortCommands(array $commands)
    {
        $namespacedCommands = [];

        foreach ($commands as $name => $command) {
            $key = $this->console->extracNamespaces($name, 1);
            if (!$key) {
                $key = self::GLOBAL_NAMESPACE;
            }
            $namespacedCommands[$key][$name] = $command;
        }
        ksort($namespacedCommands);

        foreach ($namespacedCommands as &$commandSet) {
            ksort($commandSet);
        }

        unset($commandSet);

        return $namespacedCommands;
    }
}