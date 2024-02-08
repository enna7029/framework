<?php
declare(strict_types=1);

namespace Enna\Framework;

use Closure;
use LogicException;
use InvalidArgumentException;
use Enna\Framework\Console\Command;
use Enna\Framework\Console\Input\Definition as InputDefinition;
use Enna\Framework\Console\Input\Argument as InputArgument;
use Enna\Framework\Console\Input\Option as InputOption;
use Enna\Framework\Console\Input;
use Enna\Framework\Console\Output;
use Enna\Framework\Console\Command\Lists;
use Enna\Framework\Console\Command\Help;
use Enna\Framework\Console\Command\Version;
use Enna\Framework\Console\Command\RunServer;
use Enna\Framework\Console\Command\Make\Controller;
use Enna\Framework\Console\Command\Make\Command as MakeCommand;
use Enna\Framework\Console\Command\Make\Event;
use Enna\Framework\Console\Command\Make\Listener;
use Enna\Framework\Console\Command\Make\Subscribe;
use Enna\Framework\Console\Command\Make\Middleware;
use Enna\Framework\Console\Command\Make\Model;
use Enna\Framework\Console\Command\Make\Validate;
use Enna\Framework\Console\Command\Make\Service;
use Enna\Framework\Console\Command\Clear;
use Enna\Framework\Console\Command\Optimize\Schema;
use Enna\Framework\Console\Command\Optimize\Route;
use Enna\Framework\Console\Command\ServiceDiscover;
use Enna\Framework\Console\Command\VendorPublish;
use Enna\Framework\Console\Command\RouteList;

/**
 * 控制台应用管理类
 * Class Console
 * @package Enna\Framework
 */
class Console
{
    /**
     * app实例
     * @var App
     */
    protected $app;

    /**
     * 注册的指令
     * @var Command[]
     */
    protected $commands = [];

    /**
     * 默认指令
     * @var string
     */
    protected $defaultCommand = 'list';

    /**
     * 默认指令行
     * @var array
     */
    protected $defaultCommands = [
        'help' => Help::class,
        'list' => Lists::class,
        'version' => Version::class,
        'run' => RunServer::class,
        'clear' => Clear::class,
        'make:controller' => Controller::class,
        'make:command' => MakeCommand::class,
        'make:event' => Event::class,
        'make:listener' => Listener::class,
        'make:subscribe' => Subscribe::class,
        'make:middleware' => Middleware::class,
        'make:model' => Model::class,
        'make:validate' => Validate::class,
        'make:service' => Service::class,
        'optimize:schema' => Schema::class,
        'optimize:route' => Route::class,
        'service:discover' => ServiceDiscover::class,
        'vendor:publish' => VendorPublish::class,
        'route:list' => RouteList::class,
    ];

    /**
     * 默认定义
     * @var mixed
     */
    protected $definition;

    /**
     * 查看帮助
     * @var bool
     */
    protected $wantHelps = false;

    /**
     * 自动退出
     * @var bool
     */
    protected $autoExit = true;

    /**
     * 是否捕获异常
     * @var bool
     */
    protected $catchExceptions = true;

    /**
     * 注册器
     * @var array
     */
    protected static $startCallbacks = [];

    public function __construct(App $app)
    {
        $this->app = $app;

        //初始化
        $this->initialize();

        //获取默认输入定义
        $this->definition = $this->getDefaultInputDefinition();

        //加载指令
        $this->loadCommands();

        //启动 注册器
        $this->start();
    }

    /**
     * Note: 初始化
     * Date: 2023-12-07
     * Time: 18:23
     */
    protected function initialize()
    {
        if (!$this->app->initialized()) {
            $this->app->initialize();
        }

        $this->makeRequest();
    }

