<?php

namespace Enna\Framework\Console\Command;

use Enna\Framework\Console\Command;
use Enna\Framework\Console\Input;
use Enna\Framework\Console\Input\Argument;
use Enna\Framework\Console\Input\Option;
use Enna\Framework\Console\Output;
use Enna\Framework\Console\Table;
use Enna\Framework\Event\RouteLoaded;

class RouteList extends Command
{
    protected $sortBy = [
        'rule' => 0,
        'route' => 1,
        'method' => 2,
        'name' => 3,
        'domain' => 4,
    ];

    protected function configure()
    {
        $this->setName('route:list')
            ->addArgument('dir', Argument::OPTIONAL, 'dir name')
            ->addArgument('style', Argument::OPTIONAL, 'the style of the table', 'default')
            ->addOption('sort', 's', Option::VALUE_OPTIONAL, 'order by rule name', 0)
            ->addOption('more', 'm', Option::VALUE_NONE, 'show route options')
            ->setDescription('show route list');
    }

    protected function execute(Input $input, Output $output)
    {
        $dir = $input->getArgument('dir') ?: '';

        if ($dir == 'controller') {
            $dir = false;
        }

        $filename = $this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . ($dir ? $dir . DIRECTORY_SEPARATOR : '') . 'route_list.php';

        if (is_file($filename)) {
            unlink($filename);
        } elseif (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755);
        }

        $content = $this->getRouteList($dir);

        file_put_contents($filename, 'Route list' . PHP_EOL . $content);
    }

    protected function getRouteList(string $dir = null)
    {
        $this->app->route->clear();
        $this->app->route->lazy(false);

        if ($dir) {
            $path = $this->app->getRootPath() . 'route' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR;
        } else {
            $path = $this->app->getRootPath() . 'route' . DIRECTORY_SEPARATOR;
        }

        $files = is_dir($path) ? scandir($path) : [];
        foreach ($files as $file) {
            if (strpos($file, '.php')) {
                include $path . $file;
            }
        }

        //触发路由加载完毕事件
        $this->app->event->trigger(RouteLoaded::class);

        $table = new Table();

        if ($this->input->hasOption('more')) {
            $header = ['Rule', 'Route', 'Method', 'Name', 'Domain', 'Option', 'Pattern'];
        } else {
            $header = ['Rule', 'Route', 'Method', 'Name'];
        }

        $table->setHeadr($header);

        $rows = [];
        $routeList = $this->app->route->getRuleList();
        foreach ($routeList as $item) {
            $item['route'] = $item['route'] instanceof \Closure ? '<Closure>' : $item['route'];

            $row = [$item['rule'], $item['route'], $item['method'], $item['name']];

            if ($this->input->hasOption('more')) {
                array_push($row, $item['domain'], $item['option'], $item['pattern']);
            }

            $rows[] = $row;
        }

        if ($this->input->getOption('sort')) {
            $sort = strtolower($this->input->getOption('sort'));

            if (isset($this->sortBy[$sort])) {
                $sort = $this->sortBy[$sort];
            }

            uasort($rows, function ($a, $b) use ($sort) {
                $itemA = $a[$sort] ?? null;
                $itemB = $b[$sort] ?? null;

                return strcasecmp($itemA, $itemB);
            });
        }

        $table->setRows($rows);

        if ($this->input->getArgument('style')) {
            $style = $this->input->getArgument('style');
            $table->setStyle($style);
        }

        $content = $table->render();

        $this->output->writeln($content);

        return $content;
    }
}