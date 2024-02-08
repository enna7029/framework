<?php

namespace Enna\Framework\Console\Output\Formatter;

class Style
{
    /**
     * 可用的字体颜色
     * @var \int[][]
     */
    protected static $availableForegroundColors = [
        'black' => ['set' => 30, 'unset' => 39],
        'red' => ['set' => 31, 'unset' => 39],
        'green' => ['set' => 32, 'unset' => 39],
        'yellow' => ['set' => 33, 'unset' => 39],
        'blue' => ['set' => 34, 'unset' => 39],
        'magenta' => ['set' => 35, 'unset' => 39],
        'cyan' => ['set' => 36, 'unset' => 39],
        'white' => ['set' => 37, 'unset' => 39],
    ];

    /**
     * 可用的背景颜色
     * @var \int[][]
     */
    protected static $availableBackgroundColors = [
        'black' => ['set' => 40, 'unset' => 49],
        'red' => ['set' => 41, 'unset' => 49],
        'green' => ['set' => 42, 'unset' => 49],
        'yellow' => ['set' => 43, 'unset' => 49],
        'blue' => ['set' => 44, 'unset' => 49],
        'magenta' => ['set' => 45, 'unset' => 49],
        'cyan' => ['set' => 46, 'unset' => 49],
        'white' => ['set' => 47, 'unset' => 49],
    ];

    /**
     * 可用的字体样式
     * @var \int[][]
     */
    protected static $availableOptions = [
        'bold' => ['set' => 1, 'unset' => 22],
        'underscore' => ['set' => 4, 'unset' => 24],
        'blink' => ['set' => 5, 'unset' => 25],
        'reverse' => ['set' => 7, 'unset' => 27],
        'conceal' => ['set' => 8, 'unset' => 28],
    ];

    /**
     * 字体颜色
     * @var array
     */
    private $foreground;

    /**
     * 背景颜色
     * @var array
     */
    private $background;

    /**
     * 设置字体样式
     * @var array
     */
    private $options = [];

    public function __construct($foreground = null, $background = null, array $options = [])
    {
        if ($foreground != null) {
            $this->setForeground($foreground);
        }

        if ($background != null) {
            $this->setBackground($background);
        }

        if (count($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Note: 设置字体颜色
     * Date: 2024-01-26
     * Time: 10:14
     * @param string|null $color
     * @throws \InvalidArgumentException
     */
    public function setForeground($color = null)
    {
        if ($color === null) {
            $this->foreground = null;

            return;
        }

        if (!isset(static::$availableForegroundColors[$color])) {
            throw new \InvalidArgumentException(sprintf('Invalid foreground color specified: "%s". Expected one of (%s)', $color, implode(',', array_keys(static::$availableForegroundColors))));
        }

        $this->foreground = static::$availableForegroundColors[$color];
    }

    /**
     * Note: 设置背景
     * Date: 2024-01-26
     * Time: 10:28
     * @param string|null $color
     * @throws \InvalidArgumentException
     */
    public function setBackground($color = null)
    {
        if ($color === null) {
            $this->background = null;
        }

        if (!isset(static::$availableBackgroundColors[$color])) {
            throw new \InvalidArgumentException(sprintf('Invalid background color specified: "%s". Expected one of (%s)', $color, implode(',', array_keys(static::$availableBackgroundColors))));
        }

        $this->background = static::$availableBackgroundColors[$color];
    }

    /**
     * Note: 批量设置字体样式
     * Date: 2024-01-26
     * Time: 10:39
     * @param array $option
     */
    public function setOptions(array $options)
    {
        $this->options = [];

        foreach ($options as $option) {
            $this->setOption($option);
        }
    }

    /**
     * Note: 设置字体样式
     * Date: 2024-01-26
     * Time: 10:40
     * @param string $option 样式名
     * @throws \InvalidArgumentException
     */
    public function setOption(string $option)
    {
        if (!isset(static::$availableOptions[$option])) {
            throw new \InvalidArgumentException(sprintf('Invalid option specified "%s".Excpeted one of (%s)', $option, implode(',', static::$availableOptions)));
        }

        if (!in_array(static::$availableOptions[$option], $this->options)) {
            $this->options[] = static::$availableOptions[$option];
        }
    }

    /**
     * Note: 重置字体样式
     * Date: 2024-01-26
     * Time: 10:51
     * @param string $option 样式名
     * @throws \InvalidArgumentException
     */
    public function unsetOption(string $option)
    {
        if (!isset(static::$availableOptions[$option])) {
            throw new \InvalidArgumentException(sprintf('Invalid option specified "%s".Excpeted one of (%s)', $option, implode(',', static::$availableOptions)));
        }

        $pos = array_search(static::$availableOptions[$option], $this->options);
        if ($pos !== false) {
            unset($this->options[$pos]);
        }
    }

    /**
     * Note: 应用样式到文字
     * Date: 2024-01-26
     * Time: 10:54
     * @param string $text 文字
     * @return string
     */
    public function apply(string $text)
    {
        $setCodes = [];
        $unsetCodes = [];

        if ($this->foreground !== null) {
            $setCodes[] = $this->foreground['set'];
            $unsetCodes[] = $this->foreground['unset'];
        }

        if ($this->background !== null) {
            $setCodes[] = $this->background['set'];
            $unsetCodes[] = $this->background['unset'];
        }

        if (count($this->options)) {
            foreach ($this->options as $option) {
                $setCodes[] = $option['set'];
                $unsetCodes[] = $option['unset'];
            }
        }

        if (count($setCodes) === 0) {
            return $text;
        }
        
        return sprintf("\033[%sm%s\033[%sm", implode(';', $setCodes), $text, implode(';', $unsetCodes));
    }
}