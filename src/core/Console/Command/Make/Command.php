<?php

namespace Enna\Framework\Console\Command\Make;

use Enna\Framework\Console\Command\Make;
use Enna\Framework\Console\Input\Argument;

class Command extends Make
{
    protected $type = 'Command';

    public function configure()
    {
        parent::configure();
        $this->setName('make:command')
            ->addArgument('commandName', Argument::OPTIONAL, 'The name of the command')
            ->setDescription('Create a new command class');
    }

    protected function buildClass(string $name)
    {
        $commandName = $this->input->getArgument('commandName') ?: strtolower(basename($name));
        $namespace = trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');

        $class = str_replace($namespace . '\\', '', $name);
        $stub = file_get_contents($this->getStub());

        return str_replace(['{%commandName%}', '{%className%}', '{%namespace%}', '{%app_namespace%}'], [
            $commandName,
            $class,
            $namespace,
            $this->app->getNamespace()
        ], $stub);
    }

    protected function getStub()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Stubs' . DIRECTORY_SEPARATOR . 'command.stub';
    }

    protected function getNamespace(string $app)
    {
        return parent::getNamespace($app) . '\\command';
    }
}