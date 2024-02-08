<?php

namespace Enna\Framework\Console\Command;

use Enna\Framework\Console\Command;
use Enna\Framework\Console\Input;
use Enna\Framework\Console\Input\Option;
use Enna\Framework\Console\Output;

class VendorPublish extends Command
{
    protected function configure()
    {
        $this->setName('vendor:publish')
            ->addOption('force', 'f', Option::VALUE_NONE, 'Overwrite any existing files')
            ->setDescription('Publish any publishable assets from vendor packages');
    }

    public function handle()
    {
        $force = $this->input->getOption('force');

        if (is_file($path = $this->app->getRootPath() . 'vendor/composer/installed.json')) {
            $packages = json_decode(@file_get_contents($path), true);
            
            if (isset($packages['packages'])) {
                $packages = $packages['packages'];
            }

            foreach ($packages as $package) {
                $configDir = $this->app->getConfigPath();

                if (!empty($package['extra']['enna']['config'])) {
                    $installPath = $this->app->getRootPath() . 'vendor/' . $package['name'] . DIRECTORY_SEPARATOR;

                    foreach ((array)$package['extra']['enna']['config'] as $name => $file) {
                        $target = $configDir . $name . '.php';
                        $source = $installPath . $file;

                        if (is_file($target) && !$force) {
                            $this->output->info('File ' . $target . 'exist!');
                            continue;
                        }

                        if (!is_file($source)) {
                            $this->output->info('File ' . $source . 'not exist!');
                            continue;
                        }

                        copy($source, $target);
                    }
                }
            }

            $this->output->writeln('<info>Succeed!</info>');
        }
    }
}