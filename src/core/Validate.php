<?php
declare(strict_types=1);

namespace Enna\Framework;

use Closure;
use Enna\Framework\Request;
use Enna\Framework\Lang;
use Enna\Framework\Exception\ValidateException;

class Validate
{
    /**
     * 自定义验证类型
     * @var array
     */
    protected $type = [];

    /**
     * 验证场景定义
     * @var array
     */
    protected $scene = [];

    /**
     * 当前验证场景
     * @var string
     */
    protected $currentScene;

    /**
     * 当前验证场景需要验证的字段
     * @var array
     */
    protected $only = [];

    /**
     * 验证规则
     * @var array
     */
    protected $rule = [];

    /**
     * 验证提示信息
     * @var array
     */
    protected $message = [];

    /**
     * 验证字段描述
     * @var array
     */
    protected $field = [];

    /**
     * 验证失败错误信息
     * @var array
     */
    protected $error = [];

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batch = false;

    /**
     * 验证失败是否抛出异常
     * @var bool
     */
    protected $failException = false;

    /**
     * Db对象
     * @var Db
     */
    protected $db;

    /**
     * 语言对象
     * @var Lang
     */
    protected $lang;

    /**
     * 请求对象
     * @var Request
     */
    protected $request;

    /**
     * 默认规则失败提示
     * @var array
     */
    protected $typeMsg = [
        'require' => ':attribute require',
    ];

    /**
     * @var Closure
     */
    protected static $maker = [];

    public function __construct()
    {
        if (!empty(static::$maker)) {
            foreach (static::$maker as $maker) {
                call_user_func($maker, $this);
            }
        }
    }

    /**
     * Note: 设置服务注入
     * Date: 2023-01-17
     * Time: 10:19
     * @param Closure $maker
     * @return void
     */
    public function maker(Closure $maker)
    {
        static::$maker[] = $maker;
    }

    /**
     * Note: 设置Lang对象
     * Date: 2023-01-17
     * Time: 10:24
     * @param \Enna\Framework\Lang $lang
     * @return void
     */
    public function setLang(Lang $lang)
    {
        $this->lang = $lang;
    }

    /**
     * Note: 设置Db对象
     * Date: 2023-01-17
     * Time: 10:24
     * @param Db $db
     * @return void
     */
    public function setDb(Db $db)
    {
        $this->db = $db;
    }

    /**
     * Note: 设置Request对象
     * Date: 2023-01-17
     * Time: 10:25
     * @param \Enna\Framework\Request $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Note: 添加字段验证规则
     * Date: 2023-01-17
     * Time: 11:24
     * @param string|array $name 字段名称或者规则数组
     * @param mixed $rule 验证规则或者字段描述信息
     * @return $this
     */
    public function rule($name, $rule)
    {
        if (is_array($name)) {
            $this->rule = $name + $this->rule;
            if (is_array($rule)) {
                $this->field = array_merge($this->field, $rule);
            }
        } else {
            $this->rule[$name] = $rule;
        }

        return $this;
    }

    /**
     * Note: 数据验证
     * Date: 2023-01-17
     * Time: 14:18
     * @param array $data 数据
     * @param array $rules 规则
     * return bool
     */
    public function check(array $data, array $rules = [])
    {
        $this->error = [];

        //获取当前场景
        if ($this->currentScene) {
            $this->getScene($this->currentScene);
        }

        //获取所有的规则
        if (empty($rules)) {
            $rules = $this->rule;
        }

        foreach ($rules as $key => $rule) {
            //获取字段描述
            if (strpos($key, '|')) {
                [$key, $title] = explode('|', $key);
            } else {
                $title = $this->field[$key] ?? $key;
            }

            //当设置场景时:过滤不需要验证的字段
            if (!empty($this->only) && !in_array($key, $this->only)) {
                continue;
            }

            //获取数据
            $value = $this->getDataValue($data, $key);

            //字段验证
            if ($rule instanceof Closure) {
                $result = call_user_func_array($rule, [$value, $data]);
            } else {
                $result = $this->checkItem($key, $value, $rule, $data, $title);
            }

            //对错误的错误
            if ($result !== true) {
                if (!empty($this->batch)) { //批量的处理
                    $this->error[$key] = $result;
                } elseif ($this->failException) { //抛出异常
                    throw new ValidateException($result);
                } else {
                    $this->error = $result; //返回false,并记录error
                    return false;
                }
            }
        }

        if (!empty($this->error)) {
            if ($this->failException) {
                throw new ValidateException($this->error);
            }
        }

        return true;

    }