    protected function makeRequest()
    {
        $url = $this->app->config->get('app.url', 'http://localhost');

        $components = parse_url($url);

        $server = $_SERVER;

        if (isset($components['path'])) {
            $server = array_merge($server, [
                'SCRIPT_FILENAME' => $components['path'],
                'SCRIPT_NAME' => $components['path'],
                'REQUEST_URI' => $components['path'],
            ]);
        }

        if (isset($components['host'])) {
            $server['SERVER_NAME'] = $components['host'];
            $server['HTTP_HOST'] = $components['host'];
        }

        if (isset($components['scheme'])) {
            if ('https' === $components['scheme']) {
                $server['HTTPS'] = 'on';
                $server['SERVER_PORT'] = 443;
            } else {
                unset($server['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }

        if (isset($components['port'])) {
            $server['SERVER_PORT'] = $components['port'];
            $server['HTTP_HOST'] .= ':' . $components['port'];
        }

        /**
         * @var Request $request
         */
        $request = $this->app->make('request');

        $request->withServer($server);
    }

    /**
     * Note: 启动 注册器
     * Date: 2023-12-11
     * Time: 11:12
     */
    protected function start()
    {
        foreach (static::$startCallbacks as $callback) {
            $callback($this);
        }
    }

    /**
     * Note: 添加 注册器
     * Date: 2023-03-10
     * Time: 13:47
     * @param Closure $callback 回调函数
     */
    public static function starting(Closure $callback)
    {
        static::$startCallbacks[] = $callback;
    }

    /**
     * Note: 清除 注册器
     * Date: 2023-03-10
     * Time: 13:47
     */
    public static function flushStartCallbacks()
    {
        static::$startCallbacks = [];
    }

    /**
     * Note: 设置执行用户
     * Date: 2023-12-11
     * Time: 11:27
     * @param string $user
     */
    public static function setUser(string $user)
    {
        if (extension_loaded('posix')) {
            $user = posix_getpwnam($user);

            if (!empty($user)) {
                posix_setgid($user['gid']);
                posix_setuid($user['uid']);
            }
        }
    }

    /**
     * Note: 获取默认输入定义
     * Date: 2023-12-08
     * Time: 14:04
     * @return InputDefinition
     */
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),
            new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
            new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this console message'),
            new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message'),
            new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
            new InputOption('--ansi', '', InputOption::VALUE_NONE, 'Force ANSI output'),
            new InputOption('--no-ansi', '', InputOption::VALUE_NONE, 'Disable ANSI output'),
            new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
        ]);
    }

    /**
     * Note: 加载指令
     * Date: 2023-12-09
     * Time: 11:42
     */
    protected function loadCommands()
    {
        $commands = $this->app->config->get('console.commands', []);
        $commands = array_merge($this->defaultCommands, $commands);

        $this->addCommands($commands);
    }

    /**
     * Note: 调用指令
     * Date: 2023-12-11
     * Time: 11:29
     * @param string $command 指令
     * @param array $parameters 参数
     * @param string $driver 输出驱动
     * @return
     */
    public function call(string $command, array $parameters = [], string $driver = 'buffer')
    {
        array_unshift($parameters, $command);

        $input = new Input($parameters);
        $output = new Output($driver);

        $this->setCatchExceptions(false);
        $this->find($command)->run($input, $output);

        return $output;
    }

    /**
     * Note: 执行当前的指令
     * Date: 2023-12-11
     * Time: 11:24
     * @return int
     * @throws \Exception
     */
    public function run()
    {
        $input = new Input();
        $output = new Output();

        $this->configureIO($input, $output);

        try {
            $exitCode = $this->doRun($input, $output);
        } catch (\Exception $e) {
            if (!$this->catchExceptions) {
                throw $e;
            }
            $output->renderException($e);

            $exitCode = $e->getCode();
            if (is_numeric($exitCode)) {
                $exitCode = (int)$exitCode;
                if ($exitCode === 0) {
                    $exitCode = 1;
                }
            } else {
                $exitCode = 1;
            }
        }

        if ($this->autoExit) {
            if ($exitCode > 254) {
                $exitCode = 254;
            }

            exit($exitCode);
        }

        return $exitCode;
    }

    /**
     * Note: 指定指令
     * Date: 2023-12-22
     * Time: 11:05
     * @param Input $input
     * @param Output $output
     * @return int
     */
    public function doRun(Input $input, Output $output)
    {
        if ($input->hasParameterOption(['--version', '-V']) === true) {
            $output->writeln($this->getLongVersion());

            return 0;
        }

        $name = $this->getCommandName($input);

        if ($input->hasParameterOption(['--help', '-h']) === true) {
            if (!$name) {
                $name = 'help';
                $input = new Input(['help']);
            } else {
                $this->wantHelps = false;
            }
        }

        if (!$name) {
            $name = $this->defaultCommand;
            $input = new Input([$name]);
        }

        $command = $this->find($name);

        return $this->doRunCommand($command, $input, $output);
    }

