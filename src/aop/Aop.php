<?php

namespace rap\aop;
use rap\ioc\Ioc;


/**
 * AOP 拦截
 */
class Aop {

    const NuLL="Aop___NULL";
    /**
     * 所有前置拦截器
     * @var array
     */
    static private $beforeActions = array();

    /**
     * 所有后置拦截器
     * @var array
     */
    static private $afterActions = array();

    /**
     * 所有包裹拦截器
     * @var array
     */
    static private $aroundActions = array();

    /**
     * 计数用
     * @var int
     */
    static private $range = 0;

    /**
     * 包围时只能添加一个以最后一个为准
     *
     * @param      $clazz
     * @param      $actions
     * @param      $aroundClazz
     * @param      $warpAction
     * @param null $call
     */
    public static function around($clazz, $actions, $aroundClazz, $warpAction, $call = null) {
        $actions = static::actionsBuild($actions);
        if (!isset(static::$aroundActions[ $clazz ])) {
            static::$aroundActions[ $clazz ] = array();
        }
        $info = array('methods' => $actions[ 'methods' ],
                      'class' => $aroundClazz,
                      'action' => $warpAction,
                      "call" => $call,
                      "range" => static::$range);
        static::$range++;
        static::$aroundActions[ $clazz ][ $actions[ 'type' ] ] = array();
        static::$aroundActions[ $clazz ][ $actions[ 'type' ] ][] = $info;
    }

    /**
     * 方法执行前调用
     *
     * @param      $clazz
     * @param      $actions
     * @param      $beforeClazz
     * @param      $warpAction
     * @param null $call
     */
    public static function before($clazz, $actions, $beforeClazz, $warpAction, $call = null) {
        $actions = static::actionsBuild($actions);
        if (!isset(static::$beforeActions[ $clazz ])) {
            static::$beforeActions[ $clazz ] = array();
        }
        if (!isset(static::$beforeActions[ $clazz ][ $actions[ 'type' ] ])) {
            static::$beforeActions[ $clazz ][ $actions[ 'type' ] ] = array();
        }
        $info = array('methods' => $actions[ 'methods' ],
                      'class' => $beforeClazz,
                      'action' => $warpAction,
                      "call" => $call,
                      "range" => static::$range);
        static::$range++;
        static::$beforeActions[ $clazz ][ $actions[ 'type' ] ][] = $info;
    }

    private static function actionsBuild($actions) {
        if (!array_key_exists('methods', $actions)) {
            $actions = array("type" => "only", "methods" => $actions);
        }
        if (!array_key_exists('type', $actions)) {
            $actions[ 'type' ] = "only";
        }
        return $actions;
    }

    /**
     * 方法执行后调用
     *
     * @param      $clazz
     * @param      $actions
     * @param      $afterClazz
     * @param      $warpAction
     * @param null $call
     */
    public static function after($clazz, $actions, $afterClazz, $warpAction, $call = null) {
        $actions = static::actionsBuild($actions);
        if (!isset(static::$afterActions[ $clazz ])) {
            static::$afterActions[ $clazz ] = array();
        }
        if (!isset(static::$afterActions[ $clazz ][ $actions[ 'type' ] ])) {
            static::$afterActions[ $clazz ][ $actions[ 'type' ] ] = array();
        }
        $info = array('methods' => $actions[ 'methods' ],
                      'class' => $afterClazz,
                      'action' => $warpAction,
                      "call" => $call,
                      "range" => static::$range);
        static::$range++;
        static::$afterActions[ $clazz ][ $actions[ 'type' ] ][] = $info;
    }

    /**
     * 获取某方法的所有的前置方法
     *
     * @param $clazz
     * @param $action
     *
     * @return array|null
     */
    public static function getBeforeActions($clazz, $action) {
        if (static::$beforeActions[ $clazz ]) {
            return static::buildActions(static::$beforeActions[ $clazz ], $action);
        }
        return null;
    }

