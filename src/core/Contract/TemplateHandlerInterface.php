<?php
declare(strict_types=1);

namespace Enna\Framework\Contract;

/**
 * 识图驱动接口
 * Interface TemplateHandlerInterface
 * @package Enna\Framework\Contract
 */
interface TemplateHandlerInterface
{
    /**
     * Note: 检测是否存在模板文件
     * Date: 2023-11-30
     * Time: 11:16
     * @param string $template 模板文件或者模板规则
     * @return bool
     */
    public function exists(string $template): bool;

    /**
     * Note: 渲染模板文件
     * Date: 2023-11-30
     * Time: 11:17
     * @param string $template 模板文件
     * @param array $data 模板变量
     * @return void
     */
    public function fetch(string $template, array $data = []): void;

    /**
     * Note: 渲染模板内容
     * Date: 2023-11-30
     * Time: 11:18
     * @param string $content 模板内容
     * @param array $data 模板变量
     * @return void
     */
    public function display(string $content, array $data = []): void;

    /**
     * Note: 配置模板引擎
     * Date: 2023-11-30
     * Time: 11:19
     * @param array $config 参数
     * @return void
     */
    public function config(array $config): void;

    /**
     * Note: 获取模板引擎
     * Date: 2023-11-30
     * Time: 11:20
     * @param string $name 参数名
     * @return mixed
     */
    public function getConfig(string $name);
}