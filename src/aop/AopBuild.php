<?php
/**
 * 南京灵衍信息科技有限公司
 * User: tengzhinei
 * Date: 16/9/14
 * Time: 下午1:36
 */

namespace rap\aop;

/**
 * AOP构造器
 */
class AopBuild
{

    /**
     * 方法名
     * @var string
     */
    private $method;
    /**
     * 拦截规则
     * @var string
     */
    private $rule = "all";

    /**
     * 拦截的类名
     * @var string
     */
    private $wave;

    /**
     * 拦截的类型
     * @var string
     */
    private $waveType = "before";

    /**
     * 被拦截的类名
     * @var string
     */
    private $clazz;

    /**
     * 拦截后回调方法
     * @var string
     */
    private $using = "handle";
    /**
     *  拦截回调
     * @var \Closure
     */
    private $call;

    private function __construct($waveType, $clazz)
    {
        $this->waveType = $waveType;
        $this->clazz = $clazz;
    }

    /**
     * 拦截的方法
     *
     * @param string|array $method
     *
     * @return $this
     */
    public function methods($method)
    {
        if (is_string($method)) {
            $method = array($method);
        }
        $this->rule = "only";
        $this->method = $method;
        return $this;
    }

    /**
     * 排除的方法
     *
     * @param string|array $method
     *
     * @return $this
     */
    public function methodsExcept($method)
    {
        if (is_string($method)) {
            $method = array($method);
        }
        $this->rule = "except";
        $this->method = $method;
        return $this;
    }

    /**
     *方法名开头是
     *
     * @param string|array $method
     *
     * @return $this
     */
    public function methodsStart($method)
    {
        if (is_string($method)) {
            $method = array($method);
        }
        $this->rule = "start";
        $this->method = $method;
        return $this;
    }

    /**
     *方法名包含
     *
     * @param string|array $method
     *
     * @return $this
     */
    public function methodsContains($method)
    {
        if (is_string($method)) {
            $method = array($method);
        }
        $this->rule = "contains";
        $this->method = $method;
        return $this;
    }

    /**
     * 方法名结尾是
     *
     * @param string|array $method
     *
     * @return $this
     */
    public function methodsEnd($method)
    {
        if (is_string($method)) {
            $method = array($method);
        }
        $this->rule = "end";
        $this->method = $method;
        return $this;
    }

    /**
     * 所有方法
     * @return $this
     */
    public function methodsAll()
    {
        $this->rule = "except";
        $this->method = array("1___","__construct");
        return $this;
    }

    /**
     * 需要植入的类
     *
     * @param string $wave_class
     *
     * @return $this
     */
    public function wave($wave_class)
    {
        $this->wave = $wave_class;
        return $this;
    }

    /**
     * 需要植入的方法名
     *
     * @param string $using_method
     *
     * @return $this
     */
    public function using($using_method)
    {
        $this->using = $using_method;
        return $this;
    }

    /**
     * 拦截方法
     *
     * @param \Closure $call
     *
     * @return $this
     */
    public function call(\Closure $call)
    {
        $this->call = $call;
        return $this;
    }


    /**
     * 前置拦截
     *
     * @param string $clazz 类名
     *
     * @return AopBuild
     */
    public static function before($clazz)
    {
        return new AopBuild("before", $clazz);
    }

    /**
     * 后置拦截
     *
     * @param string $clazz 类名
     *
     * @return AopBuild
     */
    public static function after($clazz)
    {
        return new AopBuild("after", $clazz);
    }

    /**
     * 包裹拦截
     *
     * @param string $clazz 类名
     *
     * @return AopBuild
     */
    public static function around($clazz)
    {
        return new AopBuild("around", $clazz);
    }

    /**
     * 添加拦截器
     */
    public function addPoint()
    {
        $type = $this->waveType;
        $action = array("type" => $this->rule, "methods" => $this->method);
        Aop::$type($this->clazz, $action, $this->wave, $this->using, $this->call);
    }
}
