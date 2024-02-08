<?php

namespace Enna\Framework\Console\Command\Optimize;

use Enna\Framework\Console\Command;
use Enna\Framework\Console\Input;
use Enna\Framework\Console\Input\Argument;
use Enna\Framework\Console\Input\Option;
use Enna\Framework\Console\Output;
use Enna\Orm\Db\PDOConnection;

class Schema extends Command
{
    protected function configure()
    {
        $this->setName('optimize:schema')
            ->addArgument('dir', Argument::OPTIONAL, 'dir name.')
            ->addOption('connection', null, Option::VALUE_REQUIRED, 'connection name.')
            ->addOption('table', null, Option::VALUE_REQUIRED, 'table name.')
            ->setDescription('Build database schema cache.');
    }

    protected function execute(Input $input, Output $output)
    {
        $dir = $input->getArgument('dir') ?: '';

        if ($input->hasOption('table')) {
            $connection = $this->app->db->connect($input->getOption('connection'));
            if (!$connection instanceof PDOConnection) {
                $output->error('only PDO connection support schema cache!');
                return;
            }

            $table = $input->getOption('table');
            if (strpos($table, '.') === false) {
                $dbName = $connection->getConfig('database');
            } else {
                [$dbName, $table] = explode('.', $table);
            }

            if ($table == '*') {
                $table = $connection->getTables($dbName);
            }

            $this->buildDataBaseSchema($connection, (array)$table, $dbName);
        } else {
            if ($dir) {
                $appPath = $this->app->getBasePath() . $dir . DIRECTORY_SEPARATOR;
                $namespace = 'app\\' . $dir;
            } else {
                $appPath = $this->app->getBasePath();
                $namespace = 'app';
            }

            $path = $appPath . 'model';
            $list = is_dir($path) ? scandir($path) : [];

            foreach ($list as $file) {
                if (strpos($file, '.') === 0) {
                    continue;
                }

                $class = '\\' . $namespace . '\\model\\' . pathinfo($file, PATHINFO_FILENAME);

                if (!class_exists($class)) {
                    continue;
                }

                $this->buildModelSchema($class);
            }
        }

        $output->writeln('<info>Succeed!</info>');
    }

    /**
     * Note: 创建模型的数据库表结构缓存
     * Date: 2024-02-01
     * Time: 11:22
     * @param string $class
     * @throws \ReflectionException
     */
    protected function buildModelSchema(string $class)
    {
        $reflect = new \ReflectionClass($class);
        if (!$reflect->isAbstract() && $reflect->isSubclassOf('\Enna\Orm\Model')) {
            try {
                /** @var \Enna\Orm\Model $model */
                $model      = new $class;
                $connection = $model->db()->getConnection();
                if ($connection instanceof PDOConnection) {
                    $table = $model->getTable();
                    
                    //预读字段信息
                    $connection->getSchemaInfo($table, true);
                }
            } catch (Exception|\Throwable $e) {
                echo $e->getMessage().$e->getFile().$e->getLine();exit;
            }
        }
    }

    /**
     * Note: 创建数据库表结构缓存
     * Date: 2024-02-01
     * Time: 11:16
     * @param PDOConnection $connection 连接实例
     * @param array $tables 表
     * @param string $dbName 库
     */
    protected function buildDataBaseSchema(PDOConnection $connection, array $tables, string $dbName)
    {
        foreach ($tables as $table) {
            $connection->getSchemaInfo($dbName . $table, true);
        }
    }
}