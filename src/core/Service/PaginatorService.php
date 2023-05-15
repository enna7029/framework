<?php
declare(strict_types=1);

namespace Enna\Framework\Service;

use Enna\Orm\Paginator;
use Enna\Orm\Paginator\Driver\Bootstrap;
use Enna\Framework\Service;

/**
 * 分页服务类
 * Class PaginatorService
 * @package Enna\Framework\Service
 */
class PaginatorService extends Service
{
    public function register(): void
    {
        if (!$this->app->has(Paginator::class)) {
            $this->app->bind(Paginator::class, Bootstrap::class);
        }
    }

    public function boot()
    {
        Paginator::maker(function (...$args) {
            return $this->app->make(Paginator::class, $args, true);
        });

        Paginator::currentPathResolver(function () {
            return $this->app->request->baseUrl();
        });

        Paginator::currentPageResolver(function ($varPage = 'page') {
            $page = $this->app->request->param($varPage);

            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int)$page >= 1) {
                return (int)$page;
            }

            return 1;
        });
    }
}