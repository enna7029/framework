<?php

namespace Enna\Framework\Console\Input;

class Option
{
    //无需传值
    const VALUE_NONE = 1;

    //必须传值
    const VALUE_REQUIRED = 2;

    //可选传值
    const VALUE_OPTIONAL = 4;

    //传值数组
    const VALUE_IS_ARRAY = 8;

    /**
     * 选项名称
     * @var string
     */
    private $name = '';

    /**
     * 选项短名称
     * @var string
     */
    private $shortcut = '';

    /**
     * 选项类型
     * @var int
     */
    private $mode;

    /**
     * 选项描述
     * @var string
     */
    private $description = '';

    /**
     * 选项默认值
     * @var mixed
     */
    private $default;

    /**
     * Option constructor.
     * @param string $name 名称
     * @param string $shortcut 短名称
     * @param int $mode 类型
     * @param string $description 描述
     * @param mixed $default 默认值
     * @throws \InvalidArgumentException
     */
    public function __construct($name, $shortcut = null, $mode = null, string $description = '', $default = null)
    {
        if (strpos($name, '--') === 0) {
            $name = substr($name, 2);
        }

        if (empty($name)) {
            throw new \InvalidArgumentException('An option name cannot be empty.');
        }

        if (empty($shortcut)) {
            $shortcut = '';
        }

        if ($shortcut !== '') {
            if (is_array($shortcut)) {
                $shortcut = implode('|', $shortcut);
            }

            $shortcuts = preg_split('{(\|)-?}', ltrim($shortcut, '-'));
            $shortcuts = array_filter($shortcuts);
            $shortcut = implode('|', $shortcuts);

            if (empty($shortcut)) {
                throw new \InvalidArgumentException('An option shortcut cannot be empty.');
            }
        }

        if ($mode === null) {
            $mode = self::VALUE_NONE;
        } elseif (!is_int($mode) || $mode > 15 || $mode < 1) {
            throw new \InvalidArgumentException(sprintf('Option mode "%s" is not valid.', $mode));
        }

        $this->name = $name;
        $this->shortcut = $shortcut;
        $this->mode = $mode;
        $this->description = $description;

        $this->setDefault($default);
    }

    /**
     * Note: 设置默认值
     * Date: 2023-12-08
     * Time: 18:24
     * @param mixed $default
     * @throws \LogicException
     */
    public function setDefault($default = null)
    {
        if (self::VALUE_NONE && (self::VALUE_NONE & $this->mode) && $default !== null) {
            throw new \LogicException('Cannot set a default value when using InputOption::VALUE_NONE mode.');
        }

        if ($this->isArray()) {
            if ($default === null) {
                $default = [];
            } elseif (!is_array($default)) {
                throw new \LogicException('A default value for an array option must be an array.');
            }
        }

        $this->default = $this->acceptValue() ? $default : false;
    }

    /**
     * Note: 选项是否接收数组
     * Date: 2023-12-08
     * Time: 18:25
     * @return bool 类型是 self::VALUE_IS_ARRAY 的时候返回true,其他均返回false
     */
    public function isArray()
    {
        return self::VALUE_IS_ARRAY && (self::VALUE_IS_ARRAY & $this->mode);
    }

    /**
     * Note: 是否可以设置值
     * Date: 2023-12-08
     * Time: 18:28
     * @return bool 类型不是 self::VALUE_NONE 的时候返回true,其他均返回false
     */
    public function acceptValue()
    {
        return $this->isValueRequired() || $this->isValueOptional();
    }

    /**
     * Note: 是否必须
     * Date: 2023-12-08
     * Time: 18:28
     * @return bool 类型是 self::VALUE_REQUIRED 的时候返回true,其他均返回false
     */
    public function isValueRequired()
    {
        return self::VALUE_REQUIRED && (self::VALUE_REQUIRED & $this->mode);
    }

    /**
     * Note: 是否可选
     * Date: 2023-12-08
     * Time: 18:28
     * @return bool 类型是 self::VALUE_OPTIONAL 的时候返回true,其他均返回false
     */
    public function isValueOptional()
    {
        return self::VALUE_OPTIONAL && (self::VALUE_OPTIONAL & $this->mode);
    }

    /**
     * Note: 获取短名称
     * Date: 2023-12-23
     * Time: 14:14
     * @return string
     */
    public function getShortcut()
    {
        return $this->shortcut;
    }

    /**
     * Note: 获取选项名
     * Date: 2023-12-23
     * Time: 14:14
     * @return false|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Note: 获取默认值
     * Date: 2023-12-23
     * Time: 14:15
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Note: 获取描述文字
     * Date: 2023-12-23
     * Time: 14:16
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Note: 检查所给的选项是否是当前这个
     * Date: 2023-12-23
     * Time: 14:19
     * @param Option $option
     * @return bool
     */
    public function equals(Option $option)
    {
        return $option->getName() === $this->getName()
            && $option->getShortcut() === $this->getShortcut()
            && $option->getDefault() === $this->getDefault()
            && $option->isArray() === $this->isArray()
            && $option->isValueRequired() === $this->isValueRequired()
            && $option->isValueOptional() === $this->isValueOptional();
    }
}