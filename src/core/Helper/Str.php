<?php
declare(strict_types=1);

namespace Enna\Framework\Helper;

class Str
{
    protected static $snakeCache = [];

    protected static $camelCache = [];

    protected static $studlyCache = [];

    /**
     * Note: 检查字符串中是否包含某些字符串
     * Date: 2023-04-07
     * Time: 17:21
     * @param string $haystack
     * @param array|string $needles
     * @return bool
     */
    public static function contains(string $haystack, $needles)
    {
        foreach ((array)$haystack as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Note: 获取字符串长度
     * Date: 2023-04-07
     * Time: 17:25
     * @param string $value
     * @return int
     */
    public static function length(string $value)
    {
        return mb_strlen($value);
    }

    /**
     * Note: 截取字符串
     * Date: 2023-04-07
     * Time: 17:27
     * @param string $string
     * @param int $start
     * @param int|null $length
     * @return string
     */
    public static function substr(string $string, int $start, int $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    /**
     * Note: 字符串转小写
     * Date: 2023-04-07
     * Time: 18:12
     * @param string $value
     * @return string
     */
    public static function lower(string $value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Note: 字符串转大写
     * Date: 2023-04-07
     * Time: 18:13
     * @param string $value
     * @return string
     */
    public static function upper(string $value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Note: 获取指定长度的随机字符串:数字,字母,汉字
     * Date: 2023-04-07
     * Time: 18:14
     * @param int $length
     * @param int|null $type
     * @param string $addChars
     * @return string
     */
    public static function random(int $length = 6, int $type = null, string $addChars = '')
    {
        $str = '';
        switch ($type) {
            case 0:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 1:
                $chars = str_repeat('0123456789', 3);
                break;
            case 2:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
                break;
            case 3:
                $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 4:
                $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书" . $addChars;
                break;
            default:
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
                break;
        }

        if ($length > 10) {
            $chars = $type == 1 ? str_repeat($chars, $length) : str_repeat($chars, 5);
        }

        if ($type != 4) {
            $chars = str_shuffle($chars);
            $str = substr($chars, 0, $length);
        } else {
            for ($i = 0; $i < $length; $i++) {
                $str .= mb_substr($chars, floor(mt_rand(0, mb_strlen($chars, 'UTF-8') - 1)), 1);
            }
        }

        return $str;
    }

    /**
     * Note: 检查字符串是否以某些字符开头
     * Date: 2023-04-07
     * Time: 18:27
     * @param string $hayStack
     * @param array|string $needles
     * @return bool
     */
    public static function startsWith(string $hayStack, $needles)
    {
        foreach ($needles as $needle) {
            if ($needle != '' && mb_strpos($hayStack, $needle) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Note: 检查字符串是否以某些字符结尾
     * Date: 2023-04-07
     * Time: 18:29
     * @param string $hayStack
     * @param $needles
     * @return bool
     */
    public static function endsWith(string $hayStack, $needles)
    {
        foreach ($needles as $needle) {
            if ((string)$needle === static::substr($hayStack, -static::length($needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Note: 转为首字母大写的标题格式
     * Date: 2023-04-07
     * Time: 18:32
     * @param string $value
     * @return string
     */
    public static function title(string $value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Note: 驼峰转下划线
     * Date: 2023-04-07
     * Time: 18:34
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    public static function snake(string $value, string $delimiter = '_')
    {
        $key = $value;

        if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        }

        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return static::$snakeCache[$key][$delimiter] = $value;
    }

    /**
     * Note: 下划线转驼峰(首字母小写)
     * Date: 2023-04-07
     * Time: 18:34
     * @param string $value
     * @return string
     */
    public static function camel(string $value)
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }

        return static::$camelCache[$value] = lcfirst(static::studly($value));
    }

    /**
     * Note: 下划线转驼峰(首字母大写)
     * Date: 2023-04-07
     * Time: 18:35
     * @param string $value
     * @return string
     */
    public static function studly(string $value)
    {
        $key = $value;

        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return static::$studlyCache[$key] = str_replace(' ', '', $value);
    }
}