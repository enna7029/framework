<?php

namespace Enna\Framework\Console\Command\Optimize;


use Enna\Framework\Console\Command;
use Enna\Framework\Console\Input;
use Enna\Framework\Console\Input\Argument;
use Enna\Framework\Console\Output;
use Enna\Framework\Event\RouteLoaded;

class Route extends Command
{
    protected function configure()
    {
        $this->setName('optimize:route')
            ->addArgument('dir', Argument::OPTIONAL, 'dir name')
            ->setDescription('Build app route cache.');
    }

    protected function execute(Input $input, Output $output)
    {
        $dir = $input->getArgument('dir') ?: '';

        $path = $this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . ($dir ? $dir . DIRECTORY_SEPARATOR : '');

        $filename = $path . 'route.php';
        if (is_file($filename)) {
            unlink($filename);
        }

        file_put_contents($filename, $this->buildRouteCache($dir));

        $output->writeln('<info>Succeed!</info>');
    }

    protected function buildRouteCache(string $dir = null)
    {
        $this->app->route->clear();
        $this->app->route->lazy(false);

        $path = $this->app->getRootPath() . ($dir ? 'app' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR : '') . 'route' . DIRECTORY_SEPARATOR;

        $files = is_dir($path) ? scandir($path) : [];
       
        foreach ($files as $file) {
            if (strpos($file, '.php')) {
                include $path . $file;
            }
        }
        
        $this->app->event->trigger(RouteLoaded::class);
        $rules = $this->app->route->getName();

        return '<?php ' . PHP_EOL . 'return unserialize(\'' . serialize($rules) . '\');';
    }
}