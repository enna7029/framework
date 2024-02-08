<?php
declare(strict_types=1);

namespace Enna\Framework\Console;

use Enna\Framework\App;
use Enna\Framework\Console;
use Enna\Framework\Console\Input\Definition;
use Enna\Framework\Console\Input\Argument;
use Enna\Framework\Console\Input\Option;
use InvalidArgumentException;
use LogicException;
use Exception;

abstract class Command
{
    /**
     * @var App
     */
    protected $app;

    /**
     * 控制台
     * @var Console
     */
    private $console;

    /**
     * 指令名称
     * @var string
     */
    private $name;

    /**
     * 指令别名
     * @var array
     */
    private $aliases = [];

    /**
     * 输入定义对象
     * @var Definition
     */
    private $definition;

    /**
     * 描述
     * @var string
     */
    private $description;

    /**
     * 获取帮助信息
     * @var string
     */
    private $help;

    /**
     * 简介信息
     * @var array
     */
    private $synopsis = [];

    /**
     * 用法介绍
     * @var array
     */
    private $usages = [];

    /**
     * 是否忽略验证错误
     * @var bool
     */
    private $ignoreValidationErrors = false;

    /**
     * 是否合并指令定义
     * @var bool
     */
    private $consoleDefinitionMerged = false;

    /**
     * 是否合并指令参数定义
     * @var bool
     */
    private $consoleDefinitionMergedWithArgs = false;

    /**
     * 进程标题
     * @var string
     */
    private $processTitle;

    /**
     * 输入对象
     * @var Input
     */
    protected $input;

    /**
     * 输出对象
     * @var Output
     */
    protected $output;

    public function __construct()
    {
        $this->definition = new Definition();

        $this->configure();

        if (!$this->name) {
            throw new LogicException(sprintf('The command defined in "%s" cannot has an empty name.'), get_class($this));
        }
    }

    /**
     * Note: 配置指令
     * Date: 2023-12-11
     * Time: 10:45
     */
    protected function configure()
    {
    }

    /**
     * Note: 执行指令
     * Date: 2024-01-17
     * Time: 16:34
     * @param Input $input
     * @param Output $output
     * @return null|int
     */
    protected function execute(Input $input, Output $output)
    {
        return $this->app->invoke([$this, 'handle']);
    }

    /**
     * Note: 初始化
     * Date: 2024-01-19
     * Time: 14:14
     * @param Input $input
     * @param Output $output
     */
    protected function initialize(Input $input, Output $output)
    {
    }

    /**
     * Note: 用户验证
     * Date: 2024-01-19
     * Time: 14:18
     * @param Input $input
     * @param Output $output
     */
    protected function interact(Input $input, Output $output)
    {
    }

    /**
     * Note: 忽略验证错误
     * Date: 2024-01-17
     * Time: 11:41
     */
    public function ignoreValidationErrors()
    {
        $this->ignoreValidationErrors = true;
    }

    /**
     * Note: 设置app
     * Date: 2023-12-09
     * Time: 11:57
     * @param App $app
     */
    public function setApp(App $app)
    {
        $this->app = $app;
    }

    /**
     * Note: 获取app
     * Date: 2023-12-09
     * Time: 11:58
     * @return App
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Note: 设置控制台
     * Date: 2023-12-09
     * Time: 11:53
     * @param Console|null $console
     */
    public function setConsole(Console $console = null)
    {
        $this->console = $console;
    }

    /**
     * Note: 获取控制台
     * Date: 2023-12-09
     * Time: 11:56
     * @return Console
     */
    public function getConsole()
    {
        return $this->console;
    }

    /**
     * Note: 设置该指令是否可用
     * Date: 2023-12-09
     * Time: 11:55
     * @return bool
     */
    public function setEnalbed()
    {
        return true;
    }

    /**
     * Note: 设置指令名称
     * Date: 2023-12-11
     * Time: 11:05
     * @param string $name
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setName(string $name)
    {
        $this->validateName($name);

        $this->name = $name;

        return $this;
    }

    /**
     * Note: 获取指定名称
     * Date: 2023-12-11
     * Time: 10:58
     * @return string
     */
    public function getName()
    {
        return $this->name ?: '';
    }

    /**
     * Note: 设置描述
     * Date: 2024-01-17
     * Time: 16:19
     * @param string $description 描述
     * @return Command
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Note: 获取描述
     * Date: 2024-01-17
     * Time: 16:19
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Note: 设置帮助信息
     * Date: 2024-01-17
     * Time: 16:21
     * @param string $help
     * @return Command
     */
    public function setHelp(string $help)
    {
        $this->help = $help;

        return $this;
    }

    /**
     * Note: 获取帮助信息
     * Date: 2024-01-17
     * Time: 16:22
     * @return string
     */
    public function getHelp()
    {
        return $this->help ?: '';
    }

    /**
     * Note: 设置别名
     * Date: 2023-12-11
     * Time: 11:08
     * @param iterable $aliases
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setAliases(iterable $aliases)
    {
        foreach ($aliases as $alias) {
            $this->validateName($alias);
        }

        $this->aliases = $aliases;

        return $this;
    }

    /**
     * Note: 获取别名
     * Date: 2023-12-11
     * Time: 11:06
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Note: 验证指令名称
     * Date: 2023-12-11
     * Time: 11:04
     * @param string $name
     * @throws InvalidArgumentException
     */
    private function validateName(string $name)
    {
        if (!preg_match('/^[^\:]++(\:[^\:]++)*$/', $name)) {
            throw new InvalidArgumentException(sprintf('Command name "%s" is invalid.', $name));
        }
    }

