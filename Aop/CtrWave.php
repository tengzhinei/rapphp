<?php
namespace rap\Aop;
use rap\Ioc;
use think\Config;
use think\Request;

/**
 * CtrWave 针对thinkphp写的,如果项目中没有使用tp,请勿使用该类
 *
 * 为控制器添加织入型AOP功能
 * @package Dh\Aop
 */
class   CtrWave  {

    private  static $instance;

    private function __construct(){
    }

    /**
     * @return CtrWave
     */
    public static function instance() {
        if(!static::$instance){
            static::$instance=new CtrWave();
        }
       return static::$instance;
    }

    private $wares;

    /**
     * 添加中间件
     * @param $name
     * @param $class
     * @param $bef
     * @param $after
     */
    public function add($name, $class, $bef, $after = null) {
        $info=array('name'=>$name,'class'=>$class,"action"=>$bef,"after"=>$after);
        $this->wares[$name]=$info;
    }

    //当前所有后置操作
    private $actionAfters;

    /**
     * 在控制器执行前后执行
     * @param $name
     * @param $methods
     * @param string $rule
     */
    private function around($name, $methods, $rule="only") {
        if($this->checkAction($methods,$rule)){
                $this->trigger($name);
        }
    }


    public function wave($wave){
        if(is_string($this->methods)){
            $this->methods=array($this->methods);
        }
        $this->around($wave,$this->methods,$this->rule);
    }


    private $methods;
    private $rule;
    public function methodsStart($methods){
        $this->rule="start";
        $ms=[];
        foreach ($methods as $method) {
            $ms[]=strtolower($method);
        }
        $this->methods=$ms;
        return $this;
    }
    public function methodsEnd($methods){
        $this->rule="end";
        $ms=[];
        foreach ($methods as $method) {
            $ms[]=strtolower($method);
        }
        $this->methods=$ms;
        return $this;
    }

    public function methods($methods){
        $this->rule="only";
        $ms=[];
        foreach ($methods as $method) {
            $ms[]=strtolower($method);
        }
        $this->methods=$ms;
        return $this;
    }

    public function methodsAll(){
        $this->rule="except";
        $this->methods=["1__"];
        return $this;

    }

    public function methodsExcept($methods){
        $this->rule="except";
        $ms=[];
        foreach ($methods as $method) {
            $ms[]=strtolower($method);
        }
        $this->methods=$ms;
        return $this;
    }
    public function methodsContains($methods){
        $this->rule="contains";
        $ms=[];
        foreach ($methods as $method) {
            $ms[]=strtolower($method);
        }
        $this->methods=$ms;
        return $this;
    }





    /**
     * 检查控制器
     * @param $methods
     * @param $rule
     * @return bool
     */
    private function checkAction(&$methods, $rule){
        $action=Request::instance()->action();
        $action=strtolower($action);
        if($rule=='only'){
            if(in_array($action,$methods)){
                  return true;
            }
        }else if($rule=='except'){
            if(!in_array($action,$methods)){
                return true;
            }
        }else if($rule=='start'){
            foreach ($methods as $method){
                if(strpos($action, $method) === 0){
                   return true;
                }
            }
        }else if($rule=='end'){
            foreach ($methods as $method){
                if(strpos($action, $method)+strlen($method)=== strlen($action)){
                    return true;
                }
            }
        }else if($rule=='contains'){
            foreach ($methods as $method){
                if(strpos($action, $method) > 0){
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 触发事件
     * @param $name
     */
    public function trigger($name) {
        $this->actionAfters[]=$name;
        $info=$this->wares[$name];
        if($info){
            $module=Ioc::get($info['class']);
            if($info['action']){
                $this->doAction($module,$info['action']);
            }
        }
    }

    private $result;

    /**
     *  触发后置事件
     * @param $result
     * @return mixed
     */
    public function triggerAfter($result) {
        $this->result=$result;
        if($this->actionAfters){
            foreach ($this->actionAfters as $name){
                $info=$this->wares[$name];
                if($info&&$info['after']){
                        $module = Ioc::get($info['class']);
                        $this->result=$this->doAction($module,$info['after']);
                }
            }
        }
        return $this->result;

    }

    /**
     * 执行任务
     * @param $module
     * @param $action
     * @return mixed
     * @throws \ReflectionException
     */
    private function doAction($module,$action){
        //执行当前操作
        $warpBean=$module;
        if($module instanceof  BeanWarp){
            $warpBean=$module->getWarpBean();
        }
        //拿包装类的方法
        $method =   new \ReflectionMethod($warpBean, $action);
        $args=$this->bindParams($method);
        $val= $method->invokeArgs($module,$args);
        return $val;
    }

    /**
     * 绑定参数
     * @access public
     * @param \ReflectionMethod|\ReflectionFunction $reflect 反射类
     * @param array             $vars    变量
     * @return array
     */
    private  function bindParams($reflect, $vars = [])
    {
        if (empty($vars)) {
            // 自动获取请求变量
            if (Config::get('url_param_type')) {
                $vars = Request::instance()->route();
            } else {
                $vars = Request::instance()->param();
            }
        }
        $args = [];
        // 判断数组类型 数字数组时按顺序绑定参数
        reset($vars);
        $type = key($vars) === 0 ? 1 : 0;
        if ($reflect->getNumberOfParameters() > 0) {
            $params = $reflect->getParameters();
            foreach ($params as $param) {
                $name  = $param->getName();
                if($name=='result'){
                    $args[]=$this->result;
                    continue;
                }
                $class = $param->getClass();
                if ($class) {
                    $className = $class->getName();
                    if (isset($vars[$name]) && $vars[$name] instanceof $className) {
                        $args[] = $vars[$name];
                        unset($vars[$name]);
                    } else {
                        $args[] = method_exists($className, 'instance') ? $className::instance() : new $className();
                    }
                } elseif (1 == $type && !empty($vars)) {
                    $args[] = array_shift($vars);
                } elseif (0 == $type && isset($vars[$name])) {
                    $args[] = $vars[$name];
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new \InvalidArgumentException('method param miss:' . $name);
                }
            }
            // 全局过滤
            array_walk_recursive($args, [Request::instance(), 'filterExp']);
        }
        return $args;
    }

}