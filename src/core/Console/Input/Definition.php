<?php

namespace Enna\Framework\Console\Input;

class Definition
{
    /**
     * 参数
     * @var Argument[]
     */
    private $arguments;

    /**
     * 必须的数量
     * @var int
     */
    private $requiredCount;

    /**
     * 是否可选
     * @var boolean
     */
    private $hasOptional;

    /**
     * 是否数组参数
     * @var boolean
     */
    private $hasAnArrayArgument;

    /**
     * 选项
     * @var Option[]
     */
    private $options;

    /**
     * 选项简称
     * @var array
     */
    private $shortcuts;

    public function __construct(array $definition = [])
    {
        $this->setDefinition($definition);
    }

    /**
     * Note: 设置指令的定义
     * Date: 2023-12-08
     * Time: 14:21
     * @param array $definition 定义的数组
     * @return void
     */
    public function setDefinition(array $definition)
    {
        $arguments = [];
        $options = [];
        foreach ($definition as $item) {
            if ($item instanceof Option) {
                $options[] = $item;
            } else {
                $arguments[] = $item;
            }
        }

        $this->setArguments($arguments);
        $this->setOptions($options);
    }

    /**
     * Note: 设置参数
     * Date: 2023-12-23
     * Time: 10:41
     * @param Argument[] $arguments
     */
    public function setArguments(array $arguments = [])
    {
        $this->arguments = [];
        $this->requiredCount = 0;
        $this->hasOptional = false;
        $this->hasAnArrayArgument = false;

        $this->addArguments($arguments);
    }

    /**
     * Note: 添加参数
     * Date: 2023-12-23
     * Time: 11:05
     * @param array $arguments
     * @return void
     * @api
     */
    public function addArguments(array $arguments = [])
    {
        if ($arguments !== null) {
            foreach ($arguments as $argument) {
                $this->addArgument($argument);
            }
        }
    }

    /**
     * Note: 添加一个参数
     * Date: 2023-12-23
     * Time: 11:06
     * @param Argument $argument 参数
     * @return void
     * @throws \LogicException
     */
    public function addArgument(Argument $argument)
    {
        if (isset($this->arguments[$argument->getName()])) {
            throw new \LogicException(sprintf('An argument with anme "%s" already exists.', $argument->getName()));
        }

        if ($this->hasAnArrayArgument) {
            throw new \LogicException('Cannot add an argument after an array argument.');
        }

        if ($argument->isRequired() && $this->hasOptional) {
            throw new \LogicException('Cannot add a required argument after an optional one.');
        }

        if ($argument->isArray()) {
            $this->hasAnArrayArgument = true;
        }

        if ($argument->isRequired()) {
            ++$this->requiredCount;
        } else {
            $this->hasOptional = true;
        }

        $this->arguments[$argument->getName()] = $argument;
    }

    /**
     * Note: 根据名称或位置获取某个参数
     * Date: 2023-12-23
     * Time: 11:43
     * @param string|int $name 参数名或位置
     * @return Argument
     */
    public function getArgument($name)
    {
        if (!$this->hasArgument($name)) {
            throw new \LogicException(sprintf('The "%s" argument does not exist.', $name));
        }

        $arguments = is_int($name) ? array_values($this->arguments) : $this->arguments;

        return $arguments[$name];
    }

    /**
     * Note: 根据名称或位置检查是否具有某个参数
     * Date: 2023-12-23
     * Time: 11:39
     * @param string|int $name 参数名或位置
     * @return boolean
     */
    public function hasArgument($name)
    {
        $arguments = is_int($name) ? array_values($this->arguments) : $this->arguments;

        return isset($arguments[$name]);
    }

    /**
     * Note: 获取所有参数
     * Date: 2023-12-23
     * Time: 11:35
     * @return Argument[]
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Note: 获取参数数量
     * Date: 2023-12-29
     * Time: 11:30
     * @return int
     */
    public function getArgumentCount()
    {
        return $this->hasAnArrayArgument ? PHP_INI_MAX : count($this->arguments);
    }

    /**
     * Note: 获取必填的参数数量
     * Date: 2023-12-23
     * Time: 11:37
     * @return int
     */
    public function getArgumentRequiredCount()
    {
        return $this->requiredCount;
    }

    /**
     * Note: 获取所有参数的默认值
     * Date: 2023-12-23
     * Time: 11:38
     * @return array
     */
    public function getArgumentDefaults()
    {
        $values = [];
        foreach ($this->arguments as $argument) {
            $values[$argument->getName()] = $argument->getDefault();
        }

        return $values;
    }

    /**
     * Note: 设置选项
     * Date: 2023-12-23
     * Time: 10:43
     * @param Option[] $options 选项数组
     * @return void
     */
    public function setOptions(array $options = [])
    {
        $this->options = [];
        $this->shortcuts = [];

        $this->addOptions($options);
    }

