<?php
declare(strict_types=1);

namespace Enna\Framework\Console;

class Table
{
    /**
     * 左对齐
     */
    const ALIGN_LEFT = 1;

    /**
     * 右对齐
     */
    const ALIGN_RIGHT = 0;

    /**
     * 居中
     */
    const ALIGN_CENTER = 2;

    /**
     * 头信息数据
     * @var array
     */
    protected $header = [];

    /**
     * 单元格数据(二维数组)
     * @var array
     */
    protected $rows = [];

    /**
     * 头部对齐方式 默认1 ALIGN_LEFT=1 ALIGN_RIGHT=0 ALIGN_CENTER=2
     * @var int
     */
    protected $headerAlign = 1;

    /**
     * 单元格对齐方式 默认1 ALIGN_LEFT=1 ALIGN_RIGHT=0 ALIGN_CENTER=2
     * @var int
     */
    protected $cellAlign = 1;

    /**
     * 单元格宽度信息
     * @var array
     */
    protected $colWidth = [];

    /**
     * 表格输出样式
     * @var string
     */
    protected $style = 'default';

    /**
     * 表格样式定义
     * @var array
     */
    protected $format = [
        'compact' => [],
        'default' => [
            'top' => ['+', '-', '+', '+'],
            'cell' => ['|', ' ', '|', '|'],
            'middle' => ['+', '-', '+', '+'],
            'bottom' => ['+', '-', '+', '+'],
            'cross-top' => ['+', '-', '-', '+'],
            'cross-bottom' => ['+', '-', '-', '+'],
        ],
        'markdown' => [
            'top' => [' ', ' ', ' ', ' '],
            'cell' => ['|', ' ', '|', '|'],
            'middle' => ['|', '-', '|', '|'],
            'bottom' => [' ', ' ', ' ', ' '],
            'cross-top' => ['|', ' ', ' ', '|'],
            'cross-bottom' => ['|', ' ', ' ', '|'],
        ],
        'borderless' => [
            'top' => ['=', '=', ' ', '='],
            'cell' => [' ', ' ', ' ', ' '],
            'middle' => ['=', '=', ' ', '='],
            'bottom' => ['=', '=', ' ', '='],
            'cross-top' => ['=', '=', ' ', '='],
            'cross-bottom' => ['=', '=', ' ', '='],
        ],
        'box' => [
            'top' => ['┌', '─', '┬', '┐'],
            'cell' => ['│', ' ', '│', '│'],
            'middle' => ['├', '─', '┼', '┤'],
            'bottom' => ['└', '─', '┴', '┘'],
            'cross-top' => ['├', '─', '┴', '┤'],
            'cross-bottom' => ['├', '─', '┬', '┤'],
        ],
        'box-double' => [
            'top' => ['╔', '═', '╤', '╗'],
            'cell' => ['║', ' ', '│', '║'],
            'middle' => ['╠', '─', '╪', '╣'],
            'bottom' => ['╚', '═', '╧', '╝'],
            'cross-top' => ['╠', '═', '╧', '╣'],
            'cross-bottom' => ['╠', '═', '╤', '╣'],
        ],
    ];

    /**
     * Note: 设置表格头信息以及对齐方式
     * Date: 2024-02-02
     * Time: 10:45
     * @param array $header 头信息
     * @param int $align 对齐方式
     * @return void
     */
    public function setHeader(array $header, int $align = 1)
    {
        $this->header = $header;
        $this->headerAlign = $align;

        $this->checkColWidth($header);
    }

    /**
     * Note: 设置表格cell信息以及对齐方式
     * Date: 2024-02-02
     * Time: 10:46
     * @param array $rows cell信息
     * @param int $align 对齐方式
     * @return void
     */
    public function setRows(array $rows, int $align = 1)
    {
        $this->rows = $rows;
        $this->cellAlign = $align;

        foreach ($rows as $row) {
            $this->checkColWidth($row);
        }
    }

    /**
     * Note: 增加一行表格数据
     * Date: 2024-02-02
     * Time: 14:48
     * @param array $row 行数据
     * @param bool $first 是否在开头插入
     * @return void
     */
    public function addRow($row, bool $first = false)
    {
        if ($first) {
            array_unshift($this->rows, $row);
        } else {
            $this->rows[] = $row;
        }

        $this->checkColWidth($row);
    }

