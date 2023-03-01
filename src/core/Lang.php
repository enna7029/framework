<?php
declare(strict_types=1);

namespace Enna\Framework;

class Lang
{
    protected $app;

    /**
     * 多语言信息
     * @var array
     */
    protected $lang = [];

    /**
     * 语言配置
     * @var array
     */
    protected $config = [
        // 默认语言
        'default_lang' => 'zh-cn',
        //是否语言分组
        'allow_group' => false,
        //扩展语言包
        'extend_list' => [],
        //允许的语言列表
        'allow_lang_list' => [],
        //是否使用cookie记录
        'use_cookie' => true,
        //cookie语言变量名
        'cookie_var' => 'enna_lang',
        //header语言变量名
        'header_var' => 'enna-lang',
        //语言自动侦测变量名
        'detect_var' => 'lang',
    ];

    /**
     * 当前语言
     * @var string
     */
    private $range = 'zh-cn';

    public function __construct(App $app, array $config = [])
    {
        $this->app = $app;
        $this->config = array_merge($this->config, $config);
        $this->range = $this->config['default_lang'];
    }

    /**
     * Note: 设置当前语言
     * Date: 2023-02-24
     * Time: 16:19
     * @param string $lang 语言
     * @return void
     */
    public function setLang(string $lang)
    {
        $this->range = $lang;
    }

    /**
     * Note: 获取当前语言
     * Date: 2023-02-24
     * Time: 16:20
     * @return string
     */
    public function getLang()
    {
        return $this->range;
    }

    /**
     * Note: 获取默认语言
     * Date: 2022-09-17
     * Time: 15:43
     * @return mixed|string
     */
    public function defaultLang()
    {
        return $this->config['default_lang'];
    }

    /**
     * Note: 加载语言定义
     * Date: 2022-09-17
     * Time: 16:47
     * @param array|string $file 语言文件
     * @param string $lang 语言作用域
     * @return array
     */
    public function load(array $file, $lang = '')
    {
        $range = $lang ?: $this->range;
        if (!isset($this->lang[$range])) {
            $this->lang[$range] = [];
        }

        $lang = [];
        foreach ((array)$file as $name) {
            if (is_file($name)) {
                $result = $this->parse($name);
                $lang = array_change_key_case($result) + $lang;
            }
        }

        if (!empty($lang)) {
            $this->lang[$range] = $lang;
        }

        return $this->lang[$range];
    }

    /**
     * Note: 解析语言文件
     * Date: 2022-09-17
     * Time: 17:03
     * @param string $file 语言文件名
     * @return array
     */
    public function parse(string $file)
    {
        $type = pathinfo($file, PATHINFO_EXTENSION);

        $result = [];
        switch ($type) {
            case 'php':
                $result = include $file;
                break;
            case 'yaml':
            case 'yml':
                if (function_exists('yaml_parse_file')) {
                    $result = yaml_parse_file($file);
                }
                break;
            case 'ini':
                break;
            case 'json':
                $data = file_get_contents($file);

                if ($data !== false) {
                    $data = json_decode($data, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $result = $data;
                    }
                }
                break;
        }

        return isset($result) && is_array($result) ? $result : [];
    }

    /**
     * Note: 判断是否存在语言定义
     * Date: 2023-02-11
     * Time: 10:26
     * @param string $name 语言变量
     * @param string $range 语言作用域
     * @return bool
     */
    public function has(string $name, string $range = '')
    {
        $range = $range ?: $this->range;
        if ($this->config['allow_group'] && strpos($name, '.')) {
            [$name1, $name2] = explode(',', $name, 2);

            return isset($this->lang[$range][strtolower($name1)][$name2]);
        }

        return $this->lang[$range][strtolower($name)];
    }

    /**
     * Note: 获取语言定义
     * Date: 2023-02-24
     * Time: 17:00
     * @param string $name 语言变量
     * @param array $vars 变量替换
     * @param string $range 语言作用域
     * @return array|string
     */
    public function get(string $name = null, array $vars = [], string $range = '')
    {
        $range = $range ?: $this->range;

        if (is_null($name)) {
            return $this->lang[$range] ?? [];
        }

        if ($this->config['allow_group'] && strpos($name, '.')) {
            [$name1, $name2] = explode(',', $name, 2);

            $value = $this->lang[$range][strtolower($name1)][$name2] ?? $name;
        } else {
            $value = $this->lang[$range][strtolower($name)] ?? $name;
        }

        if (!empty($value) && is_array($vars)) {
            if (key($vars) == 0) {
                array_unique($vars, $value);

                $value = call_user_func_array('sprintf', $vars);
            } else {
                $replace = array_keys($vars);
                foreach ($replace as &$v) {
                    $v = "{:{$v}}";
                }

                $value = str_replace($replace, $vars, $value);
            }
        }

        return $value;
    }

    /**
     * Note: 自定侦测并设置语言
     * Date: 2023-02-27
     * Time: 17:39
     * @param Request $request
     * @return string
     */
    public function detect(Request $request)
    {
        $lang = '';

        if ($request->get($this->config['detect_var'])) {
            $lang = strtolower($request->get($this->config['detect_var']));
        } elseif ($request->header($this->config['header_var'])) {
            $lang = strtolower($request->header($this->config['header_var']));
        } elseif ($request->cookie($this->config['cookie_var'])) {
            $lang = strtolower($request->cookie($this->config['cookie_var']));
        } elseif ($request->server('HTTP_ACCEPT_LANGUAGE')) {
            preg_match('/^([a-z\d\-])/i', $request->server('HTTP_ACCEPT_LANGUAGE'), $matches);
            if ($matches) {
                $lang = strtolower($matches[1]);
            }
        }

        if (empty($this->config['allow_lang_list']) || in_array($lang, $this->config['allow_lang_list'])) {
            $this->range = $lang;
        }

        return $this->range;
    }

    /**
     * Note: 保存当前语言到Cookie
     * Date: 2023-02-27
     * Time: 17:45
     * @param Cookie $cookie Cookie对象
     * @return void
     */
    public function saveToCookie(Cookie $cookie)
    {
        if ($this->config['use_cookie']) {
            $cookie->set($this->config['cookie_var'], $this->range);
        }
    }
}