<?php
declare(strict_types=1);


namespace Enna\Framework\Console;

use Enna\Framework\Console\Input\Argument;
use Enna\Framework\Console\Input\Definition;
use Enna\Framework\Console\Input\Option;


class Input
{
    /**
     * 参数
     * @var mixed|null
     */
    private $tokens;

    /**
     * 解析后的参数
     * @var mixed
     */
    private $parsed;

    /**
     * 输入定义
     * @var Definition
     */
    protected $definition;

    /**
     * 参数定义
     * @var Argument[]
     */
    protected $arguments = [];

    /**
     * 选项定义
     * @var Option[]
     */
    protected $options = [];

    /**
     * 是否交互
     * @var bool
     */
    protected $interactive = true;

    public function __construct($argv = null)
    {
        if ($argv === null) {
            $argv = $_SERVER['argv'];
            array_shift($argv);
        }

        $this->tokens = $argv;

        $this->definition = new Definition();
    }

    /**
     * Note: 设置参数
     * Date: 2023-12-29
     * Time: 11:21
     * @param array $tokens
     * @return void
     */
    protected function setTokens(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * Note: 绑定输入定义实例
     * Date: 2023-12-29
     * Time: 14:00
     * @param Definition $definition
     */
    public function bind(Definition $definition)
    {
        $this->arguments = [];
        $this->options = [];
        $this->definition = $definition;

        $this->parse();
    }

    /**
     * Note: 解析输入定义实例
     * Date: 2023-12-29
     * Time: 14:29
     */
    protected function parse()
    {
        $parseOptions = true;
        $this->parsed = $this->tokens;
        while (null !== $token = array_shift($this->parsed)) {
            if ($parseOptions && $token == '') {
                $this->parseArgument($token);
            } elseif ($parseOptions && $token == '--') {
                $parseOptions = false;
            } elseif ($parseOptions && strpos($token, '--') === 0) {
                $this->parseLongOption($token);
            } elseif ($parseOptions && $token[0] === '-' && $token !== '-') {
                $this->parseShortOption($token);
            } else {
                $this->parseArgument($token);
            }
        }
    }

    /**
     * Note: 解析短选项
     * Date: 2023-12-29
     * Time: 14:31
     * @param string $token 当前指令的选项
     */
    private function parseShortOption(string $token)
    {
        $name = substr($token, 1);

        if (strlen($name) > 1) {
            if ($this->definition->hasShortcut($name[0]) && $this->definition->getOptionForShortcut($name[0])->acceptValue()) {
                $value = substr($name, 1);
                if (strpos($value, '=') === 0) {
                    $value = substr($value, 1);
                }

                $this->addShortOption($name[0], $value);
            } else {
                $this->parseShortOptionSet($name);
            }
        } else {
            $this->addShortOption($name, null);
        }
    }

    /**
     * Note: 解析短选项:非单个字符的短选项
     * Date: 2024-01-19
     * Time: 10:20
     * @param string $token 当前指令的选项
     * @return void
     * @throws \RuntimeException
     */
    private function parseShortOptionSet(string $name)
    {
        $len = strlen($name);

        for ($i = 0; $i < $len; ++$i) {
            if (!$this->definition->getOption($name[$i])) {
                throw new \RuntimeException(sprintf('The "-%s" option not exists.'), $name[$i]);
            }

            $option = $this->definition->getOptionForShortcut($name[$i]);

            if ($option->acceptValue()) {
                $this->addLongOption($option->getName(), $i === $len - 1 ? null : substr($name, $i + 1));

                break;
            } else {
                $this->addLongOption($option->getName(), null);
            }
        }
    }

    /**
     * Note: 解析完整选项
     * Date: 2024-01-18
     * Time: 18:41
     * @param string $token 当前指令
     * @return void
     */
    private function parseLongOption(string $token)
    {
        $name = substr($token, 2);

        if (false !== $pos = strpos($name, '=')) {
            $this->addLongOption(substr($name, 0, $pos), substr($name, $pos + 1));
        } else {
            $this->addLongOption($name, null);
        }
    }

    /**
     * Note: 解析参数
     * Date: 2024-01-18
     * Time: 9:33
     * @param string $token 当前指令
     * @throws \RuntimeException
     */
    private function parseArgument(string $token)
    {
        $c = count($this->arguments);

        if ($this->definition->hasArgument($c)) {
            $arg = $this->definition->getArgument($c);

            $this->arguments[$arg->getName()] = $arg->isArray() ? [$token] : $token;
        } elseif ($this->definition->hasArgument($c - 1) && $this->definition->getArgument($c - 1)->isArray()) {
            $arg = $this->definition->hasArgument($c - 1);

            $this->arguments[$arg->getName()][] = $token;
        } else {
            throw new \RuntimeException('Too many arguments.');
        }
    }

    /**
     * Note: 添加一个短选项的值
     * Date: 2023-12-29
     * Time: 14:35
     * @param string $shortcut 短选项名称
     * @param mixed $value 值
     * @throws \RuntimeException
     */
    private function addShortOption(string $shortcut, $value)
    {
        if (!$this->definition->hasShortcut($shortcut)) {
            throw new \RuntimeException(sprintf('The "%s" option does not exist.', $shortcut));
        }

        $this->addLongOption($this->definition->getOptionForShortcut($shortcut)->getName(), $value);
    }

    /**
     * Note: 添加一个完整选项的值
     * Date: 2023-12-29
     * Time: 14:43
     * @param string $name 选项名
     * @param mixed $value 值
     * @return \RuntimeException
     */
    private function addLongOption(string $name, $value)
    {
        if (!$this->definition->hasOption($name)) {
            throw new \RuntimeException(sprintf('The "--%s" option does not exist.', $name));
        }

        $option = $this->definition->getOption($name);

        if ($value === false) {
            $value = null;
        }

        if ($value !== null && !$option->acceptValue()) {
            throw new \RuntimeException(sprintf('The "--%s" option does not accept a value.', $name, $value));
        }

        if ($value === null && $option->acceptValue() && count($this->parsed)) {
            $next = array_shift($this->parsed);
            if (isset($next[0]) && $next[0] != '-') {
                $value = $next;
            } elseif (empty($next)) {
                $value = '';
            } else {
                array_unshift($this->parsed, $next);
            }
        }

        if ($value === null) {
            if ($option->isValueRequired()) {
                throw new \RuntimeException(sprint_f('The "--%s" option requires a value.', $name));
            }

            if (!$option->isArray()) {
                $value = $option->isValueOptional() ? $option->getDefault() : true;
            }
        }

        if ($option->isArray()) {
            $this->options[$name][] = $value;
        } else {
            $this->options[$name] = $value;
        }
    }

    /**
     * Note: 验证输入
     * Date: 2024-01-19
     * Time: 11:34
     * @throws \RuntimeException
     */
    public function validate()
    {
        if (count($this->arguments) < $this->definition->getArgumentRequiredCount()) {
            throw new \RuntimeException('Not enough arguments.');
        }
    }

    /**
     * Note: 设置输入的交互
     * Date: 2023-12-22
     * Time: 15:04
     * @param bool $interactive
     * @return $this
     */
    public function setInteractive(bool $interactive)
    {
        $this->interactive = $interactive;
    }

    /**
     * Note: 检查输入是否是交互的
     * Date: 2023-12-22
     * Time: 15:10
     * @return bool
     */
    public function isInteractive()
    {
        return $this->interactive;
    }

    /**
     * Note: 获取第一个参数
     * Date: 2023-12-22
     * Time: 16:44
     * @return mixed|void
     */
    public function getFirstArgument()
    {
        foreach ($this->tokens as $token) {
            if ($token && $token[0] === '-') {
                continue;
            }

            return $token;
        }

        return;
    }

    /**
     * Note: 检查原始参数是否包含某个值
     * Date: 2023-12-21
     * Time: 10:29
     * @param string|array $values 需要检查的值
     * @return bool
     */
    public function hasParameterOption($values)
    {
        $values = (array)$values;

        foreach ($this->tokens as $token) {
            foreach ($values as $value) {
                if ($token === $value || strpos($token, $value . '=') === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Note: 获取原始选项的值
     * Date: 2023-12-22
     * Time: 15:59
     * @param string|array $values 需要检查的值
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getParameterOption($values, $default = false)
    {
        $values = (array)$values;
        $tokens = $this->tokens;

        while (count($tokens) > 0) {
            $token = array_shift($tokens);

            foreach ($values as $value) {
                if ($token === $value || strpos($token, $value . '=') === 0) {
                    if ($pos = strpos($token, '=') !== false) {
                        return substr($token, $pos + 1);
                    }

                    return array_shift($tokens);
                }
            }
        }

        return $default;
    }

    /**
     * Note: 获取所有参数
     * Date: 2024-01-19
     * Time: 11:35
     * @return array
     */
    public function getArguments()
    {
        return array_merge($this->definition->getArgumentDefaults(), $this->arguments);
    }

    /**
     * Note: 根据名称获取参数
     * Date: 2024-01-19
     * Time: 11:41
     * @param string $name 名称
     * @return Argument|mixed
     * @throws \InvalidArgumentException
     */
    public function getArgument(string $name)
    {
        if (!$this->definition->hasArgument($name)) {
            throw new \InvalidArgumentException(sprintf('The "%s" argument does not exists.', $name));
        }

        return $this->arguments[$name] ?? $this->definition->getArgument($name)->getDefault();
    }

    /**
     * Note: 设置参数的值
     * Date: 2024-01-19
     * Time: 11:44
     * @param string $name 参数名
     * @param string $value 值
     * @throws \InvalidArgumentException
     */
    public function setArgument(string $name, $value)
    {
        if (!$this->definition->hasArgument($name)) {
            throw new \InvalidArgumentException(sprintf('The "%s" argument does not exists.', $name));
        }

        $this->arguments[$name] = $value;
    }

    /**
     * Note: 检查是否存在某个参数
     * Date: 2024-01-19
     * Time: 11:45
     * @param string $name
     * @return bool
     */
    public function hasArgument(string $name)
    {
        return $this->definition->hasArgument($name);
    }

    /**
     * Note: 获取所有的选项
     * Date: 2024-01-19
     * Time: 11:48
     * @return array
     */
    public function getOptions()
    {
        return array_merge($this->definition->getOptionDefaults(), $this->options);
    }

    /**
     * Note: 获取选项值
     * Date: 2024-01-19
     * Time: 11:50
     * @param string $name 选项名
     * @return Option|mixed
     * @throws \InvalidArgumentException
     */
    public function getOption(string $name)
    {
        if (!$this->definition->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('The "%s" option does not exists.', $name));
        }

        return $this->options[$name] ?? $this->definition->getOption($name)->getDefault();
    }

    /**
     * Note: 设置选项值
     * Date: 2024-01-19
     * Time: 11:51
     * @param string $name 选项名
     * @param mixed $value 值
     * @throws \InvalidArgumentException
     */
    public function setOption(string $name, $value)
    {
        if (!$this->definition->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('The "%s" option does not exists.', $name));
        }

        $this->options[$name] = $value;
    }

    /**
     * Note: 是否有某个选项
     * Date: 2024-01-19
     * Time: 11:55
     * @param string $name 选项名
     * @return bool
     */
    public function hasOption(string $name)
    {
        return $this->definition->hasOption($name) && isset($this->options[$name]);
    }

    /**
     * Note: 转义指令
     * Date: 2024-01-19
     * Time: 14:05
     * @param string $token
     * @return string
     */
    public function escapeToken(string $token): string
    {
        return preg_match('{^[\w-]+$}', $token) ? $token : escapeshellarg($token);
    }

    public function __toString()
    {
        $tokens = array_map(function ($token) {
            if (preg_match('{^(-[^=]+=)(.+)}', $token, $match)) {
                return $match[1] . $this->escapeToken($match[2]);
            }

            if ($token && $token[0] !== '-') {
                return $this->escapeToken($token);
            }

            return $token;
        }, $this->tokens);

        return implode(',', $tokens);
    }

}