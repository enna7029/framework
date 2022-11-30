<?php
declare(strict_types=1);

namespace Enna\Framework;

class Lang
{
    protected $app;

    /**
     * 语言信息
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
    ];

    /**
     * 当前语言
     * @var string
     */
    protected $current_lang;

    public function __construct(App $app, array $config = [])
    {
        $this->app = $app;
        $this->config = array_merge($this->config, $config);
        $this->current_lang = $this->config['default_lang'];
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
     * Note: 加载语言
     * Date: 2022-09-17
     * Time: 15:59
     * @param string $lang
     */
    public function loadLang(string $lang)
    {
        if (empty($lang)) {
            return;
        }

        $this->setLang($lang);

        $this->load([
            $this->app->getCorePath() . 'lang' . DIRECTORY_SEPARATOR . '.php',
        ]);
    }

    /**
     * Note: 加载语言定义
     * Date: 2022-09-17
     * Time: 16:47
     * @param array $file
     * @param string $lang
     */
    public function load(array $file, $lang = '')
    {
        $current_lang = $lang ?: $this->current_lang;
        if (!isset($this->lang[$current_lang])) {
            $this->lang[$current_lang] = [];

        }

        $lang = [];
        foreach ($file as $name) {
            if (is_file($name)) {
                $result = $this->parse($name);
                $lang[] = array_change_key_case($result);
            }
        }

        if (!empty($lang)) {
            $this->lang[$current_lang] = $lang;
        }

        return $this->lang[$current_lang];
    }

    /**
     * Note: 解析语言文件
     * Date: 2022-09-17
     * Time: 17:03
     * @param string $file 文件
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
                break;
            case 'ini':
                break;
            case 'json':
                break;
        }

        return isset($result) && is_array($result) ? $result : [];
    }

    /**
     * Note: 设置当前语言
     * Date: 2022-09-17
     * Time: 16:01
     * @param $lang
     */
    public function setLang($lang)
    {
        $this->current_lang = $lang;
    }

    /**
     * Note: 设置当前语言
     * Date: 2022-09-17
     * Time: 16:01
     */
    public function getLang()
    {
        return $this->current_lang;
    }
}