    /**
     * Note: 设置输入参数定义
     * Date: 2023-12-22
     * Time: 11:07
     * @param InputDefinition $definition
     */
    public function setDefinition(InputDefinition $definition)
    {
        $this->definition = $definition;
    }

    /**
     * Note: 获取输入参数定义
     * Date: 2023-12-22
     * Time: 11:07
     * @return InputDefinition|mixed
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Note: 获取帮助信息
     * Date: 2023-12-22
     * Time: 11:34
     * @return string
     */
    public function getHelp()
    {
        return $this->getLongVersion();
    }

    /**
     * Note: 获取完整的版本号
     * Date: 2023-12-22
     * Time: 11:31
     * @return string
     */
    public function getLongVersion()
    {
        if ($this->app->version()) {
            return sprintf('version <comment>%s</comment>', $this->app->version());
        }

        return '<info>Console Tool</info>';
    }

    /**
     * Note: 设置是否铺货异常
     * Date: 2023-12-11
     * Time: 12:02
     * @param bool $boolean
     */
    public function setCatchExceptions(bool $boolean)
    {
        $this->catchExceptions = $boolean;
    }

    /**
     * Note: 是否自动退出
     * Date: 2023-12-22
     * Time: 11:39
     * @param bool $boolean
     * @api
     */
    public function setAutoExit(bool $boolean)
    {
        $this->autoExit = $boolean;
    }

    /**
     * Note: 获取所有的指令
     * Date: 2023-12-22
     * Time: 14:49
     * @param string $namesapce 命名空间
     * @return Command[]
     * @api
     */
    public function all(string $namesapce = null)
    {
        if ($namesapce === null) {
            return $this->commands;
        }

        $commands = [];
        foreach ($this->commands as $name => $command) {
            if ($this->extracNamespaces($name, substr_count($namesapce, ':') + 1) === $namesapce) {
                $commands[$name] = $command;
            }
        }

        return $commands;
    }

    /**
     * Note: 查找指令
     * Date: 2023-12-11
     * Time: 12:03
     * @param string $name 指令名称或别名
     * @return Command
     * @throws InvalidArgumentException
     */
    public function find(string $name)
    {
        $allCommands = array_keys($this->commands);

        $expr = preg_replace_callback('{([^:]+|)}', function ($matches) {
            return preg_quote($matches[1]) . '[^:]*';
        }, $name);

        $commands = preg_grep('{^' . $expr . '}', $allCommands);

        if (empty($commands) || count(preg_grep('{^' . $expr . '$}', $commands)) < 1) {
            if ($pos = strpos($name, ':') !== false) {
                $this->findNamespace(substr($name, 0, $pos));
            }

            $message = sprintf('Command "%s" is not defined', $name);

            if ($alternatives = $this->findAlternatives($name, $allCommands)) {
                if (count($alternatives) == 1) {
                    $message .= "\n\nDid you mean this?\n   ";
                } else {
                    $message .= "\n\n Did you mean one of these?\n  ";
                }

                $message .= implode("\n    ", $alternatives);
            }

            throw new InvalidArgumentException($message);
        }

        $exact = in_array($name, $commands, true);
        if (count($commands) > 1 && !$exact) {
            $suggestions = $this->getAbbreviationSuggestions(array_values($commands));

            throw new InvalidArgumentException(sprintf('Command "%s" is ambiguous (%s).', $name, $suggestions));
        }

        return $this->getCommand($exact ? $name : reset($commands));
    }

