<?php
namespace rap\web\mvc;

use rap\exception\MsgException;
use rap\session\Session;
use rap\storage\File;
use rap\web\Request;
use rap\web\Response;


/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/8/31
 * Time: 下午9:47
 */
abstract class HandlerAdapter {
    private $pattern;
    private $header;
    protected $method;
    private $params;

    public abstract function viewBase();

    /**
     * 设置或获取匹配的路径规则
     *
     * @param string $pattern
     *
     * @return string
     */
    public function pattern($pattern) {
        if ($pattern) {
            $this->pattern = $pattern;
        }
        return $this->pattern;
    }

    /**
     * 设置或获取匹配的请求头
     *
     * @param array $header
     *
     * @return array
     */
    public function header($header) {
        if ($header) {
            $this->header = $header;
        }
        return $this->header;
    }

    /**
     * 设置或获取匹配的方法
     *
     * @param array $method
     *
     * @return array
     */
    public function method($method) {
        if ($method) {
            $this->method = $method;
        }
        return $this->method;
    }

    public abstract function handle(Request $request, Response $response);

    public function addParam($key, $value) {
        $this->params[ $key ] = $value;
    }

    /**
     * 调用方法 并绑定对象
     *
     * @param $obj      mixed 对象
     * @param $method   string 方法名
     * @param $request  Request 请求
     * @param $response Response 回复
     *
     * @throws MsgException
     * @return mixed
     */
    public function invokeRequest($obj, $method, Request $request, Response $response) {
        try {
            $method = new \ReflectionMethod(get_class($obj), $method);
        } catch (\Exception $e) {
            throw new MsgException("对应的路径不存在方法");
        }
        $args = [];

        if ($method->getNumberOfParameters() > 0) {
            $params = $method->getParameters();
            $search_index = 0;
            $search = $request->search();
            /* @var $param \ReflectionParameter */
            foreach ($params as $param) {
                $name = $param->getName();
                $default = null;
                if ($param->isDefaultValueAvailable()) {
                    $default = $param->getDefaultValue();
                }
                $class = $param->getClass();
                if ($class) {
                    $className = $class->getName();
                    if ($className == Search::class) {
                        $args[] = new Search($search[ $search_index ]);
                        $search_index++;
                    } elseif ($className == Request::class) {
                        $args[] = $request;
                    } elseif ($className == Response::class) {
                        $args[] = $response;
                    } elseif ($className == Session::class) {
                        $args[] = $request->session();
                    } elseif ($className == File::class) {
                        $args[] = $request->file($name);
                    } else {
                        $className = $class->getName();
                        $bean = method_exists($className, 'instance') ? $className::instance() : new $className();
                        $properties = $class->getProperties();
                        foreach ($properties as $property) {
                            $name = $property->getName();
                            $val = $request->param($name);
                            if (isset($val)) {

                                $bean->$name = $val;
                            }
                        }
                        $args[] = $bean;
                    }
                } else {
                    if (key_exists($name, $this->params)) {
                        $args[ $name ] = $this->params[ $name ];
                    } else {
                        $args[ $name ] = $request->param($name, $default);
                    }
                }
            }
        }
        $val = $method->invokeArgs($obj, $args);
        return $val;
    }


    public function invokeClosure(\Closure $closure, Request $request, Response $response) {
        $method = new \ReflectionFunction($closure);
        $args = [];
        if ($method->getNumberOfParameters() > 0) {
            $params = $method->getParameters();
            $search_index = 0;
            $search = $request->search();
            /* @var $param \ReflectionParameter */
            foreach ($params as $param) {
                $name = $param->getName();
                $default = null;
                if ($param->isDefaultValueAvailable()) {
                    $default = $param->getDefaultValue();
                }
                $class = $param->getClass();
                if ($class) {
                    $className = $class->getName();
                    if ($className == Search::class) {
                        $args[] = new Search($search[ $search_index ]);
                        $search_index++;
                    } elseif ($className == Request::class) {
                        $args[] = $request;
                    } elseif ($className == Response::class) {
                        $args[] = $response;
                    } elseif ($className == Session::class) {
                        $args[] = $request->session();
                    } elseif ($className == File::class) {
                        $args[] = $request->file($name);
                    } else {
                        $bean = method_exists($className, 'instance') ? $className::instance() : new $className();
                        $properties = $class->getProperties();
                        foreach ($properties as $property) {
                            $name = $property->getName();
                            $val = $request->param($name);
                            if (isset($val)) {
                                $bean->$name = $val;
                            }
                        }
                        $args[ $name ] = $bean;
                    }
                } else {
                    if (key_exists($name, $this->params)) {
                        $args[ $name ] = $this->params[ $name ];
                    } else {
                        $args[ $name ] = $request->param($name, $default);
                    }
                }
            }
        }
        $result = call_user_func_array($closure, $args);
        return $result;
    }

}