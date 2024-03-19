<?php
declare(strict_types=1);

namespace Enna\Framework\Route;

use Closure;
use Enna\Framework\App;
use Enna\Framework\Request;
use Enna\Framework\Response;
use Enna\Framework\Validate;
use Psr\Http\Message\ResponseInterface;

/**
 * 路由调度基础类
 * Class Dispatch
 * @package Enna\Framework\Route
 */
abstract class Dispatch
{
    /**
     * 应用对象
     * @var App
     */
    protected $app;

    /**
     * 请求对象
     * @var Request
     */
    protected $request;

    /**
     * 路由规则对象
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

    /**
     * Note: 执行路由调度
     * Date: 2022-09-30
     * Time: 15:42
     * @return mixed
     */
    public function run()
    {
        if ($this->rule instanceof RuleItem && $this->request->method() == 'OPTIONS' && $this->rule->isAutoOptions()) {
            $rules = $this->rule->getRouter()->getRule($this->rule->getRule());
            $allow = [];
            foreach ($rules as $item) {
                $allow[] = strtoupper($item->getMethod());
            }

            return Response::create('', 'html', 204)->header(['allow' => implode(',', $allow)]);
        }

        $data = $this->exec();
        return $this->autoResponse($data);
    }

    /**
     * Note: 返回响应
     * Date: 2023-07-27
     * Time: 15:01
     * @param $data
     * @return Response
     */
    protected function autoResponse($data): Response
    {
        if ($data instanceof Response) {
            $response = $data;
        } elseif ($data instanceof ResponseInterface) {
            $response = Response::create((string) $data->getBody(), 'html', $data->getStatusCode());

            foreach ($data->getHeaders() as $header => $values) {
                $response->header([$header => implode(", ", $values)]);
            }
        }elseif (!is_null($data)) {
            $type = $this->request->isJson() ? 'json' : 'html';
            $response = Response::create($data, $type);
        } else {
            $data = ob_get_clean();

            $content = $data === false ? '' : $data;
            $status = $content === '' && $this->request->isJson() ? 204 : 200;
            $response = Response::create($content, 'html', $status);
        }

        return $response;
    }

    /**
     * Note: 检查路由后置操作
     * Date: 2023-07-19
     * Time: 14:56
     * @return void
     */
    protected function doRouteAfter()
    {
        $option = $this->rule->getOption();

        //添加路由中间件
        if (!empty($option['middleware'])) {
            $this->app->middleware->import($option['middleware'], 'route');
        }

        //添加额外参数
        if (!empty($option['append'])) {
            $this->param = array_merge($this->param, $option['append']);
        }

        //绑定模型数据
        if (!empty($option['model'])) {
            $this->createBindModel($option['model'], $this->param);
        }

        //设置路由规则
        $this->request->setRule($this->rule);

        //设置路由变量
        $this->request->setRoute($this->param);

        //数据自动验证
        if (isset($option['validate'])) {
            $this->autoValidate($option['validate']);
        }
    }

    /**
     * Note: 路由绑定模型实例
     * Date: 2023-07-27
     * Time: 14:58
     * @param array $bindModel 绑定模型
     * @param array $matches 路由变量
     * @return void
     */
    protected function createBindModel(array $bindModel, array $matches)
    {
        foreach ($bindModel as $key => $val) {
            if ($val instanceof Closure) {
                $result = $this->app->invokeFunction($val, $matches);
            } else {
                $fields = explode('&', $key);

                if (is_array($val)) {
                    [$model, $exception] = $val;
                } else {
                    $model = $val;
                    $exception = true;
                }

                $where = [];
                $match = true;
                foreach ($fields as $field) {
                    if (!isset($matches[$fields])) {
                        $match = false;
                        break;
                    } else {
                        $where[] = [$field, '=', $matches[$field]];
                    }
                }

                if ($match) {
                    $result = $model::where($where)->failException($exception)->find();
                }
            }

            if (!empty($result)) {
                $this->app->instance(get_class($result), $result);
            }
        }
    }

    /**
     * Note: 验证数据
     * Date: 2023-07-27
     * Time: 14:59
     * @param array $option
     * @return void
     * @throws \Enna\Framework\Exception\ ValidateException
     */
    protected function autoValidate(array $option)
    {
        [$validate, $scene, $message, $batch] = $option['validate'];

        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            $class = strpos($validate, '\\') !== false ? $validate : $this->app->parseClass('validate', $validate);

            $v = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message)
            ->batch($batch)
            ->failException(true)
            ->check($this->request->param());
    }

    /**
     * Note: 获取调度信息
     * Date: 2023-07-27
     * Time: 15:06
     * @return mixed
     */
    public function getDispatch()
    {
        return $this->dispatch;
    }

    /**
     * Note: 获取路由变量
     * Date: 2023-07-27
     * Time: 15:06
     * @return array
     */
    public function getParam()
    {
        return $this->param;
    }

    abstract public function exec();

    public function __debugInfo()
    {
        return [
            'dispatch' => $this->dispatch,
            'param' => $this->param,
            'rule' => $this->rule,
        ];
    }
}