    /**
     * Note: 添加选项
     * Date: 2023-12-23
     * Time: 11:46
     * @param array $options 选项数组
     * @return void
     * @api
     */
    public function addOptions(array $options = [])
    {
        foreach ($options as $option) {
            $this->addOption($option);
        }
    }

    /**
     * Note: 添加一个选项
     * Date: 2023-12-23
     * Time: 14:38
     * @param Option $option 选项
     * @return void
     * @throws \LogicException
     */
    public function addOption(Option $option)
    {
        if (isset($this->options[$option->getName()]) && !$option->equals($this->options[$option->getName()])) {
            throw new \LogicException(sprintf('An option named "%s" already exists.', $option->getName()));
        }

        if ($option->getShortcut()) {
            foreach (explode('|', $option->getShortcut()) as $shortcut) {
                if (isset($this->shortcuts[$shortcut]) && !$option->equals($this->options[$this->shortcuts[$shortcut]])) {
                    throw new \LogicException(sprintf('An option with shortcut "%s" already exists.', $shortcut));
                }
            }
        }

        $this->options[$option->getName()] = $option;
        if ($option->getShortcut()) {
            foreach (explode('|', $option->getShortcut()) as $shortcut) {
                $this->shortcuts[$shortcut] = $option->getName();
            }
        }
    }

    /**
     * Note: 根据名称获取选项
     * Date: 2023-12-23
     * Time: 14:42
     * @param string $name 选项名
     * @return Option
     * @throws \InvalidArgumentException
     */
    public function getOption(string $name)
    {
        if (!$this->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('The "--%S" option does not exist.', $name));
        }

        return $this->options[$name];
    }

    /**
     * Note: 根据名称检查是否有这个选项
     * Date: 2023-12-23
     * Time: 14:41
     * @param string $name 选项名
     * @return bool
     */
    public function hasOption(string $name)
    {
        return isset($this->options[$name]);
    }

    /**
     * Note: 获取所有选项
     * Date: 2023-12-23
     * Time: 14:43
     * @return Option[]
     * @api
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Note: 获取所有选项的默认值
     * Date: 2023-12-23
     * Time: 14:46
     * @return array
     */
    public function getOptionDefaults()
    {
        $values = [];
        foreach ($this->options as $option) {
            $values[$option->getName()] = $option->getDefault();
        }

        return $values;
    }

    /**
     * Note: 根据名称检查某个选项是否有短名称
     * Date: 2023-12-23
     * Time: 14:44
     * @param string $name 选项名
     * @return bool
     */
    public function hasShortcut(string $name)
    {
        return isset($this->shortcuts[$name]);
    }

    /**
     * Note: 根据短名称获取选项
     * Date: 2023-12-23
     * Time: 14:51
     * @param string $shortcut 短名称
     * @return Option
     */
    public function getOptionForShortcut(string $shortcut)
    {
        return $this->getOption($this->shortcutToName($shortcut));
    }

    /**
     * Note: 根据短名称获取选项名
     * Date: 2023-12-23
     * Time: 14:49
     * @param string $shortcut 短名称
     * @return string
     * @throws \InvalidArgumentException
     */
    private function shortcutToName(string $shortcut)
    {
        if (!isset($this->shortcuts[$shortcut])) {
            throw new \InvalidArgumentException(sprintf('The "-%s" option does not exist.', $shortcut));
        }

        return $this->shortcuts[$shortcut];
    }

    /**
     * Note: 获取该指令的介绍
     * Date: 2023-12-23
     * Time: 14:53
     * @param bool $short 是否短介绍
     * @return string
     */
    public function getSynopsis(bool $short = false)
    {
        $elements = [];

        if ($short && $this->getOptions()) {
            $elements[] = '[options]';
        } elseif (!$short) {
            foreach ($this->getOptions() as $option) {
                $value = '';
                if ($option->acceptValue()) {
                    $value = sprintf('%s%s%s', $option->isValueOptional() ? '[' : '', strtoupper($option->getName()), $option->isValueOptional() ? ']' : '');
                }

                $shortcut = $option->getShortcut() ? sprintf('-%s|', $option->getShortcut()) : '';
                $elements[] = sprintf('[%s--%s%s]', $shortcut, $option->getName(), $value);
            }
        }

        if (count($elements) && $this->getArguments()) {
            $elements[] = '[--]';
        }

        foreach ($this->getArguments() as $argument) {
            $element = '<' . $argument->getName() . '>';
            if (!$argument->isRequired()) {
                $element = '[' . $element . ']';
            } elseif ($argument->isArray()) {
                $element .= ' (' . $element . ')';
            }
            
            if ($argument->isArray()) {
                $element .= '...';
            }

            $elements[] = $element;
        }
  
        return implode(' ', $elements);
    }
}