    private static function buildActions(&$wareactions, $action) {
        $actions = array();
        if (array_key_exists('only', $wareactions)) {
            $acs = $wareactions[ 'only' ];
            foreach ($acs as $ac) {
                if (in_array($action, $ac[ 'methods' ])) {
                    $actions[] = array('class' => $ac[ 'class' ],
                                       "action" => $ac[ 'action' ],
                                       "call" => $ac[ 'call' ],
                                       "range" => $ac[ 'range' ]);
                }
            }
        }
        if (array_key_exists('except', $wareactions)) {
            $acs = $wareactions[ 'except' ];
            foreach ($acs as $ac) {
                if (!in_array($action, $ac[ 'methods' ])) {
                    $actions[] = array('class' => $ac[ 'class' ],
                                       "action" => $ac[ 'action' ],
                                       "call" => $ac[ 'call' ],
                                       "range" => $ac[ 'range' ]);
                }
            }
        }
        if (array_key_exists('start', $wareactions)) {
            $acs = $wareactions[ 'start' ];
            foreach ($acs as $ac) {
                foreach ($ac[ 'methods' ] as $method) {
                    if (strpos($action, $method) === 0) {
                        $actions[] = array('class' => $ac[ 'class' ],
                                           "action" => $ac[ 'action' ],
                                           "call" => $ac[ 'call' ],
                                           "range" => $ac[ 'range' ]);
                    }
                }
            }
        }
        if (array_key_exists('end', $wareactions)) {
            $acs = $wareactions[ 'end' ];
            foreach ($acs as $ac) {
                foreach ($ac[ 'methods' ] as $method) {
                    if (strpos($action, $method) + strlen($method) === strlen($action)) {
                        $actions[] = array('class' => $ac[ 'class' ],
                                           "action" => $ac[ 'action' ],
                                           "call" => $ac[ 'call' ],
                                           "range" => $ac[ 'range' ]);
                    }
                }
            }
        }
        if (array_key_exists('contains', $wareactions)) {
            $acs = $wareactions[ 'contains' ];
            foreach ($acs as $ac) {
                foreach ($ac[ 'methods' ] as $method) {
                    if (strpos($action, $method) > 0) {
                        $actions[] = array('class' => $ac[ 'class' ],
                                           "action" => $ac[ 'action' ],
                                           "call" => $ac[ 'call' ],
                                           "range" => $ac[ 'range' ]);
                    }
                }
            }
        }
        foreach ($actions as $val) {
            $vals[] = $val[ 'range' ];
        }
        if ($actions) {
            array_multisort($vals, $actions, SORT_ASC);
        }
        return $actions;
    }


    /**
     * 获取某方法的所有的后置方法
     *
     * @param $clazz
     * @param $action
     *
     * @return array|null
     */
    public static function getAfterActions($clazz, $action) {
        if (static::$afterActions[ $clazz ]) {
            return static::buildActions(static::$afterActions[ $clazz ], $action);
        }
        return null;
    }

    /**
     * 获取某方法的包围方法,只有一个
     *
     * @param $clazz
     * @param $action
     *
     * @return array
     */
    public static function getAroundActions($clazz, $action) {
        $actions = null;
        if (isset(static::$aroundActions[ $clazz ]) && static::$aroundActions[ $clazz ]) {
            $actions = static::buildActions(static::$aroundActions[ $clazz ], $action);
        }
        if ($actions && count($actions) > 0) {
            return $actions[ 0 ];
        }
        return null;
    }

    /**
     * 检测是否需要进行对象warp
     *
     * @param $bean
     *
     * @return bool
     */
    public static function needWarp($bean) {
        if (array_key_exists($bean, static::$beforeActions) || array_key_exists($bean, static::$afterActions) || array_key_exists($bean, static::$aroundActions)) {
            return true;
        }
        return false;
    }

    public static function warpBean($clazz, $name) {
        $who = $clazz;
        if (self::needWarp($name)) {
            $who = "rap\\aop\\build\\" . $clazz . "_PROXY";
        } else if ($name != $clazz && self::needWarp($clazz)) {
            $who = "rap\\aop\\build\\" . $clazz . "_PROXY";
        }
        $class = new \ReflectionClass($who);
        $obj = $class->newInstanceWithoutConstructor();
        return $obj;
    }

