<?php

namespace Enna\Framework\Console\Command;

use Enna\Framework\Console\Command;
use Enna\Framework\Console\Input;
use Enna\Framework\Console\Input\Option;
use Enna\Framework\Console\Output;

class RunServer extends Command
{
    public function configure()
    {
        $this->setName('run')
            ->addOption('host', 'H', Option::VALUE_OPTIONAL, 'The host to server the application on', '0.0.0.0')
            ->addOption('port', 'p', Option::VALUE_OPTIONAL, 'The port to server the application on', 8000)
            ->addOption('root', 'r', Option::VALUE_OPTIONAL, 'The document root of the application', '')
            ->setDescription('PHP Built-in Server');
    }

    public function execute(Input $input, Output $output)
    {
        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $root = $input->getOption('root');
        if (empty($root)) {
            $root = $this->app->getRootPath() . 'public';
        }

        $command = sprintf('php -S %s:%d -t %s %s', $host, $port, escapeshellarg($root), escapeshellarg($root . DIRECTORY_SEPARATOR . 'router.php'));

        $output->writeln(sprintf('Enna Development server is started On <http://%s:%s>', $host, $port));
        $output->writeln(sprintf('You can exit with <info>`CTRL-C`</info>'));
        $output->writeln(sprintf('Document root is: %s', $root));
        passthru($command);
    }
}