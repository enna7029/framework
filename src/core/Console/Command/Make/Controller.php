<?php

namespace Enna\Framework\Console\Command\Make;

use Enna\Framework\Console\Command\Make;
use Enna\Framework\Console\Input\Option;

class Controller extends Make
{
    protected $type = 'Controller';

    protected function configure()
    {
        parent::configure();
        $this->setName('make:controller')
            ->addOption('api', null, Option::VALUE_NONE, 'Generate an api controller class.')
            ->addOption('plain', null, Option::VALUE_NONE, 'Generate an empty contoller class.')
            ->setDescription('Create a new resource controller class');
    }

    protected function getStub()
    {
        $stubPath = __DIR__ . DIRECTORY_SEPARATOR . 'Stubs' . DIRECTORY_SEPARATOR;

        if ($this->input->getOption('api')) {
            return $stubPath . 'controller.api.stub';
        }

        if ($this->input->getOption('plain')) {
            return $stubPath . 'controller.plain.stub';
        }

        return $stubPath . 'controller.stub';
    }

    protected function getClassName(string $name)
    {
        return parent::getClassName($name) . ($this->app->config->get('route.controller_suffix') ? 'Controller' : '');
    }

    protected function getNamespace(string $app)
    {
        return parent::getNamespace($app) . '\\controller';
    }
}