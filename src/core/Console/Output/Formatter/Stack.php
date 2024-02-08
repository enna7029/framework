<?php

namespace Enna\Framework\Console\Output\Formatter;

class Stack
{
    /**
     * 样式对象
     * @var Style|null
     */
    private $emptyStyle;

    /**
     * 样式对象集合
     * @var Style[]
     */
    private $styles;

    public function __construct(Style $style = null)
    {
        $this->emptyStyle = $style ?: new Style();
        $this->reset();
    }

    /**
     * Note: 重置堆栈
     * Date: 2024-01-25
     * Time: 10:03
     */
    public function reset()
    {
        $this->styles = [];
    }

    /**
     * Note: 推一个样式到堆栈
     * Date: 2024-01-26
     * Time: 11:15
     * @param Style $style
     */
    public function push(Style $style)
    {
        $this->styles[] = $style;
    }

    /**
     * Note: 从堆栈中弹出一个样式
     * Date: 2024-01-26
     * Time: 14:51
     * @param Style|null $style
     * @return Style|null
     */
    public function pop(Style $style = null)
    {
        if (empty($this->styles)) {
            return $this->emptyStyle;
        }

        if ($style === null) {
            return array_pop($this->styles);
        }

        foreach (array_reverse($this->styles, true) as $index => $stackedStyle) {
            if ($style->apply('') === $stackedStyle->apply('')) {
                $this->styles = array_slice($this->styles, 0, $index);

                return $stackedStyle;
            }
        }

        throw new \InvalidArgumentException('Incorrectly nested style tag found.');
    }

    /**
     * Note: 计算堆栈当前的样式
     * Date: 2024-01-26
     * Time: 15:02
     * @return Style|null
     */
    public function getCurrent()
    {
        if (empty($this->styles)) {
            return $this->emptyStyle;
        }

        return $this->styles[count($this->styles) - 1];
    }

    /**
     * Note: 设置样式对象
     * Date: 2024-01-26
     * Time: 11:26
     * @param Style $emptyStyle
     * @return $this
     */
    public function setEmptyStyle(Style $emptyStyle)
    {
        $this->emptyStyle = $emptyStyle;

        return $this;
    }

    /***
     * Note: 得到样式对象
     * Date: 2024-01-26
     * Time: 11:25
     * @return Style|null
     */
    public function getEmptyStyle()
    {
        return $this->emptyStyle;
    }
}