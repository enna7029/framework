<?php
declare(strict_types=1);

namespace Enna\Framework\Validate;

/**
 * 验证规则实例类
 * Class ValidateRule
 * @package Enna\Framework\Validate
 */
class ValidateRule
{
    /**
     * 验证字段名称
     * @var string
     */
    protected $title;

    /**
     * 当前验证规则
     * @var array
     */
    protected $rule = [];

    /**
     * 验证提示信息
     * @var array
     */
    protected $message = [];

    /**
     * Note: 添加验证规则
     * Date: 2023-09-14
     * Time: 18:02
     * @param string $name 验证字段|验证名称
     * @param mixed $rule 验证规则
     * @param string $msg 提示信息
     * @return $this
     */
    public function addItem(string $name, $rule = null, string $msg = '')
    {
        if ($rule || $rule === 0) {
            $this->rule[$name] = $rule;
        } else {
            $this->rule[] = $rule;
        }

        $this->message = $msg;

        return $this;
    }

    /**
     * Note: 获取验证规则
     * Date: 2023-09-14
     * Time: 18:12
     * @return array
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Note: 获取验证提示
     * Date: 2023-09-14
     * Time: 18:12
     * @return array
     */
    public function getMsg()
    {
        return $this->message;
    }

    /**
     * Note: 设置验证字段名称
     * Date: 2023-09-14
     * Time: 18:15
     */
    public function title()
    {
        $this->title = $title;
    }

    /**
     * Note: 获取字段验证名称
     * Date: 2023-09-14
     * Time: 18:15
     * @return string
     */
    public function getTitle()
    {
        return $this->title ?: '';
    }

    public function __call(string $method, array $args)
    {
        if (strtolower(substr($method, 0, 2)) == 'is') {
            $method = substr($method, 0, 2);
        }

        array_unshift($args, lcfirst($method));

        return call_user_func_array([$this, 'addItem'], $args);
    }

    public static function __callStatic(string $name, array $arguments)
    {
        $rule = new static;

        if (strtolower(substr($method, 0, 2)) == 'is') {
            $method = substr($method, 0, 2);
        }

        array_unshift($args, lcfirst($method));

        return call_user_func_array([$rule, 'addItem'], $args);
    }
}