<?php

namespace Enna\Framework\Console\Command;

use Enna\Framework\Console\Command;
use Enna\Framework\Console\Input;
use Enna\Framework\Console\Input\Option;
use Enna\Framework\Console\Output;

class Clear extends Command
{
    protected function configure()
    {
        $this->setName('clear')
            ->addOption('path', 'd', Option::VALUE_OPTIONAL, 'path to clear', null)
            ->addOption('cache', 'c', Option::VALUE_NONE, 'clear cache file')
            ->addOption('log', 'l', Option::VALUE_NONE, 'clear log file')
            ->addOption('dir', 'r', Option::VALUE_NONE, 'clear empty dir')
            ->addOption('expire', 'e', Option::VALUE_NONE, 'clear cache file if cache has expired')
            ->setDescription('Clear runtime file');
    }

    protected function execute(Input $input, Output $output)
    {
        $runtimePath = $this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR;

        if ($input->getOption('cache')) {
            $path = $runtimePath . 'cache';
        } elseif ($input->getOption('log')) {
            $path = $runtimePath . 'log';
        } else {
            $path = $input->getOption('path') ?: $runtimePath;
        }

        $rmdir = $input->getOption('dir') ? true : false;

        $cache_expire = $input->getOption('expire') && $input->getOption('cache') ? true : false;

        $this->clear(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, $rmdir, $cache_expire);

        $output->writeln('<info>Clear Successed</info>');
    }

    /**
     * Note: 清除文件
     * Date: 2024-01-31
     * Time: 17:49
     * @param string $path 目录
     * @param bool $rmdir 是否删除目录
     * @param bool $cache_expire 是否删除过期缓存
     * @return void
     */
    protected function clear(string $path, bool $rmdir, bool $cache_expire)
    {
        $files = is_dir($path) ? scandir($path) : [];

        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && is_dir($path . $file)) {
                $this->clear($path . $file . DIRECTORY_SEPARATOR, $rmdir, $cache_expire);
                if ($rmdir) {
                    @rmdir($path . $file);
                }
            } elseif ($file != '.gitignore' && is_file($path . $file)) {
                if ($cache_expire) {
                    if ($this->cacheHasExpired($path . $file)) {
                        unlink($path . $file);
                    }
                } else {
                    unlink($path . $file);
                }
            }
        }
    }


    /**
     * Note: 缓存文件是否过期
     * Date: 2024-01-31
     * Time: 18:00
     * @param string $filename 文件
     * @return bool
     */
    protected function cacheHasExpired($filename)
    {
        $content = file_get_contents($filename);
        $expire = (int)substr($content, 8, 12);

        return $expire != 0 && time() - $expire > filemtime($filename);
    }
}