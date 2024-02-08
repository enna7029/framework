<?php

namespace Enna\Framework\Console\Command;

use Enna\Framework\Console\Command;
use Enna\Framework\Console\Input\Argument as InputArgument;
use Enna\Framework\Console\Input\Option as InputOption;
use Enna\Framework\Console\Input;
use Enna\Framework\Console\Output;

class Help extends Command
{
    /**
     * 指令
     * @var Command
     */
    private $command;

    /**
     * Note: 配置
     * Date: 2023-12-29
     * Time: 14:49
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this->setName('help')
            ->setDefinition([
                new InputArgument('command_name', InputArgument::OPTIONAL, 'The command name', 'help'),
                new InputOption('raw', null, InputOption::VALUE_NONE, 'To output raw command help'),
            ])
            ->setDescription('Displays help for a command')
            ->setHelp(
                <<<EOL
The <info>%command.name%</info> command displays help for a given command:

  <info>php %command.full_name% list</info>

To display the list of available commands.please use the <info>list</info> command.
EOL
            );
    }

    /**
     * Note: 设置指令
     * Date: 2023-12-19
     * Time: 11:32
     * @param Command $command
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Note: 执行指令
     * Date: 2024-01-17
     * Time: 16:26
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        if ($this->command === null) {
            $this->command = $this->getConsole()->find($input->getArgument('command_name'));
        }

        $output->describe($this->command, [
            'raw_text' => $input->getOption('raw'),
        ]);

        $this->command = null;
    }
}