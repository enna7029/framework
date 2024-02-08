<?php

namespace Enna\Framework\Console\Command;

use Enna\Framework\Console\Command;
use Enna\Framework\Console\Input;
use Enna\Framework\Console\Output;

class Version extends Command
{
    protected function configure()
    {
        $this->setName('version')
            ->setDescription('show Enna framework verson');
    }

    public function execute(Input $input, Output $output)
    {
        $output->writeln('v' . $this->app->version());
    }
}