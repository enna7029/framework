<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Cookie;
use Enna\Framework\Facade;
use Enna\Framework\Request;

/**
 * @method static void setLang(string $lang) 设置当前语言
 * @method static string getLang() 获取当前语言
 * @method static mixed|string defaultLang() 获取默认语言
 * @method static array load($file, $lang = '') 加载语言定义
 * @method static bool has(string $name, string $range = '') 判断是否存在语言定义
 * @method static array|string get(string $name = null, array $vars = [], string $range = '') 获取语言定义
 * @method static string detect(Request $request) 自定侦测并设置语言
 * @method static void saveToCookie(Cookie $cookie) 保存当前语言到Cookie
 * Class Lang
 * @package Enna\Framework\Facade
 */
class Lang extends Facade
{
    protected static function getFacadeClass()
    {
        return 'lang';
    }
}