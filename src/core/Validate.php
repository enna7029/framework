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
     * 场景需要验证的字段
     * @var array
     */
    protected $only = [];

    /**
     * 场景需要增加的字段规则
     * @var array
     */
    protected $append = [];

    /**
     * 场景需要移除的字段规则
     * @var array
     */
    protected $remove = [];

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
     * 验证字段描述,如age|年龄 ,年龄就是age字段的描述
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
     * 自定义正则验证
     * @var array
     */
    protected $regex = [];

    /**
     * 默认正则验证
     * @var array
     */
    protected $defaultRegex = [
        'alpha' => '/^[A-Za-z]+$/',
        'alphaNum' => '/^[A-Za-z0-9]+$/',
        'alphaDash' => '/^[A-Za-z0-9\-\_]+$/',
        'chs' => '/^[\x{4e00}-\x{9fa5}\x{9fa6}-\x{9fef}\x{3400}-\x{4db5}\x{20000}-\x{2ebe0}]+$/u',
        'chsAlpha' => '/^[\x{4e00}-\x{9fa5}\x{9fa6}-\x{9fef}\x{3400}-\x{4db5}\x{20000}-\x{2ebe0}a-zA-Z]+$/u',
        'chsAlphaNum' => '/^[\x{4e00}-\x{9fa5}\x{9fa6}-\x{9fef}\x{3400}-\x{4db5}\x{20000}-\x{2ebe0}a-zA-Z0-9]+$/u',
        'chsDash' => '/^[\x{4e00}-\x{9fa5}\x{9fa6}-\x{9fef}\x{3400}-\x{4db5}\x{20000}-\x{2ebe0}a-zA-Z0-9\_\-]+$/u',
        'mobile' => '/^1[3-9]\d{9}$/',
        'idCard' => '/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}$)/',
        'zip' => '/\d{6}/',
    ];

    /**
     * filter_var 规则
     * @var array
     */
    protected $filter = [
        'email' => FILTER_VALIDATE_EMAIL,
        'ip' => FILTER_VALIDATE_IP,
        'integer' => FILTER_VALIDATE_INT,
        'url' => FILTER_VALIDATE_URL,
        'macAddr' => FILTER_VALIDATE_MAC,
        'float' => FILTER_VALIDATE_FLOAT
    ];

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
        'must' => ':attribute must',
        'number' => ':attribute must be numeric',
        'integer' => ':attribute must be integer',
        'float' => ':attribute must be float',
        'boolean' => ':attribute must be bool',
        'email' => ':attribute not a valid email address',
        'mobile' => ':attribute not a valid mobile',
        'array' => ':attribute must be a array',
        'accepted' => ':attribute must be yes,on or 1',
        'date' => ':attribute not a valid datetime',
        'file' => ':attribute not a valid file',
        'image' => ':attribute not a valid image',
        'ip' => ':attribute not a valid ip',
        'url' => ':attribute not a valid url',
        'in' => ':attribute must be in :rule',
        'notIn' => ':attribute be notin :rule',
        'between' => ':attribute must between :1 - :2',
        'notBetween' => ':attribute not between :1 - :2',
        'length' => 'size of :attribute must be :rule',
        'max' => 'max size of :attribute muste be :rule',
        'min' => 'min size of :attribute must be :rule',
        'after' => ':attribute cannot be less than :rule',
        'before' => ':attribute cannot exceed :rule',
        'egt' => ':attribute must greater than or equal :rule',
        'gt' => ':attribute must greater than :rule',
        'elt' => ':attribute must less than or equal :rule',
        'lt' => ':attribute must less than :rule',
        'eq' => ':attribute must equal :rule',
        'unique' => ':attribute has exists',
        'method' => 'invalid Request method',
        'token' => 'invalid  token',
        'fileSize' => 'filesize not match',
        'fileExt' => 'extendsions to upload is not allowed',
        'fileMime' => 'mimetype to upload is not allowed',
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
    public static function maker(Closure $maker)
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
    public function rule($name, $rule = '')
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
     * Note: 设置验证失败后,是否抛出异常
     * Date: 2023-02-10
     * Time: 15:18
     * @param bool $fail 是否抛出异常
     * @return $this
     */
    public function failException(bool $fail = true)
    {
        $this->failException = $fail;

        return $this;
    }

    /**
     * Note: 设置提示信息
     * Date: 2023-02-10
     * Time: 15:20
     * @param array $message 错误信息
     * @return $this
     */
    public function message(array $message)
    {
        $this->message = array_merge($this->message, $message);

        return $this;
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
     * Note: 判断是否存在某个验证场景
     * Date: 2023-02-11
     * Time: 11:23
     * @param string $name 场景
     * @return bool
     */
    public function hasScene(string $name)
    {
        return isset($this->scene[$name]) || method_exists($this, 'scene' . $name);
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
        $this->only = $this->append = $this->remove = [];

        if (method_exists($this, 'scene' . $scene)) {
            call_user_func([$this, 'scene' . $scene]);
        } elseif (isset($this->scene[$scene])) {
            $this->only = $this->scene[$scene];
        }
    }

    /**
     * Note: 批量验证
     * Date: 2023-01-17
     * Time: 14:14
     * @param bool $batch 是否批量验证
     * @return $this
     */
    public function batch(bool $batch = true)
    {
        $this->batch = $batch;

        return $this;
    }

    /**
     * Note: 指定需要验证的字段列表
     * Date: 2023-02-11
     * Time: 11:39
     * @param array $fields 字段名
     * @return $this
     */
    public function only(array $fields)
    {
        $this->only = $fields;

        return $this;
    }

    /**
     * Note: 移除指定字段的验证规则
     * Date: 2023-02-11
     * Time: 11:50
     * @param array|string $field 字段
     * @param string|null $rule 验证规则
     * @return $this
     */
    public function remove($field, $rule = null)
    {
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                if (is_int($key)) {
                    $this->remove($key);
                } else {
                    $this->remove($key, $value);
                }
            }
        } else {
            if (is_string($rule)) {
                $rule = explode('|', $rule);
            }

            $this->remove[$field] = $rule;
        }
    }

    /**
     * Note: 添加指定字段的验证规则
     * Date: 2023-02-11
     * Time: 11:51
     * @param array|string $field 字段
     * @param string|null $rule 验证规则
     * @return $this
     */
    public function append($field, $rule = null)
    {
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $this->append($key, $value);
            }
        } else {
            if (is_string($rule)) {
                $rule = explode('|', $rule);
            }

            $this->append[$field] = $rule;
        }

        return $this;
    }

    /**
     * Note: 数据验证
     * Date: 2023-01-17
     * Time: 14:18
     * @param array $data 数据
     * @param array $rules 验证规则
     * @return bool
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

        //添加场景中新增字段(未对规则进行判断)
        foreach ($this->append as $key => $rule) {
            if (!isset($rules[$key])) {
                $rules[$key] = $rule;
                unset($this->append[$key]);
            }
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
            if ($rule instanceof Closure) { //闭包
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
            return false;
        }

        return true;

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
        //对移除的字段进行判断
        if (isset($this->remove[$field]) && $this->remove[$field] === true && empty($this->append[$field])) {
            return true;
        }

        //对字段规则进行数组化
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        //添加场景中新增字段规则
        if (isset($this->append[$field])) {
            $rules = array_unique(array_merge($rules, $this->append[$field]), SORT_REGULAR);
            unset($this->append[$field]);
        }

        if (empty($rules)) {
            return true;
        }

        foreach ($rules as $key => $rule) {
            if ($rule instanceof Closure) {
                $result = call_user_func_array($rule, [$value, $data]);
                $info = is_numeric($key) ? '' : $key;
            } else {
                //获取验证类型,验证值,验证原始类型
                [$type, $rule, $info] = $this->getValidateType($key, $rule);

                //对添加和移除的进行处理
                if (isset($this->append[$field]) && $this->append[$field] === $info) {

                } elseif (isset($this->remove[$field]) && in_array($info, $this->remove[$field])) {
                    continue;
                }

                if (isset($this->type[$type])) { //自定义的扩展类型
                    $result = call_user_func_array($this->type[$type], [$value, $rule, $data, $field, $title]);
                } elseif ($rule == 'must' || strpos($rule, 'require') === 0 || (!is_null($value) && $value !== '')) { //内置类型
                    $result = call_user_func_array([$this, $type], [$value, $rule, $data, $field, $title]);
                } else { //未定义的类型
                    $result = true;
                }
            }

            if ($result === false) {
                $message = $this->getRuleMsg($field, $title, $info, $rule);

                return $message;
            } elseif ($result !== true) {
                if (is_string($result) && strpos($result, ':') !== false) {
                    $message = str_replace(':attribute', $title, $result);

                    if (strpos($message, ':rule') && is_scalar($rule)) {
                        $message = str_replace(':rule', (string)$rule, $message);
                    }
                }
                return $message;
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
            $info = $type;
        } elseif (method_exists($this, $rule)) {
            $type = $rule;
            $info = $rule;
            $rule = '';
        } else {
            $type = 'is';
            $info = $rule;
        }

        return [$type, $rule, $info];
    }

    /**
     * Note: 获取验证规则的错误提示信息
     * Date: 2023-01-18
     * Time: 11:03
     * @param string $attibute 字段名称
     * @param string $title 字段描述名
     * @param string $type 规则名称
     * @param mixed $rule 规则
     * @return string|array
     */
    protected function getRuleMsg(string $attibute, string $title, string $type, $rule)
    {
        if (isset($this->message[$attibute . '.' . $type])) { //例如:name.require='name不能为空'
            $msg = $this->message[$attibute . '.' . $type];
        } elseif (isset($this->typeMsg[$type])) { //自定义扩展的错误提示
            $msg = $this->typeMsg[$type];
        } else {
            $msg = $title . $this->lang->get('not conform to the rules');
        }

        if (is_array($msg)) {
            return $this->errorMsgIsArray($msg, $rule, $title);
        }

        return $this->parseErrorMsg($msg, $rule, $title);
    }

    /**
     * Note: 错误信息数组处理
     * Date: 2023-01-18
     * Time: 11:47
     * @param array $msg 错误信息
     * @param mixed $rule 规则
     * @param string $title 字段描述名
     * @return array
     */
    protected function errorMsgIsArray(array $msg, $rule, string $title)
    {
        foreach ($msg as $key => $val) {
            if (is_string($val)) {
                $msg[$key] = $this->parseErrorMsg($val, $rule, $title);
            }
        }

        return $msg;
    }

    /**
     * Note: 获取验证规则的错误提示信息
     * Date: 2023-01-18
     * Time: 11:45
     * @param string $msg 错误信息
     * @param mixed $rule 规则
     * @param string $title 字段描述名
     * @return string
     */
    protected function parseErrorMsg(string $msg, $rule, string $title)
    {
        if ($this->lang->has($msg)) {
            $msg = $this->lang->get($msg);
        }

        if (is_array($rule)) {
            $rule = implode(',', $rule);
        }

        if (is_scalar($rule) && strpos($msg, ':') !== false) {
            if (is_string($rule) && strpos($rule, ',')) {
                $array = array_pad(explode(',', $rule), 3, '');
            } else {
                $array = array_pad([], 3, '');
            }

            $msg = str_replace(
                [':attribute', ':1', ':2', ':3'],
                [$title, $array[0], $array[1], $array[2]],
                $msg,
            );

            if (strpos($msg, ':rule')) {
                $msg = str_replace(':rule', (string)$rule, $msg);
            }
        }

        return $msg;
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

    /**
     * Note: 设置验证规则的默认提示信息
     * Date: 2023-02-11
     * Time: 11:19
     * @param string|array $type 验证规则类型
     * @param string|null $msg 验证提示信息
     * @return void
     */
    public function setTypeMsg($type, string $msg = null)
    {
        if (is_array($type)) {
            $this->typeMsg = array_merge($this->typeMsg, $type);
        } else {
            $this->typeMsg = [$type, $msg];
        }
    }

    /**
     * Note: 验证字段值是否有效格式
     * Date: 2023-01-18
     * Time: 11:54
     * @param mixed $value 字段值
     * @param string $rule 验证规则
     * @param array $data 全部数据
     * @return bool
     */
    public function is($value, string $rule, array $data)
    {
        switch ($rule) {
            case 'require':
                $result = !empty($value) || $value == '0';
                break;
            case 'accepted':
                $result = in_array($value, ['on', 'yes', 1]);
                break;
            case 'date':
                $result = strtotime($value) !== false;
                break;
            case 'url':
                $result = checkdnsrr($value);
                break;
            case 'bool':
            case 'boolean':
                $result = in_array($value, [true, false, 0, 1, '0', '1'], true);
                break;
            case 'number':
                $result = ctype_digit((string)$value);
                break;
            case 'array':
                $result = is_array($value);
                break;
            case 'file':
                $result = $value instanceof File;
                break;
            case 'image':
                $result = $value instanceof File && in_array($this->getImageType($value->getRealPath()), [1, 2, 3, 6]);
                break;
            case 'token':
                $result = $this->token($value, '__token__', $data);
                break;
            default:
                if (isset($this->type[$rule])) {
                    $result = call_user_func($this->type[$rule], $value);
                } elseif (function_exists('ctype_' . $rule)) {
                    $callFunction = 'ctype_' . $rule;
                    $result = $callFunction($value);
                } elseif (isset($this->filter[$rule])) {
                    $result = $this->filter($value, $this->filter[$rule]);
                } else {
                    $result = $this->regex($value, $rule);
                }

        }

        return $result;
    }

    /**
     * Note: 获取图片类型
     * Date: 2023-02-14
     * Time: 17:40
     * @param string $image
     * @return false|int|mixed
     */
    protected function getImageType($image)
    {
        if (function_exists('exif_imagetype')) {
            return exif_imagetype($image);
        }

        try {
            $info = getimagesize($value);
            return $info ? $info[2] : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Note: 验证token令牌
     * Date: 2023-02-14
     * Time: 17:41
     * @param string $value token值
     * @param string $name token字段名
     * @param array $data 请求数据
     * @return bool
     */
    protected function token($value, string $name, array $data)
    {
        $name = !empty($name) ? $name : '__token__';

        return $this->request->checkToken($name, $data);
    }

    /**
     * Note: 使用filter_var方式验证
     * Date: 2023-02-15
     * Time: 17:04
     * @param mixed $value 值
     * @param mixed $rule 规则
     * @return bool
     */
    public function filter($value, $rule)
    {
        if (is_array($rule)) {
            $param = $rule[1] ?? 0;
            $rule = $rule[0];
        } else {
            $param = 0;
        }

        return filter_var($value, is_int($rule) ? $rule : filter_id($rule), $param) !== false;
    }

    /**
     * Note: 使用正则验证
     * Date: 2023-02-15
     * Time: 17:04
     * @param mixed $value 值
     * @param mixed $rule 规则
     * @return bool
     */
    public function regex($value, $rule)
    {
        if (isset($this->regex[$rule])) {
            $rule = $this->regex[$rule];
        } elseif (isset($this->defaultRegex[$rule])) {
            $rule = $this->defaultRegex[$rule];
        }

        if (is_string($rule) && strpos($rule, '/') !== 0 && !preg_match('/\/[imsU]]{0,4}$/', $rule)) {
            $rule = '/^' . $rule . '$/';
        }

        return is_scalar($value) && preg_match($rule, (string)$value) === 1;
    }

    /**
     * Note: 必须验证
     * Date: 2023-02-15
     * Time: 17:23
     * @param mixed $value 字段值
     * @param mixed $rule 验证规则
     * @return bool
     */
    public function must($value, $rule = null)
    {
        return !empty($value) || $value == '0';
    }

    /**
     * Note: 验证是否和某个字段的值一致
     * Date: 2023-02-15
     * Time: 17:39
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @param array $data 数据
     * @param string $field 字段名
     * @return bool
     */
    public function confirm($value, $rule, $data, $field)
    {
        if ($rule == '') {
            if (strpos($field, '_confirm')) {
                $rule = strstr($field, '_confirm', true);
            } else {
                $rule = $field . '_confirm';
            }
        }
        return $this->getDataValue($data, $rule) === $value;
    }

    /**
     * Note: 验证是否和某个字段的值是否不同
     * Date: 2023-02-17
     * Time: 10:44
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @param array $data 数据
     * @return bool
     */
    public function different($value, $rule, array $data = [])
    {
        return $this->getDataValue($data, $rule) !== $value;
    }

    /**
     * Note: 验证是否大于等于某个值
     * Date: 2023-02-17
     * Time: 10:45
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @param array $data 数据
     * @return bool
     */
    public function egt($value, $rule, array $data = [])
    {
        return $value >= $this->getDataValue($data, $rule);
    }

    /**
     * Note: 验证是否大于某个值
     * Date: 2023-02-17
     * Time: 10:49
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @param array $data 数据
     * @return bool
     */
    public function gt($value, $rule, array $data = [])
    {
        return $value > $this->getDataValue($data, $rule);
    }

    /**
     * Note: 验证是否小于等于某个值
     * Date: 2023-02-17
     * Time: 10:49
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @param array $data 数据
     * @return bool
     */
    public function elt($value, $rule, array $data = [])
    {
        return $value <= $this->getDataValue($data, $rule);
    }

    /**
     * Note: 验证是否小于某个值
     * Date: 2023-02-17
     * Time: 10:49
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @param array $data 数据
     * @return bool
     */
    public function lt($value, $rule, array $data = [])
    {
        return $value < $this->getDataValue($data, $rule);
    }

    /**
     * Note: 验证是否等于某个值
     * Date: 2023-02-17
     * Time: 10:49
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @param array $data 数据
     * @return bool
     */
    public function eq($value, $rule, array $data = [])
    {
        return $value == $this->getDataValue($data, $rule);
    }

    /**
     * Note: 验证是否为合法的域名或IP地址
     * Date: 2023-02-17
     * Time: 10:56
     * @param string $value 字段值
     * @param string $rule 规则值
     * @return bool
     */
    public function activeUrl(string $value, string $rule = 'MX')
    {
        if (!in_array($rule, ['A', 'MX', 'NS', 'SOA', 'PTR', 'CNAME', 'AAAA', 'A6', 'SRV', 'NAPTR', 'TXT', 'ANY'])) {
            $rule = 'MX';
        }

        return checkdnsrr($value, $rule);
    }

    /**
     * Note: 验证是否为合法的IP地址
     * Date: 2023-02-17
     * Time: 11:04
     * @param string $value 字段值
     * @param string $rule 规则值
     * @return bool
     */
    public function ip(string $value, string $rule = 'ipv4')
    {
        if (!in_array($rule, ['ipv4', 'ipv5'])) {
            $rule = 'ipv4';
        }

        return $this->filter($value, [FILTER_VALIDATE_IP, $rule == 'ipv6' ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4]);
    }

    /**
     * Note: 验证上传文件后缀
     * Date: 2023-02-17
     * Time: 11:26
     * @param mixed $file 文件
     * @param mixed $rule 规则值
     * @return bool
     */
    public function fileExt($file, $rule)
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                if ((!$item instanceof File) || !$this->checkExt($item, $rule)) {
                    return false;
                }
            }
            return true;
        } elseif ($file instanceof File) {
            return $this->checkExt($file, $rule);
        }

        return false;
    }

    /**
     * Note: 验证上传文件后缀
     * Date: 2023-02-17
     * Time: 11:55
     * @param File $file 文件
     * @param mixed $ext 后缀
     * @return bool
     */
    protected function checkExt(File $file, $ext)
    {
        if (is_string($ext)) {
            $ext = explode(',', $ext);
        }

        return in_array(strtolower($file->extension()), $ext);
    }

    /**
     * Note: 验证上传文件大小
     * Date: 2023-02-17
     * Time: 11:58
     * @param mixed $file 上传文件
     * @param mixed $rule 规则值
     * @return bool
     */
    public function fileSize($file, $rule)
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                if ((!$item instanceof File) || $this->checkSize($item, $rule)) {
                    return false;
                }
            }
            return true;
        } elseif ($file instanceof File) {
            return $this->checkSize($file, $rule);
        }

        return false;
    }

    /**
     * Note: 检测上传文件大小
     * Date: 2023-02-20
     * Time: 16:56
     * @param File $file 文件
     * @param int $size 文件大小
     * @return bool
     */
    protected function checkSize(File $file, $size)
    {
        return $file->getSize() <= (int)$size;
    }

    /**
     * Note: 验证上传文件的MIME值
     * Date: 2023-02-17
     * Time: 11:59
     * @param mixed $file 上传文件
     * @param mixed $rule 规则值
     * @return bool
     */
    public function fileMime($file, $rule)
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                if (!$item instanceof $file || $item->checkMime($item, $rule)) {
                    return false;
                }
            }
            return true;
        } elseif ($file instanceof File) {
            return $this->checkMime($file, $rule);
        }

        return false;
    }

    /**
     * Note: 检测上传文件MIME值
     * Date: 2023-02-20
     * Time: 16:57
     * @param File $file 文件
     * @param mixed $mime
     * @return bool
     */
    protected function checkMime(File $file, $mime)
    {
        if (is_string($mime)) {
            $mime = explode(',', $mime);
        }

        return in_array(strtolower($file->getMime()), $mime);
    }

    /**
     * Note: 验证图片的宽高以及类型
     * Date: 2023-02-20
     * Time: 17:09
     * @param $file
     * @param $rule
     */
    public function image($file, $rule)
    {
        if (!($file instanceof File)) {
            return false;
        }

        if ($rule) {
            [$width, $height, $type] = getimagesize($file->getRealPath());

            if (isset($rule[2])) {

                $imageType = strtolower($rule[2]);

                if (image_type_to_extension($type, false) !== $imageType) {
                    return false;
                }
            }

            [$w, $h] = $rule;

            return $w == $width && $h == $height;
        }

        return in_array($this->getImageType($file->getRealPath()), [1, 2, 3, 6]);
    }

    /**
     * Note: 验证时间和日期是否符合指定格式
     * Date: 2023-02-20
     * Time: 18:03
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @return bool
     */
    public function dateFormat($value, $rule)
    {
        $info = date_parse_from_format($rule, $value);

        return $info['error_count'] == 0 && $info['warning_count'] == 0;
    }

    /**
     * Note: 验证是否唯一
     * Date: 2023-02-20
     * Time: 18:07
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @param array $data 数据
     * @param string $field 字段名称
     * @return bool
     */
    public function unique($value, $rule, array $data = [], string $field = '')
    {
        return true;
    }

    /**
     * Note: 验证是否在列表内
     * Date: 2023-02-20
     * Time: 18:12
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @return bool
     */
    public function in($value, $rule)
    {
        return in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * Note: 验证是否不在列表内
     * Date: 2023-02-20
     * Time: 18:12
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @return bool
     */
    public function notIn($value, $rule)
    {
        return !in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * Note: 验证是否在范围内
     * Date: 2023-02-20
     * Time: 18:14
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @return bool
     */
    public function between($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }

        [$min, $max] = $rule;

        return $value >= $min && $value <= $max;
    }

    /**
     * Note: 验证是否不在范围内
     * Date: 2023-02-20
     * Time: 18:16
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @return bool
     */
    public function notBetween($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }

        [$min, $max] = $rule;

        return $value < $min || $value > $max;
    }

    /**
     * Note: 验证数据长度
     * Date: 2023-02-20
     * Time: 18:20
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @return bool
     */
    public function length($value, $rule)
    {
        if (is_array($value)) {
            $length = count($value);
        } elseif ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen((string)$value);
        }

        if (is_string($rule) && strpos($rule, ',')) {
            [$min, $max] = explode(',', $rule);

            return $length >= $min && $length <= $max;
        }

        return $length == $rule;
    }

    /**
     * Note: 验证数据最大长度
     * Date: 2023-02-20
     * Time: 18:29
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @return bool
     */
    public function max($value, $rule)
    {
        if (is_array($value)) {
            $length = count($value);
        } elseif ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen((string)$value);
        }

        return $length <= $rule;
    }

    /**
     * Note: 验证数据最小长度
     * Date: 2023-02-20
     * Time: 18:29
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @return bool
     */
    public function min($value, $rule)
    {
        if (is_array($value)) {
            $length = count($value);
        } elseif ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen((string)$value);
        }

        return $length >= $rule;
    }

    /**
     * Note: 验证日期否在某个值之后
     * Date: 2023-02-20
     * Time: 18:38
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @param array $data
     * @return bool
     */
    public function after($value, $rule)
    {
        return strtotime($value) >= strtotime($rule);
    }

    /**
     * Note: 验证日期是否在某个值之前
     * Date: 2023-02-20
     * Time: 18:39
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @param array $data
     */
    public function before($value, $rule)
    {
        return strtotime($value) <= strtotime($rule);
    }

    /**
     * Note: 验证有效期
     * Date: 2023-02-20
     * Time: 18:49
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @return bool
     */
    public function expire($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }

        [$start, $end] = $rule;

        if (!is_numeric($value)) {
            $start = strtotime($start);
        }
        if (!is_numeric($value)) {
            $end = strtotime($start);
        }

        return time() >= $start && time() <= $end;
    }

    /**
     * Note: 允许IP许可
     * Date: 2023-02-20
     * Time: 18:52
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @return bool
     */
    public function allowIp($value, $rule)
    {
        return in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * Note: 禁止IP
     * Date: 2023-02-20
     * Time: 18:52
     * @param mixed $value 字段值
     * @param mixed $rule 规则值
     * @return bool
     */
    public function denyIp($value, $rule)
    {
        return !in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * Note: 获取错误值
     * Date: 2023-02-20
     * Time: 18:54
     */
    public function getError()
    {
        return $this->error;
    }

    public function __call($method, $args)
    {
        if (strtolower(substr($method, 0, 2)) == 'is') {
            $method = substr($method, 2);
        }

        array_push($args, lcfirst($method));

        return call_user_func_array([$this, 'is'], $args);
    }

}