    /**
     * Note: 查找注册命名空间中名称或缩写
     * Date: 2023-12-14
     * Time: 18:22
     * @param string $namespace
     * @return string
     * @throws InvalidArgumentException
     */
    public function findNamespace(string $namespace)
    {
        $allNamespaces = $this->getNamespaces();

        $expr = preg_replace_callback('{([^:]+|)}', function ($matches) {
            return preg_quote($matches[1]) . '[^:]*';
        }, $namespace);
        $namespaces = preg_grep('{^' . $expr . '}', $allNamespaces);

        if (empty($namespaces)) {
            $message = sprintf('There are no command defined in the "%s" namespace.', $namespace);

            if ($alternatives = $this->findAlternatives($namespace, $allNamespaces)) {
                if (count($alternatives) == 1) {
                    $message .= "\n\nDid you mean this?\n   ";
                } else {
                    $message .= "\n\n Did you mean one of these?\n  ";
                }

                $message .= implode("\n    ", $alternatives);
            }

            throw new InvalidArgumentException($message);
        }

        $exact = in_array($namespace, $namespaces, true);
        if (count($namespaces) > 1 && !$exact) {
            throw new InvalidArgumentException(sprintf('The namespace "%s" is ambiguous (%s).', $namespace, $this->getAbbreviationSuggestions(array_values($namespaces))));
        }

        return $exact ? $namespace : reset($namespaces);
    }

    /**
     * Note: 查找可以替代的建议
     * Date: 2023-12-19
     * Time: 10:04
     * @param string $name
     * @param array $collection
     * return array
     */
    private function findAlternatives(string $name, $collection)
    {
        $threshold = 1e3;
        $alternatives = [];

        $collectionParts = [];
        foreach ($collection as $item) {
            $collectionParts[$item] = explode(':', $item);
        }

        foreach (explode(':', $name) as $i => $subname) {
            foreach ($collectionParts as $collectionName => $parts) {
                $exists = isset($alternatives[$collectionName]);
                if (!isset($parts[$i]) && $exists) {
                    $alternatives[$collectionName] += $threshold;
                    continue;
                } elseif (!isset($parts[$i])) {
                    continue;
                }

                $lev = levenshtein($subname, $parts[$i]);
                if ($lev <= strlen($subname) / 3 || $subname != '' && strpos($parts[$i], $subname) !== false) {
                    $alternatives[$collectionName] = $exists ? $alternatives[$collectionName] + $lev : $lev;
                } elseif ($exists) {
                    $alternatives[$collectionName] += $threshold;
                }
            }
        }

        foreach ($collection as $item) {
            $lev = levenshtein($name, $item);
            if ($lev <= strlen($name) / 3 || false !== strpos($item, $name)) {
                $alternatives[$item] = isset($alternatives[$item]) ? $alternatives[$item] - $lev : $lev;
            }
        }

        $alternatives = array_filter($alternatives, function ($lev) use ($threshold) {
            return $lev < 2 * $threshold;
        });
        asort($alternatives);

        return array_keys($alternatives);
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
        if ($name) {
            $this->commands[$name] = $command;
            return;
        }

        if (is_string($command)) {
            $command = $this->app->invokeClass($command);
        }

        $command->setConsole($this);

        if (!$command->setEnalbed()) {
            $command->setConsole(null);
            return;
        }

        $command->setApp($this->app);

        if ($command->getDefinition() === null) {
            throw new LogicException(sprintf('Command class "%s" is not correctly initialized. You probably forgot to call the parent constructor.', get_class($command)));
        }

        $this->commands[$command->getName()] = $command;

        foreach ($command->getAliases() as $alias) {
            $this->commands[$alias] = $command;
        }

        return $command;
    }

    /**
     * Note: 获取指令
     * Date: 2023-12-14
     * Time: 18:00
     * @param string $name 指令名称
     * @return Command
     * @throws InvalidArgumentException
     */
    public function getCommand(string $name)
    {
        if (!isset($this->commands[$name])) {
            throw new InvalidArgumentException(sprintf('The command "%s" does not exists.', $name));
        }

        $command = $this->commands[$name];

        if (is_string($command)) {
            $command = $this->app->invokeClass($command);
            /**
             * @var Command $command
             */
            $command->setConsole($this);
            $command->setApp($this->app);
        }

        if ($this->wantHelps) {
            $this->wantHelps = false;

            /**
             * @var Help $helpCommand
             */
            $helpCommand = $this->getCommand('help');
            $helpCommand->setCommand($command);

            return $helpCommand;
        }

        return $command;
    }

    /**
     * Note: 某个指令是否存在
     * Date: 2023-12-22
     * Time: 11:41
     * @param string $name 指令名称
     * @return bool
     */
    public function hasCommand(string $name)
    {
        return isset($this->commands[$name]);
    }

