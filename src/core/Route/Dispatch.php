<?php
declare(strict_types=1);

namespace Enna\Framework\Route;

use Enna\Framework\App;
use Enna\Framework\Request;
use Enna\Framework\Response;

abstract class Dispatch
{
    /**
     * 应用对象
     * @var App
     */
    protected $app;

    /**
     * @var Request
     */
    protected $request;

    /**
     * 路由规则
     * @var Rule
     */
    protected $rule;

    /**
     * 调度信息
     * @var mixed
     */
    protected $dispatch;

    /**
     * 路由变量
     * @var array
     */
    protected $param;

    public function __construct(Request $request, Rule $rule, $dispatch, array $param = [])
    {
        $this->request = $request;
        $this->rule = $rule;
        $this->dispatch = $dispatch;
        $this->param = $param;
    }

    public function init(App $app)
    {
        $this->app = $app;

        //执行路由后置操作
        $this->doRouteAfter();
    }

    abstract public function exec();

    /**
     * Note: 执行路由调度
     * Date: 2022-09-30
     * Time: 15:42
     * @return mixed
     */
    public function run()
    {
        $data = $this->exec();
        return $this->autoResponse($data);
    }

    protected function autoResponse($data): Response
    {
        if ($data instanceof Response) {
            $response = $data;
        } else {
            $data = ob_get_clean();

            $content = $data === false ? '' : $data;
            $status = $content === '' && $this->request->isJson() ? 204 : 200;
            $response = Response::create($content, 'html', $status);
        }

        return $response;
    }

    protected function doRouteAfter()
    {
        $option = $this->rule->getOption();
        
        if (!empty($option['middleware'])) {
            $this->app->middleware->import($option['middleware'], 'route');
        }

        //记录路由变量
        $this->request->setRoute($this->param);
    }

    public function __debugInfo()
    {
        return [
            'dispatch' => $this->dispatch,
            'param' => $this->param,
            'rule' => $this->rule,
        ];
    }
}