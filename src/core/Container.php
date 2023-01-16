<?php
declare(strict_types=1);

namespace Enna\Framework;

use Closure;
use ArrayAccess;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionClass;
use ReflectionException;
use ReflectionFunctionAbstract;
use Enna\Framework\Exception\ClassNotFoundException;
use Enna\Framework\Exception\FuncNotFoundException;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface, ArrayAccess
{
    /**
     * 容器对象实例
     * @var Container|Closure
     */
    protected static $instance;

    /**
     * 容器中的对象实例
     * @var array
     */
    protected $instances = [];

    /**
     * Note: 获取当前容器的实例(单例)
     * Date: 2022-09-30
     * Time: 11:01
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        if (static::$instance instanceof Closure) {
            return (static::$instance)();
        }

        return static::$instance;
    }

    /**
     * Note: 设置当前容器实例
     * Date: 2022-09-13
     * Time: 18:52
     * @param object|Closure $instance
     */
    public static function setInstance($instance)
    {
        static::$instance = $instance;
    }

    /**
     * Note: 绑定一个类,闭包,实例,接口实现到容器
     * Date: 2022-09-13
     * Time: 18:49
     * @param $abstract
     * @param null $concrete
     */
    public function bind($abstract, $concrete = null)
    {
        if (is_array($abstract)) {
            foreach ($abstract as $key => $value) {
                $this->bind($key, $value);
            }
        } elseif ($concrete instanceof Closure) {
            $this->bind[$abstract] = $concrete;
        } elseif (is_object($concrete)) {
            $this->instance($abstract, $concrete);
        } else {
            $abstract = $this->getAlias($abstract);
            if ($abstract != $concrete) {
                $this->bind[$abstract] = $concrete;
            }
        }
    }

    /**
     * Note: 绑定一个类实例到容器
     * Date: 2022-09-13
     * Time: 18:48
     * @param string $abstract 类名或标识符
     * @param object $concrete 实例
     * @return $this
     */
    public function instance($abstract, $concrete)
    {
        $abstract = $this->getAlias($abstract);
        $this->instances[$abstract] = $concrete;

        return $this;
    }

    /**
     * Note:创建类的实例 已经创建则直接获取
     * Date: 2022-09-13
     * Time: 19:07
     * @param string $abstract 类名或标识
     * @param array $vars 变量
     * @param bool $newInstance 是否每次创建新的实例
     * @return mixed
     */
    public function make(string $abstract, array $vars = [], bool $newInstance = false)
    {
        $abstract = $this->getAlias($abstract);
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bind[$abstract]) && $this->bind[$abstract] instanceof Closure) {
            $object = $this->invokeFunction($this->bind[$abstract], $vars);
        } else {
            $object = $this->invokeClass($abstract, $vars);
        }

        if (!$newInstance) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Note: 调动反射执行类的方法
     * Date: 2022-10-10
     * Time: 18:29
     * @param $instance
     * @param $reflect
     * @param array $vars
     */
    public function invokeReflectMethod($instance, $reflect, array $vars = [])
    {
        $args = $this->bindParams($reflect, $vars);

        return $reflect->invokeArgs($instance, $args);
    }

    /**
     * Note: 调用反射,执行callable
     * Date: 2022-09-19
     * Time: 17:52
     * @param mixed $callable 类和方法
     * @param array $vars 参数
     */
    public function invoke($callable, array $vars = [])
    {
        if ($callable instanceof Closure) {
            return $this->invokeFunction($callable, $vars);
        } else {
            return $this->invokeMethod($callable, $vars);
        }
    }

    /**
     * Note: 调用反射,执行类的方法
     * User: enna
     * Date: 2022-09-19
     * Time: 17:53
     * @param $method
     * @param array $vars
     */
    public function invokeMethod($method, array $vars = [])
    {
        if (is_array($method)) {
            [$class, $method] = $method;

            $class = is_object($class) ? $class : $this->invokeClass($class);
        }

        try {
            $reflect = new  ReflectionMethod($class, $method);
        } catch (ReflectionException $e) {
            $class = is_object($class) ? get_class($class) : $class;
            throw new FuncNotFoundException('method not exists:' . $class . '->' . $method . '()', $class . '->' . $method . '()', $e);
        }

        $args = $this->bindParams($reflect, $vars);

        return $reflect->invokeArgs($class, $args);
    }

    /**
     * Note: 调用反射,执行函数或闭包方法
     * Date: 2022-09-14
     * Time: 9:35
     * @param string|Closure $function 函数或闭包
     * @param array $vars 参数
     */
    public function invokeFunction($function, array $vars = [])
    {
        try {
            $reflect = new ReflectionFunction($function);
        } catch (ReflectionException $e) {
            throw new FuncNotFoundException('function not found' . $function . '()', $function, $e);
        }

        $args = $this->bindParams($reflect, $vars);

        return $function(...$args);
    }

    /**
     * Note: 调用反射,执行类的实例化
     * Date: 2022-09-14
     * Time: 11:58
     * @param string $abstract
     * @param array $vars
     * @return object
     */
    public function invokeClass(string $abstract, array $vars = [])
    {
        try {
            $reflect = new ReflectionClass($abstract);
        } catch (ReflectionException $e) {
            throw new ClassNotFoundException('class not found' . $abstract, $abstract, $e);
        }

        if ($reflect->hasMethod('__make')) {
            $method = $reflect->getMethod('__make');
            if ($method->isPublic() && $method->isStatic()) {
                $args = $this->bindParams($method, $vars);
                $object = $method->invokeArgs(null, $args);
                return $object;
            }
        }

        $constructor = $reflect->getConstructor();
        $args = $constructor ? $this->bindParams($constructor, $vars) : [];
        $object = $reflect->newInstanceArgs($args);

        return $object;
    }

    /**
     * Note: 绑定参数
     * Date: 2022-09-14
     * Time: 10:43
     * @param ReflectionFunctionAbstract $reflect 反射类
     * @param array $vars 参数
     * @return array
     */
    public function bindParams(ReflectionFunctionAbstract $reflect, array $vars = []): array
    {
        if ($reflect->getNumberOfParameters() == 0) {
            return [];
        }

        reset($vars);
        $type = key($vars) == 0 ? 1 : 0;
        $params = $reflect->getParameters();

        $args = [];
        foreach ($params as $param) {
            $name = $param->getName();
            $reflectionType = $param->getType();

            if ($reflectionType && $reflectionType->isBuiltin() === false) {
                $args[] = $this->getObjectParam($reflectionType->getName(), $vars);
            } elseif ($type == 1 && !empty($vars)) {
                $args[] = array_shift($vars);
            } elseif ($type == 0 && array_key_exists($name, $vars)) {
                $args[] = $vars[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException('method param miss' . $name);
            }
        }

        return $args;
    }

    /**
     * Note:
     * Date: 2022-09-14
     * Time: 11:42
     * @param string $className 类型
     * @param array $vars 参数
     * @return mixed
     */
    public function getObjectParam(string $className, array $vars)
    {
        $array = $vars;
        $value = array_shift($array);

        if ($value instanceof $className) {
            $result = $value;
            array_shift($vars);
        } else {
            $result = $this->make($className);
        }
        return $result;
    }

    /**
     * Note: 创建工厂对象实例
     * Date: 2022-10-13
     * Time: 18:50
     * @param string $name 工厂类名
     * @param string $namespace 默认命名空间
     * @param mixed ...$args
     * @return mixed
     */
    public static function factory(string $name, string $namespace = '', ...$args)
    {
        $class = strpos($name, '\\') !== false ? $name : $namespace . ucwords($name);

        return Container::getInstance()->invokeClass($class, $args);
    }

    /**
     * Note: 获取别名
     * Date: 2022-09-13
     * Time: 18:48
     * @param $abstract
     * @return mixed
     */
    public function getAlias($abstract)
    {
        if (isset($this->bind[$abstract])) {
            $abstract = $this->bind[$abstract];

            if (is_string($abstract)) {
                return $this->getAlias($abstract);
            }
        }

        return $abstract;
    }

    /**
     * Note:获取容器中的对象实例
     * Date: 2022-09-13
     * Time: 19:04
     * @param string $name 类名或标识符
     * @return mixed|void
     */
    public function get(string $abstract)
    {
        if ($this->has($abstract)) {
            return $this->make($abstract);
        }

        throw new ClassNotFoundException('class not found:' . $abstract, $abstract);
    }

    /**
     * Note: 判断容器中是否存在类或标识
     * Date: 2022-09-13
     * Time: 19:05
     * @param string $abstract 类名或标识符
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bind[$abstract]) || isset($this->instances[$abstract]);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
        // TODO: Implement __isset() method.
    }

    public function __unset($name)
    {
        // TODO: Implement __unset() method.
    }

    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
    }

    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.
    }

    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }
}