    /**
     * Note: 设置单元格对齐方式
     * Date: 2024-02-02
     * Time: 14:46
     * @param int $align 对齐方式
     * @return $this|int
     */
    public function setCellAlign(int $align = 1)
    {
        return $this->cellAlign = $align;

        return $this;
    }

    /**
     * Note: 检查单元格的宽度
     * Date: 2024-02-01
     * Time: 18:27
     * @param mixed $row 行数据
     * @return void
     */
    protected function checkColWidth($row)
    {
        if (is_array($row)) {
            foreach ($row as $key => $cell) {
                $width = mb_strwidth((string)$cell);
                if (!isset($this->colWidth[$key]) || $width > $this->colWidth[$key]) {
                    $this->colWidth[$key] = $width;
                }
            }
        }
    }

    /**
     * Note: 设置输出表格的样式
     * Date: 2024-02-02
     * Time: 10:47
     * @param string $style 样式名
     * @return void
     */
    public function setStyle(string $style)
    {
        $this->style = isset($this->format[$style]) ? $style : 'default';
    }

    /**
     * Note: 得到样式
     * Date: 2024-02-02
     * Time: 11:46
     * @param string $style
     * @return mixed|string[]
     */
    protected function getStyle(string $style)
    {
        if ($this->format[$this->style]) {
            return $this->format[$this->style][$style];
        } else {
            return [' ', ' ', ' ', ' '];
        }
    }

    /**
     * Note: 输出表格
     * Date: 2024-02-02
     * Time: 11:11
     * @param array $dataList 表格数据
     * @return string
     */
    public function render(array $dataList = [])
    {
        if (!empty($dataList)) {
            $this->setRows($dataList);
        }

        $content = $this->renderHeader();

        $style = $this->getStyle('cell');

        if (!empty($this->rows)) {
            foreach ($this->rows as $row) {
                if (is_string($row) && $row === '-') {
                    $content .= $this->renderSeparator('middle');
                } elseif (is_scalar($row)) {
                    $content .= $this->renderSeparator('cross-top');

                    $width = 3 * (count($this->colWidth) - 1) + array_reduce($this->colWidth, function ($a, $b) {
                            return $a + $b;
                        });
                    $array = str_pad($row, $width);
                    $content .= $style[0] . ' ' . $array . ' ' . $style[3] . PHP_EOL;

                    $content .= $this->renderSeparator('cross-bottom');
                } else {
                    $array = [];

                    foreach ($row as $key => $val) {
                        $width = $this->colWidth[$key];

                        if (false !== $encoding = mb_detect_encoding((string)$val, null, true)) {
                            $width += strlen((string)$val) - mb_strwidth((string)$val, $encoding);
                        }
                        $array[] = ' ' . str_pad($val, $width, ' ', $this->cellAlign);
                    }

                    $content .= $style[0] . implode(' ' . $style[2], $array) . ' ' . $style[3] . PHP_EOL;
                }
            }
        }

        $content .= $this->renderSeparator('bottom');

        return $content;
    }

    /**
     * Note: 输出表格头部
     * Date: 2024-02-02
     * Time: 14:13
     * @return string
     */
    protected function renderHeader()
    {
        $style = $this->getStyle('cell');
        $content = $this->renderSeparator('top');

        foreach ($this->header as $key => $header) {
            $array[] = ' ' . str_pad($header, $this->colWidth[$key], $style[1], $this->headerAlign);
        }

        if (!empty($array)) {
            $content .= $style[0] . implode(' ' . $style[2], $array) . ' ' . $style[3] . PHP_EOL;

            if (!$this->rows) {
                $content .= $this->renderSeparator('middle');
            }
        }

        return $content;
    }

    /**
     * Note: 输出分隔行
     * Date: 2024-02-02
     * Time: 11:57
     * @param string $pos 位置
     * @return string
     */
    protected function renderSeparator(string $pos)
    {
        $style = $this->getStyle($pos);
        $array = [];
        foreach ($this->colWidth as $width) {
            $array[] = str_repeat($style[1], $width + 2);
        }
        return $style[0] . implode($style[2], $array) . $style[3] . PHP_EOL;
    }
}