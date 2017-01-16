<?php
namespace rap;

/**
 *tengzhinei
 */
class Aop{

    static private $beforeActions = array();
    static private $afterActions  = array();
    static private $aroundActions = array();
    static private $range         = 0;

    /**
     * 包围时只能添加一个以最后一个为准
     * @param $clazz
     * @param $actions
     * @param $aroundClazz
     * @param $warpAction
     * @param null $call
     */
    public static function around($clazz, $actions, $aroundClazz, $warpAction, $call = null){

        $actions = static::actionsBuild($actions);
        if (!isset(static::$aroundActions[ $clazz ])) {
            static::$aroundActions[ $clazz ] = array();
        }
        $info = array('methods' => $actions[ 'methods' ], 'class' => $aroundClazz, 'action' => $warpAction, "call" => $call, "range" => static::$range);
        static::$range++;
        static::$aroundActions[ $clazz ][ $actions[ 'type' ] ] = array();
        static::$aroundActions[ $clazz ][ $actions[ 'type' ] ][] = $info;
    }

    /**
     * 方法执行前调用
     * @param $clazz
     * @param $actions
     * @param $beforeClazz
     * @param $warpAction
     * @param null $call
     */
    public static function before($clazz, $actions, $beforeClazz, $warpAction, $call = null){
        $actions = static::actionsBuild($actions);
        if (!isset(static::$beforeActions[ $clazz ])) {
            static::$beforeActions[ $clazz ] = array();
        }
        if (!isset(static::$beforeActions[ $clazz ][ $actions[ 'type' ] ])) {
            static::$beforeActions[ $clazz ][ $actions[ 'type' ] ] = array();
        }
        $info = array('methods' => $actions[ 'methods' ], 'class' => $beforeClazz, 'action' => $warpAction, "call" => $call, "range" => static::$range);
        static::$range++;
        static::$beforeActions[ $clazz ][ $actions[ 'type' ] ][] = $info;
    }