    /**
     * Note: 获取可能的建议
     * Date: 2023-12-14
     * Time: 17:35
     * @param array $abbrevs
     * @return string
     */
    private function getAbbreviationSuggestions(array $abbrevs)
    {
        return sprintf('%s, %s%s', $abbrevs[0] . $abbrevs[1] . count($abbrevs) > 2 ? sprintf(' and %d more', count($abbrevs) - 2) : '');
    }

    /**
     * Note: 获取所有命名空间
     * Date: 2023-12-19
     * Time: 9:34
     * @return array
     */
    public function getNamespaces()
    {
        $namesapces = [];

        foreach ($this->commands as $key => $command) {
            if (is_string($command)) {
                $namesapces = array_merge($namesapces, $this->extractAllNamespaces($key));
            } else {
                $namespaces = array_merge($namespaces, $this->extractAllNamespaces($command->getName()));

                foreach ($command->getAliases() as $alias) {
                    $namesapces = array_merge($namesapces, $this->extractAllNamespaces($alias));
                }
            }
        }

        return array_values(array_unique(array_filter($namesapces)));
    }

    /**
     * Note: 返回所有的命名空间
     * Date: 2023-12-19
     * Time: 9:34
     * @param string $name
     * @return array
     */
    private function extractAllNamespaces(string $name)
    {
        $parts = explode(':', $name, -1);
        $namespaces = [];

        foreach ($parts as $part) {
            if (count($namespaces)) {
                $namespaces[] = end($namespaces) . ':' . $part;
            } else {
                $namespaces[] = $part;
            }
        }

        return $namespaces;
    }

    /**
     * Note: 返回命名空间部分
     * Date: 2023-12-22
     * Time: 14:58
     * @param string $name 指令
     * @param int $limit 命名空间的层级
     * @return string
     */
    public function extracNamespaces(string $name, int $limit = 0)
    {
        $parts = explode(':', $name);
        array_pop($parts);

        return implode(':', $limit === 0 ? $parts : array_slice($parts, 0, $limit));
    }

    /**
     * Note: 配置基于用户的参数和选项的输入和输出实例
     * Date: 2023-12-21
     * Time: 10:21
     * @param Input $input 输入实例
     * @param Output $output 输出实例
     * @return void
     */
    protected function configureIO(Input $input, Output $output)
    {
        if ($input->hasParameterOption(['--ansi']) === true) {
            $output->setDecorated(true);
        } elseif ($input->hasParameterOption(['--no-asin'])) {
            $output->setDecorated(false);
        }

        if ($input->hasParameterOption(['--no-interaction', 'n'])) {
            $input->setInteractive(false);
        }

        if ($input->hasParameterOption(['--quiet', '-q'])) {
            $output->setVerbosity(Output::VERBOSITY_QUIET);
        } elseif ($input->hasParameterOption('--vvv') || $input->hasParameterOption('--verbose=3') || $input->getParameterOption('--verbose') == 3) {
            $output->setVerbosity(Output::VERBOSITY_DEBUG);
        } elseif ($input->hasParameterOption('--vv') || $input->hasParameterOption('--verbose=2') || $input->getParameterOption('--verbose') == 2) {
            $output->setVerbosity(Output::VERBOSITY_VERY_VERBOSITY);
        } elseif ($input->hasParameterOption('--v') || $input->hasParameterOption('--verbose=1') || $input->getParameterOption('--verbose') == 1) {
            $output->setVerbosity(Output::VERBOSITY_VERBOSE);
        }
    }

    /**
     * Note: 获取指令的基础名称
     * Date: 2023-12-22
     * Time: 16:42
     * @param Input $input
     * @return string
     */
    public function getCommandName(Input $input)
    {
        return $input->getFirstArgument() ?: '';
    }

    /**
     * Note: 执行指令
     * Date: 2023-12-22
     * Time: 16:51
     * @param Command $command 指令实例
     * @param Input $input 输入实例
     * @param Output $output 输出实例
     * @return int
     * @throws \Exception
     */
    protected function doRunCommand(Command $command, Input $input, Output $output)
    {
        return $command->run($input, $output);
    }

}