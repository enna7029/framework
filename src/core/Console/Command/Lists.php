<?php

namespace Enna\Framework\Console\Command;

use Enna\Framework\Console\Command;
use Enna\Framework\Console\Input;
use Enna\Framework\Console\Output;
use Enna\Framework\Console\Input\Definition;
use Enna\Framework\Console\Input\Argument;
use Enna\Framework\Console\Input\Option;

class Lists extends Command
{
    protected function configure()
    {
        $this->setName('list')
            ->setDefinition($this->createDefinition())
            ->setDescription('Lists commands')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command lists all commands:

  <info>php %command.full_name%</info>

You can also display the commands for a specific namesapce:

  <info>php %command.full_name% test</info>

It's also possible to get raw list of commands (useful for embedding command runner):
  
  <info>php %command.full_name% --raw</info>
EOF
            );
    }

    protected function execute(Input $input, Output $output)
    {
        $output->describe($this->getConsole(), [
            'raw_text' => $input->getOption('raw'),
            'namespace' => $input->getArgument('namespace'),
        ]);
    }

    /**
     * Note: 创建定义对象
     * Date: 2024-01-30
     * Time: 15:30
     * @return Definition
     */
    private function createDefinition()
    {
        return new Definition([
            new Argument('namespace', Argument::OPTIONAL, 'The namespace name'),
            new Option('raw', null, Option::VALUE_NONE, 'To output raw command list'),
        ]);
    }
}