    private static function actionsBuild($actions){
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
     * @param $clazz
     * @param $actions
     * @param $afterClazz
     * @param $warpAction
     * @param null $call
     */
    public static function after($clazz, $actions, $afterClazz, $warpAction, $call = null){
        $actions = static::actionsBuild($actions);
        if (!isset(static::$afterActions[ $clazz ])) {
            static::$afterActions[ $clazz ] = array();
        }
        if (!isset(static::$afterActions[ $clazz ][ $actions[ 'type' ] ])) {
            static::$afterActions[ $clazz ][ $actions[ 'type' ] ] = array();
        }
        $info = array('methods' => $actions[ 'methods' ], 'class' => $afterClazz, 'action' => $warpAction, "call" => $call, "range" => static::$range);
        static::$range++;
        static::$afterActions[ $clazz ][ $actions[ 'type' ] ][] = $info;
    }

    /**
     * 获取某方法的所有的前置方法
     * @param $clazz
     * @param $action
     * @return array|null
     */
    public static function getBeforeActions($clazz, $action){
        if (static::$beforeActions[ $clazz ]) {
            return static::buildActions(static::$beforeActions[ $clazz ], $action);
        }
        return null;
    }

    private static function buildActions(&$wareactions, $action){
        $actions = array();
        if (array_key_exists('only', $wareactions)) {
            $acs = $wareactions[ 'only' ];
            foreach ($acs as $ac) {
                if (in_array($action, $ac[ 'methods' ])) {
                    $actions[] = array('class' => $ac[ 'class' ], "action" => $ac[ 'action' ], "call" => $ac[ 'call' ], "range" => $ac[ 'range' ]);
                }
            }
        }
        if (array_key_exists('except', $wareactions)) {
            $acs = $wareactions[ 'except' ];
            foreach ($acs as $ac) {
                if (!in_array($action, $ac[ 'methods' ])) {
                    $actions[] = array('class' => $ac[ 'class' ], "action" => $ac[ 'action' ], "call" => $ac[ 'call' ], "range" => $ac[ 'range' ]);
                }
            }
        }
        if (array_key_exists('start', $wareactions)) {
            $acs = $wareactions[ 'start' ];
            foreach ($acs as $ac) {
                foreach ($ac[ 'methods' ] as $method) {
                    if (strpos($action, $method) === 0) {
                        $actions[] = array('class' => $ac[ 'class' ], "action" => $ac[ 'action' ], "call" => $ac[ 'call' ], "range" => $ac[ 'range' ]);
                    }
                }
            }
        }
        if (array_key_exists('end', $wareactions)) {
            $acs = $wareactions[ 'end' ];
            foreach ($acs as $ac) {
                foreach ($ac[ 'methods' ] as $method) {
                    if (strpos($action, $method) + strlen($method) === strlen($action)) {
                        $actions[] = array('class' => $ac[ 'class' ], "action" => $ac[ 'action' ], "call" => $ac[ 'call' ], "range" => $ac[ 'range' ]);
                    }
                }
            }
        }
        if (array_key_exists('contains', $wareactions)) {
            $acs = $wareactions[ 'contains' ];
            foreach ($acs as $ac) {
                foreach ($ac[ 'methods' ] as $method) {
                    if (strpos($action, $method) > 0) {
                        $actions[] = array('class' => $ac[ 'class' ], "action" => $ac[ 'action' ], "call" => $ac[ 'call' ], "range" => $ac[ 'range' ]);
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
     * @param $clazz
     * @param $action
     * @return array|null
     */
    public static function getAfterActions($clazz, $action){
        if (static::$afterActions[ $clazz ]) {
            return static::buildActions(static::$afterActions[ $clazz ], $action);
        }
        return null;
    }

    /**
     * 获取某方法的包围方法,只有一个
     * @param $clazz
     * @param $action
     * @return array
     */
    public static function getAroundActions($clazz, $action){
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
     * @param $bean
     * @return bool
     */
    public static function needWarp($bean){
        if (array_key_exists($bean, static::$beforeActions) || array_key_exists($bean, static::$afterActions) || array_key_exists($bean, static::$aroundActions)) {
            return true;
        }
        return false;
    }

    public static function warpBean($clazz){
        if (self::needWarp($clazz)) {
            $name = "rap\\build\\" . $clazz . "_PROXY";
            $who = new $name;
        } else {
            $who = new $clazz;
        }
        return $who;
    }

    private static function deleteAll($path){
        $op = dir($path);
        while (false != ($item = $op->read())) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (is_dir($op->path . '/' . $item)) {
                static::deleteAll($op->path . '/' . $item);
                rmdir($op->path . '/' . $item);
            } else {
                unlink($op->path . '/' . $item);
            }

        }
    }

    public static function init($version){
        $file=str_replace("/Aop.php", "/build/build", __FILE__);
        if(file_exists($file)){
            $content = file_get_contents($file);
            //版本相同 返回
            if($content==$version.""){
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
    public static function buildProxy(){
        self::deleteAll(str_replace("/Aop.php", "/build", __FILE__));
        $dir = str_replace("/Aop.php", "/build", __FILE__);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $clazzs = array_unique(array_merge(array_keys(static::$beforeActions), array_keys(static::$beforeActions), array_keys(static::$aroundActions)));
        foreach ($clazzs as $clazz) {
            $reflection = new \ReflectionClass($clazz);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            $methodsStr = "";
            foreach ($methods as $method) {
                $around = self::getAroundActions($clazz, $method->getName());
                $after = self::getAfterActions($clazz, $method->getName());
                $before = self::getBeforeActions($clazz, $method->getName());
                if (!$around && !$after && !$before) {
                    continue;
                }

                $methodName = $method->getName();
                $BeanClazz = "'" . $clazz . "'";
                $methodArgs = "";
                $pointArgs = "";
                $index = 0;
                $params = $method->getParameters();
                foreach ($params as $param) {
                    if ($methodArgs) {
                        $methodArgs .= ",";
                        $pointArgs = ",";
                    }
                    $paramClazz = $param->getClass();
                    if ($paramClazz) {
                        $methodArgs .= "\\" . $paramClazz->getName() . " ";
                    }
                    $methodArgs .= "$" . $param->getName() . " ";

                    if ($param->isDefaultValueAvailable()) {
                        $value = $param->getDefaultValue();
                        $isStr = gettype($value) == "string";
                        $methodArgs .= "=";
                        if ($isStr) {
                            $methodArgs .= "\"";
                        }
                        $methodArgs . $param->getDefaultValue();
                        if ($isStr) {
                            $methodArgs .= "\"";
                        }
                    }
                    $pointArgs .= "\$pointArgs[" . $index . "]";
                    $index++;
                }
                $methodItem = <<<EOF
        public function $methodName($methodArgs){
            \$point = new JoinPoint(\$this, __FUNCTION__, func_get_args(),function(\$pointArgs){
                return parent::$methodName($pointArgs);
                }
            );
            \$action = Aop::getAroundActions($BeanClazz, __FUNCTION__);
            //包围操作只可以添加一个
            if (\$action) {
                if (\$action[ 'call' ]) {
                    return \$action[ 'call' ](\$point);
                }
                return Ioc::get(\$action[ 'class' ])->\$action[ 'action' ](\$point);
            }
            //前置操作
            \$actions = Aop::getBeforeActions($BeanClazz, __FUNCTION__);
            \$pointArgs=\$point->getArgs();
            if(\$actions){
                foreach (\$actions as \$action) {
                    if (\$action[ 'call' ]) {
                        return \$action[ 'call' ]($pointArgs);
                    } else {
                        Ioc::get(\$action[ 'class' ])->\$action[ 'action' ](\$point);
                    }
                }
            }
            \$val=parent::$methodName($pointArgs);
            //后置操作
            \$actions = Aop::getAfterActions($BeanClazz, __FUNCTION__);
             if(\$actions){
                foreach (\$actions as \$action) {
                    if (\$action[ 'call' ]) {
                        \$val = \$action[ 'call' ](\$point, \$val);
                    } else {
                        \$val = Ioc::get(\$action[ 'class' ])->\$action[ 'action' ](\$point, \$val);
                    }
                }
            }
            return \$val;
        }

EOF;
                $methodsStr .= $methodItem;
            }


            $nameSpace = "\\" . $reflection->getNamespaceName();
            $clazzSimpleName = $reflection->getShortName() . "_PROXY";
            $clazzExtend = "\\" . $clazz;
            $clazzStr = <<<EOF
<?php
namespace rap\build$nameSpace;
use rap\Aop;
use rap\Aop\JoinPoint;
use rap\Ioc;
class $clazzSimpleName extends $clazzExtend{
         $methodsStr
}
EOF;

            $dir = str_replace("/Aop.php", "/build" . str_replace("\\", "/", $nameSpace), __FILE__);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $path = $dir . "/" . $clazzSimpleName . ".php";
            $file = fopen($path, "w");
            fwrite($file, $clazzStr);
        }

    }

}