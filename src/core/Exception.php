<?php
declare(strict_types=1);

namespace Enna\Framework;

/**
 * 异常基础类
 * Class Exception
 * @package Enna\Framework
 */
class Exception extends \Exception
{
    /**
     * 保存debug数据
     * @var array
     */
    protected $data = [];

    /**
     * Note: 设置额外的debug数据
     * Date: 2022-09-20
     * Time: 11:18
     * @param string $label
     * @param array $data
     */
    final protected function setData(string $label, array $data)
    {
        $this->data[$label] = $data;
    }

    /**
     * Note: 获取debug数据
     * Date: 2022-09-20
     * Time: 11:27
     * @return array
     */
    final public function getData()
    {
        return $this->data;
    }
}