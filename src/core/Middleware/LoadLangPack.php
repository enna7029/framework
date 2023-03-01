<?php
declare(strict_types=1);

namespace Enna\Framework\Middleware;

use Enna\Framework\App;
use Enna\Framework\Lang;
use \Closure;
use Enna\Framework\Request;
use Enna\Framework\Response;

class LoadLangPack
{
    protected $app;

    protected $lang;

    public function __construct(App $app, Lang $lang)
    {
        $this->app = $app;
        $this->lang = $lang;
    }

    /**
     * Note: 自动侦测系统语言
     * Date: 2023-02-27
     * Time: 17:50
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        $lang = $this->lang->detect($request);

        if ($lang !== $this->lang->defaultLang()) {

            $this->lang->load([
                $this->app->getCorePath() . 'lang' . DIRECTORY_SEPARATOR . $lang . '.php',
            ]);

            $this->app->loadLangPack($lang);
        }

        $this->lang->saveToCookie($this->app->cookie);

        return $next($request);
    }
}