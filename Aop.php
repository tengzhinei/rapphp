<?php
namespace rap;
/**
*tengzhinei
*/
class Aop  {

    static private $beforeActions=array();
    static private $afterActions=array();
    static private $aroundActions=array();
    static private $range=0;

    /**
     * 包围时只能添加一个以最后一个为准
     * @param $clazz
     * @param $actions
     * @param $aroundClazz
     * @param $warpAction
     * @param null $call
     */
    public static function around($clazz,$actions,$aroundClazz,$warpAction,$call=null){

        $actions= static::actionsBuild($actions);
        if(!isset(static::$aroundActions[$clazz])){
            static::$aroundActions[$clazz]=array();
        }
        $info=array('methods'=>$actions['methods'],'class'=>$aroundClazz,'action'=>$warpAction,"call"=>$call,"range"=>static::$range);
        static::$range++;
        static::$aroundActions[$clazz][$actions['type']]=array();
        static::$aroundActions[$clazz][$actions['type']][]=$info;
    }

    /**
     * 方法执行前调用
     * @param $clazz
     * @param $actions
     * @param $beforeClazz
     * @param $warpAction
     * @param null $call
     */
    public static function before($clazz,$actions,$beforeClazz,$warpAction,$call=null){
        $actions= static::actionsBuild($actions);
        if(!isset(static::$beforeActions[$clazz])){
            static::$beforeActions[$clazz]=array();
        }
        if(!isset(static::$beforeActions[$clazz][$actions['type']])){
            static::$beforeActions[$clazz][$actions['type']]=array();
        }
        $info=array('methods'=>$actions['methods'],'class'=>$beforeClazz,'action'=>$warpAction,"call"=>$call,"range"=>static::$range);
        static::$range++;
        static::$beforeActions[$clazz][$actions['type']][]=$info;
    }

    private static function actionsBuild($actions){
        if(!array_key_exists('methods',$actions)){
            $actions=array("type"=>"only","methods"=>$actions);
        }
        if(!array_key_exists('type',$actions)){
            $actions['type']="only";
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
    public static function after($clazz,$actions,$afterClazz,$warpAction,$call=null){
        $actions= static::actionsBuild($actions);
        if(!isset(static::$afterActions[$clazz])){
            static::$afterActions[$clazz]=array();
        }
        if(!isset(static::$afterActions[$clazz][$actions['type']])){
            static::$afterActions[$clazz][$actions['type']]=array();
        }
        $info=array('methods'=>$actions['methods'],'class'=>$afterClazz,'action'=>$warpAction,"call"=>$call,"range"=>static::$range);
        static::$range++;
        static::$afterActions[$clazz][$actions['type']][]=$info;
    }

    /**
     * 获取某方法的所有的前置方法
     * @param $clazz
     * @param $action
     * @return array|null
     */
    public static function getBeforeActions($clazz,$action){
        if(static::$beforeActions[$clazz]){
            return static::buildActions(static::$beforeActions[$clazz],$action);
        }
        return null;
    }

    private static function buildActions(&$wareactions,$action){
        $actions=array();
        if(array_key_exists('only',$wareactions)){
            $acs=$wareactions['only'];
            foreach ($acs as $ac){
                if(in_array($action,$ac['methods'])){
                    $actions[]=array('class'=>$ac['class'],"action"=>$ac['action'],"call"=>$ac['call'],"range"=>$ac['range']);
                }
            }
        }
        if(array_key_exists('except',$wareactions)){
            $acs=$wareactions['except'];
            foreach ($acs as $ac){
                if(!in_array($action,$ac['methods'])){
                    $actions[]=array('class'=>$ac['class'],"action"=>$ac['action'],"call"=>$ac['call'],"range"=>$ac['range']);
                }
            }
        }
        if(array_key_exists('start',$wareactions)){
            $acs=$wareactions['start'];
            foreach ($acs as $ac){
                foreach ($ac['methods'] as $method){
                    if(strpos($action, $method) === 0){
                        $actions[]=array('class'=>$ac['class'],"action"=>$ac['action'],"call"=>$ac['call'],"range"=>$ac['range']);
                    }
                }
            }
        }
        if(array_key_exists('end',$wareactions)){
            $acs=$wareactions['end'];
            foreach ($acs as $ac){
                foreach ($ac['methods'] as $method){
                    if(strpos($action, $method)+strlen($method)=== strlen($action)){
                        $actions[]=array('class'=>$ac['class'],"action"=>$ac['action'],"call"=>$ac['call'],"range"=>$ac['range']);
                    }
                }
            }
        }
        if(array_key_exists('contains',$wareactions)){
            $acs=$wareactions['contains'];
            foreach ($acs as $ac){
                foreach ($ac['methods'] as $method){
                    if(strpos($action, $method) > 0){
                        $actions[]=array('class'=>$ac['class'],"action"=>$ac['action'],"call"=>$ac['call'],"range"=>$ac['range']);
                    }
                }
            }
        }
        foreach ($actions as $val){
            $vals[] = $val['range'];
        }
        array_multisort($vals,$actions,SORT_ASC);
        return  $actions;
    }


    /**
     * 获取某方法的所有的后置方法
     * @param $clazz
     * @param $action
     * @return array|null
     */
    public static function getAfterActions($clazz,$action){
        if(static::$afterActions[$clazz]){
            return static::buildActions(static::$afterActions[$clazz],$action);
        }
        return null;
    }

    /**
     * 获取某方法的包围方法,只有一个
     * @param $clazz
     * @param $action
     * @return array
     */
    public static function getAroundActions($clazz,$action){
        if(isset(static::$aroundActions[$clazz])&&static::$aroundActions[$clazz]){
            return static::buildActions(static::$aroundActions[$clazz],$action);
        }
        return null;
    }

    /**
     * 检测是否需要进行对象warp
     * @param $bean
     * @return bool
     */
    public static function needWarp($bean){
        if(array_key_exists(get_class($bean),static::$beforeActions)
        ||array_key_exists(get_class($bean),static::$afterActions)
        ||array_key_exists(get_class($bean),static::$aroundActions)){
            return true;
        }
        return false;
    }

}