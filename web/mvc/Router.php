<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/1
 * Time: 下午9:43
 */

namespace rap\web\mvc;


use rap\web\Request;

class Router{

    private $patterns;

    private $intVar=[];
    private $intVarContain=[];
    private $lettersVar=[];
    private $regexVar=[];
    private $routerPattern;
    private $groups;
    private $miss;
    /**
     * @param $url string 规则
     * @return  RouterPattern
     */
    public function when($url){
        $pattern=new RouterPattern($url);
        $this->patterns[]=$pattern;
        $this->routerPattern[]=$pattern;
        return $pattern;
    }

    /**
     * 变量是纯数字
     * @param $key
     * @return $this
     */
    public function intVar($key){
        $this->intVar[]=$key;
        return $this;
    }

    /**
     *
     * @param $key
     * @return $this
     */
    public function intVarContain($key){
        $this->intVarContain[]=$key;
        return $this;
    }

    /**
     * 变量是字母
     * @param $key
     * @return $this
     */
    public function lettersVar($key){
        $this->lettersVar[]=$key;
        return $this;
    }

    /**
     * 变量符合正则
     * @param $key
     * @param $regex
     * @return $this
     */
    public function regexVar($key,$regex){
        $this->regexVar[$key]=$regex;
        return $this;
    }

    public function group($groupStr){
        $group=new RouterGroup($groupStr);
        $group->intVar=$this->intVar;
        $group->intVarContain=$this->intVarContain;
        $group->lettersVar=$this->lettersVar;
        $group->regexVar=$this->regexVar;
        $this->groups[$groupStr]=$group;
        return $group;
    }

    /**
     * @param $ctr string/\Closure
     * @param string $action
     */
    public function whenMiss($ctr,$action=""){
        if($ctr instanceof \Closure){
            $this->miss=new ClosureHandlerAdapter($ctr);
        }else{
            $this->miss=new ControllerHandlerAdapter($ctr,$action);
        }


    }

    public function match(Request $request, $pathArray){
        //拥有分组
        /* @var $group RouterGroup */
        $group=$this->groups[$pathArray[0]];
        if($group){
            array_shift($pathArray);
            $adapter=$group->match($request,$pathArray);
            if($adapter)return $adapter;
        }

        /* @var $pattern RouterPattern  */
        foreach ($this->routerPattern as $pattern) {
            $adapter=$pattern->match($request,$pathArray);
            if($adapter)return $adapter;
        }
        return $this->miss;


    }


}