    /**
     * Note: 设置当前场景
     * Date: 2023-01-17
     * Time: 16:25
     * @param string $name 场景名
     * @return $this
     */
    public function scene(string $name)
    {
        $this->currentScene = $name;

        return $this;
    }

    /**
     * Note: 获取验证场景
     * Date: 2023-01-17
     * Time: 16:19
     * @param string $scene 场景
     * @return void
     */
    public function getScene(string $scene)
    {
        $this->only = [];

        if (isset($this->scene[$scene])) {
            $this->only = $this->scene[$scene];
        }
    }

    /**
     * Note: 获取对应key的值
     * Date: 2023-01-17
     * Time: 16:37
     * @param array $data 数据
     * @param string $key 标识
     * @return mixed
     */
    protected function getDataValue(array $data, $key)
    {
        if (is_numeric($key)) {
            $value = $key;
        } elseif (is_string($key) && strpos($key, '.')) {
            foreach (explode('.', $key) as $key) {
                if (!isset($data[$key])) {
                    $value = null;
                    break;
                }
                $value = $data = $data[$key];
            }
        } else {
            $value = $data[$key] ?? null;
        }

        return $value;
    }

    /**
     * Note: 验证单个字段规则
     * Date: 2023-01-17
     * Time: 16:47
     * @param string $field 字段名称
     * @param mixed $value 字段值
     * @param mixed $rules 字段规则
     * @param array $data 数据
     * @param string $title 字段描述
     * @param array $msg 提示信息
     * @return mixed
     */
    protected function checkItem(string $field, $value, $rules, array $data, string $title = '', array $msg = [])
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        if (empty($rules)) {
            return true;
        }

        foreach ($rules as $key => $rule) {
            if ($rule instanceof Closure) {
                $result = call_user_func_array($rule, [$value, $data]);
            } else {
                [$type, $rule] = $this->getValidateType($key, $rule);

                if (isset($this->type[$type])) {
                    $result = call_user_func_array($this->type[$type], [$value, $rule, $data, $field, $title]);
                } elseif ($rule == 'must' || strpos($rule, 'require') === 0 || (!is_null($value) && $value !== '')) {
                    $result = call_user_func_array([$this, $type], [$value, $rule, $data, $field, $title]);
                } else {
                    $result = true;
                }
            }

            if ($result === false) {

            } elseif ($result !== true) {

            }
        }

        return $result ?? true;
    }

    /**
     * Note: 获取当前验证类型和规则
     * Date: 2023-01-17
     * Time: 18:11
     * @param mixed $key key
     * @param mixed $rule 规则
     * @return array
     */
    protected function getValidateType($key, $rule)
    {
        if (strpos($rule, ':')) {
            [$type, $rule] = explode(':', $rule, 2);
        } else {
            $type = 'is';
        }

        return [$type, $rule];
    }

    /**
     * Note: 批量验证
     * Date: 2023-01-17
     * Time: 14:14
     * @param bool $batch
     * @return $this
     */
    public function batch(bool $batch = true)
    {
        $this->batch = $batch;

        return $this;
    }

    /**
     * Note: 扩展验证规则
     * Date: 2023-01-17
     * Time: 11:33
     * @param string $type 验证规则类型
     * @param Closure $callbck 闭包(验证方法)
     * @param string|null $message 验证错误信息
     * @return $this
     */
    public function extend(string $type, Closure $callbck, string $message = null)
    {
        $this->type[$type] = $callbck;

        if ($message) {
            $this->typeMsg[$type] = $message;
        }

        return $this;
    }

}