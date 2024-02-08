<?php

namespace Enna\Framework\Console\Command;

use Enna\Framework\Console\Command;
use Enna\Framework\Console\Input;
use Enna\Framework\Console\Input\Argument;
use Enna\Framework\Console\Output;

abstract class Make extends Command
{
    protected $type;

    abstract protected function getStub();

    protected function configure()
    {
        $this->addArgument('name', Argument::REQUIRED, 'The name of the class');
    }

    protected function execute(Input $input, Output $output)
    {
        $name = trim($input->getArgument('name'));

        $classname = $this->getClassName($name);

        $pathname = $this->getPathName($classname);

        if (is_file($pathname)) {
            $output->writeln('<error>' . $this->type . ':' . $classname . ' already exists!' . '</error>');
            return false;
        }

        if (!is_dir(dirname($pathname))) {
            mkdir(dirname($pathname), 0755, true);
        }

        file_put_contents($pathname, $this->buildClass($classname));

        $output->writeln('<info>' . $this->type . ':' . $classname . ' created successfully.</info>');
    }

    /**
     * Note: 构建类
     * Date: 2024-01-31
     * Time: 10:29
     * @param string $name 类名
     * @return string
     */
    protected function buildClass(string $name)
    {
        $stub = file_get_contents($this->getStub());

        $namespace = trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');

        $class = str_replace($namespace . '\\', '', $name);

        return str_replace(['{%className%}', '{%actionSuffix%}', '{%namespace%}', '{%app_namespace%}'], [
            $class,
            $this->app->config->get('route.action_suffix'),
            $namespace,
            $this->app->getNamespace()
        ], $stub);
    }

    /**
     * Note: 解析类名
     * Date: 2024-01-31
     * Time: 10:10
     * @param string $name 类名
     * @return string
     */
    protected function getClassName(string $name)
    {
        if (strpos($name, '\\') !== false) {
            return $name;
        }

        if (strpos($name, '@')) {
            [$app, $name] = explode('@', $name);
        } else {
            $app = '';
        }

        if (strpos($name, '/') !== false) {
            $name = str_replace('/', '\\', $name);
        }

        return $this->getNamespace($app) . '\\' . $name;
    }

    /**
     * Note: 获取应用
     * Date: 2024-01-31
     * Time: 10:11
     * @param string $app 应用名
     * @return string
     */
    protected function getNamespace(string $app)
    {
        return 'app' . ($app ? '\\' . $app : '');
    }

    /**
     * Note: 得到文件路径
     * Date: 2024-01-31
     * Time: 10:45
     * @param string $name 文件名
     * @return string
     */
    protected function getPathName(string $name)
    {
        $name = substr($name, 4);

        return $this->app->getBasePath() . ltrim(str_replace('\\', '/', $name), '/') . '.php';
    }
}