    private static function deleteAll($path) {
        if (!file_exists($path)) {
            return;
        }
        $op = dir($path);
        while (false != ($item = $op->read())) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (is_dir($op->path . DS . $item)) {
                static::deleteAll($op->path . DS . $item);
                rmdir($op->path . DS . $item);
            } else {
                unlink($op->path . DS . $item);
            }

        }
    }

    public static function init($version) {
        $file = str_replace(DS . "Aop.php", DS . "build" . DS . "build", __FILE__);
        if (file_exists($file)) {
            $content = file_get_contents($file);
            //版本相同 返回
            if ($content == $version . "") {
                return;
            }
        }
        self::buildProxy();
        $file = fopen($file, "w");
        fwrite($file, $version);
    }

    /**
     * 创建代理文件
     */
    public static function buildProxy() {
        $dir = ROOT_PATH . 'aop';
        self::deleteAll($dir);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $clazzs = array_unique(array_merge(array_keys(static::$beforeActions), array_keys(static::$afterActions), array_keys(static::$aroundActions)));
        foreach ($clazzs as $aop_clazz) {
            $clazz = Ioc::getRealClass($aop_clazz);
            $reflection = new \ReflectionClass($clazz);
            $isInterface = $reflection->isInterface();
            $nameSpace = "\\" . $reflection->getNamespaceName();
            $clazzSimpleName = $reflection->getShortName() . "_PROXY";
            $clazzExtend = "\\" . $clazz;

            $aop_reflection = new \ReflectionClass($aop_clazz);
            $methods = $aop_reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            $methodsStr = "";

            /* @var $method \ReflectionMethod */
            foreach ($methods as $method) {
                if ($method->getName() == '_initialize' || $method->getName() == '_prepared') {
                    continue;
                }
                $around = self::getAroundActions($aop_clazz, $method->getName());
                $after = self::getAfterActions($aop_clazz, $method->getName());
                $before = self::getBeforeActions($aop_clazz, $method->getName());
                if (!$around && !$after && !$before) {
                    continue;
                }
                $methodName = $method->getName();
                $BeanClazz = "'" . $aop_clazz . "'";
                $methodArgs = "";
                $pointArgs = "";
                $index = 0;
                $params = $method->getParameters();
                $names = [];

                /* @var $param   \ReflectionParameter */
                foreach ($params as $param) {
                    if ($methodArgs) {
                        $methodArgs .= ",";
                        $pointArgs .= ",";
                    }
                    $paramClazz = $param->getClass();
                    $names[] = '"' . $param->getName() . '"';
                    if ($paramClazz) {
                        $methodArgs .= "\\" . $paramClazz->getName() . " ";
                    }
                    $methodArgs .= "$" . $param->getName() . " ";

                    if ($param->isDefaultValueAvailable()) {
                        $value = $param->getDefaultValue();
                        $isStr = gettype($value) == "string";
                        $isArray = gettype($value) == "array";
                        $methodArgs .= "=";
                        if ($value === null) {
                            $methodArgs .= 'null';
                        }else if ($value === false){
                            $methodArgs .= 'false';
                        }  else {
                            if($isArray){
                                $methodArgs .= "[]";
                            }else{
                                if ($isStr) {
                                    $methodArgs .= "\"";
                                }
                                $methodArgs .= $param->getDefaultValue();
                                if ($isStr) {
                                    $methodArgs .= "\"";
                                }
                            }

                        }
                    }
                    $pointArgs .= "\$pointArgs[" . $index . "]";
                    $index++;
                }
                $names = '[' . implode(',', $names) . ']';
                $call_parent=$isInterface?'false':"parent::$methodName($pointArgs)";
                $methodItem = <<<EOF
        public function $methodName($methodArgs){
             \$names=$names;   
             
            \$point = new JoinPoint(\$this, __FUNCTION__,\$names,func_get_args(),$BeanClazz,function(\$pointArgs){
                return $call_parent;
                }
            );
            \$action = Aop::getAroundActions($BeanClazz, __FUNCTION__);
            //包围操作只可以添加一个
            if (\$action) {
                if (\$action[ 'call' ]) {
                    return \$action[ 'call' ](\$point);
                }
                \$action_name=  \$action[ 'action' ];
                return Ioc::get(\$action[ 'class' ])->\$action_name(\$point);
            }
            //前置操作
            \$actions = Aop::getBeforeActions($BeanClazz, __FUNCTION__);
            try{
                if(\$actions){
                    \$val = null;
                    foreach (\$actions as \$action) {
                        \$value_data=null;
                        if (\$action[ 'call' ]) {
                            try{
                                 \$value_data = \$action[ 'call' ](\$point);
                            }catch (\Throwable \$throwable){
                                if(!\$val){
                                    \$val=\$throwable;
                                }
                                \$point->hasThrow(true);
                                 \$point->hasReturn(true);
                            }
                        } else {
                           \$action_name=  \$action[ 'action' ];
                           try{
                                \$value_data =  Ioc::get(\$action[ 'class' ])->\$action_name(\$point);
                           }catch (\Throwable \$throwable){
                                if(!\$val){
                                    \$val=\$throwable;
                                }
                                \$point->hasThrow(true);
                                 \$point->hasReturn(true);
                           }
                        }
                          if (\$value_data&&!\$val) {
                            \$val = \$value_data;
                            \$point->hasReturn(true);
                        }
                    }
                    if(\$val instanceof \Throwable){
                        throw \$val;
                    }
                    if (\$val) {
                        return Aop::NuLL==\$val?null:\$val;
                    }
                }
                \$pointArgs=\$point->getArgs();
                \$val=$call_parent;
                 return Aop::NuLL==\$val?null:\$val;
            }catch (\Throwable \$e){
                \$val=\$e;
                 throw \$e;
            }finally{
                \$actions = Aop::getAfterActions($BeanClazz, __FUNCTION__);
                 if(\$actions){
                    \$value=null;
                    foreach (\$actions as \$action) {
                        \$value_data=null;
                        if (\$action[ 'call' ]) {
                            try{
                                 \$value_data = \$action[ 'call' ](\$point, \$val);
                            }catch (\Throwable \$throwable){
                                if(!\$value){
                                  \$value=\$throwable;
                                }
                                \$point->hasThrow(true);
                            }
                        } else {
                              \$action_name=  \$action[ 'action' ];
                              try{
                                    \$value_data =  Ioc::get(\$action[ 'class' ])->\$action_name(\$point, \$val);
                               }catch (\Throwable \$throwable){
                                    if(!\$value){
                                     \$value=\$throwable;
                                    }
                                    \$point->hasThrow(true);
                               }
                        }
                         if (\$value_data &&!\$value) {
                            \$value = \$value_data;
                        }
                        
                    }
                    if(\$value instanceof \Throwable){
                        throw \$value;
                    }
                    if(\$value){
                       return Aop::NuLL==\$value?null:\$value;
                    }
                }
            }
        }

EOF;
                $methodsStr .= $methodItem;
            }

            $extend_implements=$isInterface?'implements':'extends';


            $clazzStr = <<<EOF
<?php
namespace rap\aop\build$nameSpace;
use rap\aop\Aop;
use rap\aop\JoinPoint;
use rap\ioc\Ioc;
class $clazzSimpleName $extend_implements $clazzExtend{
         $methodsStr
}
EOF;

            $file_dir = $dir . str_replace("\\", DS, $nameSpace);
            if (!file_exists($file_dir)) {
                mkdir($file_dir, 0777, true);
            }
            $path = $file_dir . "/" . $clazzSimpleName . ".php";
            $file = fopen($path, "w");
            fwrite($file, $clazzStr);
        }

    }

    public static function clear() {
        $dir = ROOT_PATH . 'aop';
        self::deleteAll($dir);
    }

}