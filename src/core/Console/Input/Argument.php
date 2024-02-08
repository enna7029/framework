<?php

namespace Enna\Framework\Console\Input;

class Argument
{
    //必须参数
    const REQUIRED = 1;

    //可选参数
    const OPTIONAL = 2;

    //数组参数
    const IS_ARRAY = 4;

    /**
     * 参数名
     * @var string
     */
    private $name;

    /**
     * 参数类型
     * @var int
     */
    private $mode;

    /**
     * 参数默认值
     * @var mixed
     */
    private $default;

    /**
     * 参数描述
     * @var string
     */
    private $description;

    /**
     * Argument constructor.
     * @param string $name 参数名
     * @param int|null $mode 参数类型
     * @param string $description 描述
     * @param mixed $default 默认值
     * @throws \InvalidArgumentException
     */
    public function __construct(string $name, int $mode = null, string $description = '', $default = null)
    {
        if ($mode === null) {
            $mode = self::OPTIONAL;
        } elseif (!is_int($mode) || $mode > 7 || $mode < 1) {
            throw new \InvalidArgumentException(sprintf('Argument mode "%s" is not valid.', $mode));
        }

        $this->name = $name;
        $this->mode = $mode;
        $this->description = $description;

        $this->setDefault($default);
    }

    /**
     * Note: 设置默认值
     * Date: 2023-12-08
     * Time: 15:04
     * @param mixed $default 默认值
     * @throws \LogicException
     */
    public function setDefault($default = null)
    {
        if ($this->mode === self::REQUIRED && $default !== null) {
            throw new \LogicException('Cannot set a default value except for InputArgument::OPTIONAL mode.');
        }

        if ($this->isArray()) {
            if ($default === null) {
                $default = [];
            } elseif (!is_array($default)) {
                throw new \LogicException('A default value for an array argument must be an array.');
            }
        }

        $this->default = $default;
    }

    /**
     * Note: 该参数是否接受数组
     * Date: 2023-12-08
     * Time: 15:25
     * @return bool
     */
    public function isArray()
    {
        return self::IS_ARRAY === (self::IS_ARRAY & $this->mode);
    }

    /**
     * Note: 是否必须
     * Date: 2023-12-23
     * Time: 11:25
     * @return bool
     */
    public function isRequired()
    {
        return self::REQUIRED === (self::REQUIRED & $this->mode);
    }

    /**
     * Note: 获取参数名
     * Date: 2023-12-23
     * Time: 11:26
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Note: 获取默认值
     * Date: 2023-12-23
     * Time: 11:28
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Note: 获取描述
     * Date: 2023-12-23
     * Time: 11:28
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}