    /**
     * Note: 执行
     * Date: 2023-12-21
     * Time: 9:31
     * @param Input $input
     * @param Output $output
     * @return int
     * @throws Exception
     */
    public function run(Input $input, Output $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->getSynopsis(true);
        $this->getSynopsis(false);

        $this->mergeConsoleDefinition();

        try {
            $input->bind($this->definition);
        } catch (\Exception $e) {
            if (!$this->ignoreValidationErrors) {
                throw $e;
            }
        }

        $this->initialize($input, $output);

        if ($this->processTitle !== null) {
            if (function_exists('cli_set_process_title')) {

            } elseif (function_exists('setproctitle')) {

            } elseif (Output::VERBOSITY_VERY_VERBOSE === $output->getVerbosity()) {
                $output->writeln('<comment>Install the proctitle PECL to be able to change the process title.</comment>');
            }
        }

        if ($input->isInteractive()) {
            $this->interact($input, $output);
        }

        $input->validate();

        $statusCode = $this->execute($input, $output);

        return is_numeric($statusCode) ? (int)$statusCode : 0;
    }

    /**
     * Note: 获取简介
     * Date: 2024-01-17
     * Time: 17:48
     * @param bool $short 是否简短的
     * @return string
     */
    public function getSynopsis(bool $short = false)
    {
        $key = $short ? 'short' : 'long';

        if (!isset($this->synopsis[$key])) {
            $this->synopsis[$key] = trim(sprintf('%s %s', $this->name, $this->definition->getSynopsis($short)));
        }

        return $this->synopsis[$key];
    }

    /**
     * Note: 合并参数定义
     * Date: 2023-12-29
     * Time: 17:05
     * @param bool $mergeArgs 合并参数
     * @return void
     */
    public function mergeConsoleDefinition(bool $mergeArgs = true)
    {
        if ($this->console === null || $this->consoleDefinitionMerged === true && ($this->consoleDefinitionMergedWithArgs || !$mergeArgs)) {
            return;
        }

        if ($mergeArgs) {
            $currentArguments = $this->definition->getArguments();
            $this->definition->setArguments($this->console->getDefinition()->getArguments());
            $this->definition->addArguments($currentArguments);
        }

        $this->definition->addOptions($this->console->getDefinition()->getOptions());

        $this->consoleDefinitionMerged = true;

        if ($mergeArgs) {
            $this->consoleDefinitionMergedWithArgs = true;
        }
    }

    /**
     * Note: 设置参数定义
     * Date: 2023-12-11
     * Time: 10:57
     * @param array|Definition $definitaion
     * @return Command
     */
    public function setDefinition($definitaion)
    {
        if ($definitaion instanceof Definition) {
            $this->definition = $definitaion;
        } else {
            $this->definition->setDefinition($definitaion);
        }

        $this->consoleDefinitionMerged = false;

        return $this;
    }

    /**
     * Note: 获取参数定义
     * Date: 2023-12-09
     * Time: 12:02
     * @return Definition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Note: 获取当前指令的参数定义
     * Date: 2024-01-19
     * Time: 14:47
     * @return Definition
     */
    public function getNativeDefinition()
    {
        return $this->getDefinition();
    }

    /**
     * Note: 添加参数
     * Date: 2024-01-19
     * Time: 14:54
     * @param string $name 参数名称
     * @param int $mode 类型
     * @param string $description 描述
     * @param mixed $default 默认值
     * @return $this
     */
    public function addArgument(string $name, int $mode = null, string $description = '', $default = null)
    {
        $this->definition->addArgument(new Argument($name, $mode, $description, $default));

        return $this;
    }

    /**
     * Note: 添加选项
     * Date: 2024-01-19
     * Time: 14:57
     * @param string $name 选项名称
     * @param string $shortcut 别名
     * @param int $mode 类型
     * @param string $description 描述
     * @param mixed $default 默认值
     * @return $this
     */
    public function addOption(string $name, string $shortcut = null, int $mode = null, string $description = '', $default = null)
    {
        $this->definition->addOption(new Option($name, $shortcut, $mode, $description, $default));

        return $this;
    }

    /**
     * Note: 设置进程名称
     *
     * PHP 5.5+ or the proctitle PECL library is required
     *
     * Date: 2024-01-19
     * Time: 15:01
     * @param string $title 进程名称
     * @return $this
     */
    public function setProcessTitle($title)
    {
        $this->processTitle = $title;

        return $this;
    }

    /**
     * Note: 描述信息
     * Date: 2024-01-19
     * Time: 15:02
     */
    public function getProcessedHelp()
    {
        $name = $this->name;

        $placeholders = [
            '%command.name%',
            '%command.full_name%'
        ];

        $replacements = [
            $name,
            $_SERVER['PHP_SELF'] . ' ' . $name
        ];

        return str_replace($placeholders, $replacements, $this->getHelp());
    }

    /**
     * Note: 添加用法介绍
     * Date: 2024-01-19
     * Time: 15:41
     * @param string $usage
     * @return $this
     */
    public function addUsage(string $usage)
    {
        if (strpos($usage, $this->name) !== 0) {
            $usage = sprintf('%s %s', $this->name, $usage);
        }

        $this->usages[] = $usage;

        return $this;
    }

    /**
     * Note: 获取用法介绍
     * Date: 2024-01-19
     * Time: 15:43
     * @return array
     */
    public function getUsages()
    {
        return $this->usages;
    }
}