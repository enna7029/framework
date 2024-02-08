<?php

namespace Enna\Framework\Console\Output;

use Enna\Framework\Console;
use Enna\Framework\Console\Command;
use Enna\Framework\Console\Input\Definition;
use Enna\Framework\Console\Input\Option;
use Enna\Framework\Console\Output;
use Enna\Framework\Console\Input\Argument;
use Enna\Framework\Console\Output\Descriptor\Console as ConsoleDescriptor;

class Descriptor
{
    /**
     * 输出对象
     * @var Output
     */
    protected $output;

    public function describe(Output $output, $object, array $options = [])
    {
        $this->output = $output;

        switch (true) {
            case $object instanceof Argument:
                $this->describeArgument($object, $options);
                break;
            case $object instanceof Option:
                $this->describeOption($object, $options);
                break;
            case $object instanceof Definition:
                $this->describeDefinition($object, $options);
                break;
            case $object instanceof Command:
                $this->describeCommand($object, $options);
                break;
            case $object instanceof Console:
                $this->describeConsole($object, $options);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Object of type "%s" is not describable.', get_class($object)));
        }
    }

    /**
     * Note: 描述指令
     * Date: 2024-01-26
     * Time: 17:38
     * @param Command $command 指令
     * @param array $options 选项
     */
    protected function describeCommand(Command $command, array $options = [])
    {
        $command->getSynopsis(true);
        $command->getSynopsis(false);
        $command->mergeConsoleDefinition(false);

        $this->writeText('<comment>Usage:</comment>', $options);
        foreach (array_merge([$command->getSynopsis(true)], $command->getAliases(), $command->getUsages()) as $usage) {
            $this->writeText("\n");
            $this->writeText('  ' . $usage, $options);
        }
        $this->writeText("\n");

        $definition = $command->getNativeDefinition();
        if ($definition->getOptions() || $definition->getArguments()) {
            $this->writeText("\n");
            $this->describeDefinition($definition, $options);
            $this->writeText("\n");
        }

        if ($help = $command->getProcessedHelp()) {
            $this->writeText("\n");
            $this->writeText('<comment>Help:</comment>', $options);
            $this->writeText("\n");
            $this->writeText(' ' . str_replace("\n", "\n ", $help), $options);
            $this->writeText("\n");
        }
    }

    protected function describeDefinition(Definition $definition, array $options = [])
    {
        $totalWidth = $this->calculateTotalWidthForOptions($definition->getOptions());
        foreach ($definition->getArguments() as $argument) {
            $totalWidth = max($totalWidth, strlen($argument->getName()));
        }

        if ($definition->getArguments()) {
            $this->writeText('<comment>Arguments:</comment>', $options);
            $this->writeText("\n");
            foreach ($definition->getArguments() as $argument) {
                $this->describeArgument($argument, array_merge($options, ['total_width' => $totalWidth]));
                $this->writeText("\n");
            }
        }

        if ($definition->getArguments() && $definition->getOptions()) {
            $this->writeText("\n");
        }

        if ($definition->getOptions()) {
            $laterOptions = [];

            $this->writeText('<comment>Options:</comment>', $options);
            foreach ($definition->getOptions() as $option) {
                if (strlen($option->getShortcut()) > 1) {
                    $laterOptions[] = $option;
                    continue;
                }
                $this->writeText("\n");
                $this->describeOption($option, array_merge($options, ['total_width' => $totalWidth]));
            }

            foreach ($laterOptions as $option) {
                $this->writeText("\n");
                $this->describeOption($option, array_merge($options, ['total_width' => $totalWidth]));
            }
        }
    }

    protected function describeArgument(Argument $argument, array $options = [])
    {
        if ($argument->getDefault() !== null) {
            $default = sprintf('<comment> [default:%s]</comment>', $this->formatDefaultValue($argument->getDefault()));
        } else {
            $default = '';
        }

        $totalWidth = $options['total_width'] ?? strlen($argument->getName());
        $spacingWidth = $totalWidth - strlen($argument->getName()) + 2;

        $this->writeText(sprintf("  <info>%s</info>%s%s%s", $argument->getName(), str_repeat(' ', $spacingWidth), // + 17 = 2 spaces + <info> + </info> + 2 spaces
            preg_replace('/\s*\R\s*/', PHP_EOL . str_repeat(' ', $totalWidth + 17), $argument->getDescription()), $default), $options);
    }

