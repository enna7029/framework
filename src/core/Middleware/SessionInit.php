<?php
declare(strict_types=1);

namespace Enna\Framework\Middleware;

use Enna\Framework\App;
use Enna\Framework\Request;
use Enna\Framework\Response;
use Enna\Framework\Session;
use Closure;

class SessionInit
{
    /**
     * @var App
     */
    protected $app;

    /**
     * @var Session
     */
    protected $session;

    public function __construct(App $app, Session $session)
    {
        $this->app = $app;
        $this->session = $session;
    }

    /**
     * Note: Session初始化
     * Date: 2023-03-01
     * Time: 15:45
     * @param Request $request 请求实例
     * @param Closure $next 中间件和dispatch闭包
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        $varSessionId = $this->app->config->get('session.var_session_id');
        $cookieName = $this->session->getName();

        //获取session_id
        if ($varSessionId && $request->request($varSessionId)) {
            $sessionId = $request->request($varSessionId);
        } else {
            $sessionId = $request->cookie($cookieName);
        }

        //设置session_id
        if ($sessionId) {
            $this->session->setSessionId($sessionId);
        }

        //根据session_id获取session数据
        $this->session->init();

        //request中设置session
        $request->withSession($this->session);

        /** @var Response $response */
        $response = $next($request);

        //response设置session
        $response->setSession($this->session);

        //使用cookie保存session_id,供下次方式时使用
        $this->app->cookie->set($cookieName, $this->session->getSessionId());

        return $response;
    }

    public function end(Response $response)
    {
        $this->session->save();
    }
}