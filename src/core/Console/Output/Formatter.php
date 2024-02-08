<?php

namespace Enna\Framework\Console\Output;

use Enna\Framework\Console\Output\Formatter\Style;
use Enna\Framework\Console\Output\Formatter\Stack as StyleStack;

class Formatter
{
    /**
     * 是否允许装饰
     * @var bool
     */
    private $decorated = false;

    /**
     * 样式
     * @var array
     */
    private $styles = [];

    /**
     * 堆栈对象
     * @var
     */
    private $styleStack;

    public function __construct()
    {
        $this->setStyle('error', new Style('white', 'red'));
        $this->setStyle('info', new Style('green'));
        $this->setStyle('comment', new Style('yellow'));
        $this->setStyle('question', new Style('black', 'cyan'));
        $this->setStyle('highlight', new Style('red'));
        $this->setStyle('warning', new Style('black', 'yellow'));

        $this->styleStack = new StyleStack();
    }

    /**
     * Note: 设置外观标识
     * Date: 2024-01-19
     * Time: 18:23
     * @param bool $decorated 是否美化文字
     */
    public function setDecorated($decorated)
    {
        $this->decorated = (bool)$decorated;
    }

    /**
     * Note: 获取外观标识
     * Date: 2024-01-25
     * Time: 9:38
     * @return bool
     */
    public function isDecorated()
    {
        return $this->decorated;
    }

    /**
     * Note: 添加一个新样式
     * Date: 2024-01-25
     * Time: 9:39
     * @param string $name 样式名
     * @param Style $style 样式实例
     */
    public function setStyle($name, Style $style)
    {
        $this->styles[strtolower($name)] = $style;
    }

    /**
     * Note: 是否有指定的样式
     * Date: 2024-01-25
     * Time: 9:42
     * @param string $name 样式名
     * @return bool
     */
    public function hasStyle($name)
    {
        return isset($this->styles[strtolower($name)]);
    }

    /**
     * Note: 获取一个样式
     * Date: 2024-01-25
     * Time: 9:42
     * @param string $name 样式名
     * @return mixed
     */
    public function getStyle($name)
    {
        if (!$this->hasStyle($name)) {
            throw new \InvalidArgumentException(sprintf('Undefined style: %s', $name));
        }

        return $this->styles[strtolower($name)];
    }

    /**
     * Note: 使用所给的样式格式化文字
     * Date: 2024-01-24
     * Time: 18:44
     * @param string $message 字符串
     * @return string
     */
    public function format($message)
    {
        $offset   = 0;
        $output   = '';
        $tagRegex = '[a-z][a-z0-9_=;-]*';
        preg_match_all("#<(($tagRegex) | /($tagRegex)?)>#isx", $message, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $i => $match) {
            $pos  = $match[1];
            $text = $match[0];

            if (0 != $pos && '\\' == $message[$pos - 1]) {
                continue;
            }

            $output .= $this->applyCurrentStyle(substr($message, $offset, $pos - $offset));
            $offset = $pos + strlen($text);

            if ($open = '/' != $text[1]) {
                $tag = $matches[1][$i][0];
            } else {
                $tag = $matches[3][$i][0] ?? '';
            }


            if (!$open && !$tag) {
                // </>
                $this->styleStack->pop();
            } elseif (false === $style = $this->createStyleFromString(strtolower($tag))) {
                $output .= $this->applyCurrentStyle($text);
            } elseif ($open) {
                $this->styleStack->push($style);
            } else {
                $this->styleStack->pop($style);
            }
        }

        $output .= $this->applyCurrentStyle(substr($message, $offset));

        return str_replace('\\<', '<', $output);
    }

    /**
     * Note: 根据字符串设置新的样式实例
     * Date: 2024-01-26
     * Time: 15:33
     * @param string $string
     * @return Style|false|mixed
     */
    private function createStyleFromString($string)
    {
        if (isset($this->styles[$string])) {
            return $this->styles[$string];
        }

        if (!preg_match_all('/([^=]+)=([^;]+)(;|$)/', strtolower($string), $matches, PREG_SET_ORDER)) {
            return false;
        }

        $style = new Style();
        foreach ($matches as $match) {
            array_shift($match);

            if ($match[0] == 'fg') {
                $style->setForeground($match[1]);
            } elseif ($match[0] == 'bg') {
                $style->setBackground($match[1]);
            } else {
                try {
                    $style->setOption($match[1]);
                } catch (\InvalidArgumentException $e) {
                    return false;
                }
            }
        }

        return $style;
    }

    /**
     * Note: 从堆栈应用样式到文字
     * Date: 2024-01-25
     * Time: 17:55
     * @param string $text 文字
     * @return string
     */
    private function applyCurrentStyle($text)
    {
        return $this->isDecorated() && strlen($text) > 0 ? $this->styleStack->getCurrent()->apply($text) : $text;
    }

    /**
     * Note: 获取样式堆栈
     * Date: 2024-01-26
     * Time: 16:14
     * @return StyleStack
     */
    public function getStyleStack()
    {
        return $this->styleStack;
    }

}