    protected function describeOption(Option $option, array $options = [])
    {
        if ($option->acceptValue() && $option->getDefault() !== null) {
            $default = sprintf('<comment> [default: %s]</comment>', $this->formatDefaultValue($option->getDefault()));
        } else {
            $default = '';
        }

        $value = '';
        if ($option->acceptValue()) {
            $value = '=' . strtoupper($option->getName());

            if ($option->isValueOptional()) {
                $value = '[' . $value . ']';
            }
        }

        $totalWidth = $options['total_width'] ?? $this->calculateTotalWidthForOptions([$option]);
        $synopsis = sprintf('%s%s', $option->getShortcut() ? sprintf('-%s, ', $option->getShortcut()) : '    ', sprintf('--%s%s', $option->getName(), $value));

        $spacingWidth = $totalWidth - strlen($synopsis) + 2;

        $this->writeText(sprintf("  <info>%s</info>%s%s%s%s", $synopsis, str_repeat(' ', $spacingWidth), // + 17 = 2 spaces + <info> + </info> + 2 spaces
            preg_replace('/\s*\R\s*/', "\n" . str_repeat(' ', $totalWidth + 17), $option->getDescription()), $default, $option->isArray() ? '<comment> (multiple values allowed)</comment>' : ''), $options);
    }

    protected function describeConsole(Console $console, array $options = [])
    {
        $describedNamespace = $options['namespace'] ?? null;
        $description = new ConsoleDescriptor($console, $describedNamespace);

        if (isset($options['raw_text']) && $options['raw_text']) {
            $width = $this->getColumnWidth($description->getNamespaces());

            foreach ($description->getCommands() as $command) {
                $this->writeText(sprintf("%-{$width}s %s", $command->getName(), $command->getDescription()), $options);
                $this->writeText("\n");
            }
        } else {
            if ('' != $help = $console->getHelp()) {
                $this->writeText("$help\n\n", $options);
            }

            $this->writeText("<comment>Usage:</comment>\n", $options);
            $this->writeText("  command  [options] [arguments]\n\n", $options);

            $this->describeDefinition(new Definition($console->getDefinition()->getOptions()), $options);

            $this->writeText("\n");
            $this->writeText("\n");

            $width = $this->getColumnWidth($description->getNamespaces());

            if ($describedNamespace) {
                $this->writeText(sprintf('<comment>Available commands for the "%s" namespace:</comment>', $describedNamespace), $options);
            } else {
                $this->writeText('<comment>Available commands:</comment>', $options);
            }

            foreach ($description->getNamespaces() as $namespace) {
                if (!$describedNamespace && ConsoleDescriptor::GLOBAL_NAMESPACE !== $namespace['id']) {
                    $this->writeText("\n");
                    $this->writeText('<comment>' . $namespace['id'] . '</comment>');
                }

                foreach ($namespace['commands'] as $name) {
                    $this->writeText("\n");
                    $spacingWidth = $width - strlen($name);
                    $this->writeText(sprintf("  <info>%s</info>%s%s", $name, str_repeat(' ', $spacingWidth), $description->getCommand($name)->getDescription()), $options);

                }
            }

            $this->writeText("\n");
        }
    }

    /**
     * Note: 输出内容
     * Date: 2024-01-30
     * Time: 13:41
     */
    private function writeText($content, array $options = [])
    {
        $this->write(isset($options['raw_text']) && $options['raw_text'] ? strip_tags($content) : $content
            , isset($options['raw_output']) ? !$options['raw_output'] : true);
    }

    private function write($content, $decorated = false)
    {
        $this->output->write($content, false, $decorated ? Output::OUTPUT_NORMAL : Output::OUTPUT_RAW);
    }


    private function calculateTotalWidthForOptions($options)
    {
        $totalWidth = 0;
        foreach ($options as $option) {
            $nameLength = 4 + strlen($option->getName()) + 2; // - + shortcut + , + whitespace + name + --

            if ($option->acceptValue()) {
                $valueLength = 1 + strlen($option->getName()); // = + value
                $valueLength += $option->isValueOptional() ? 2 : 0; // [ + ]

                $nameLength += $valueLength;
            }
            $totalWidth = max($totalWidth, $nameLength);
        }

        return $totalWidth;
    }

    private function formatDefaultValue($default)
    {
        return json_encode($default, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function getColumnWidth(array $namespaces)
    {
        $width = 0;
        foreach ($namespaces as $namespace) {
            foreach ($namespace['commands'] as $name) {
                if (strlen($name) > $width) {
                    $width = strlen($name);
                }
            }
        }

        return $width + 2;
    }
}