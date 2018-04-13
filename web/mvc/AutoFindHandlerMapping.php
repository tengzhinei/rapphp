<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/1
 * Time: 下午4:20
 */
namespace rap\web\mvc;

use rap\web\Request;
use rap\web\Response;

class AutoFindHandlerMapping implements HandlerMapping{

    public $separator="/";
    public $controllerDir="controller";
    public $controllerPostfix="Controller";
    public $prefixArr=[];

    public function prefix($prefix,$dir){
        $dir=str_replace('/','\\',$dir);
        $this->prefixArr[$prefix]=$dir;
    }

    public function map(Request $request, Response $response){
        $path=$request->path();
        $prefix="";
        $dir="";
        $find=false;
        foreach ($this->prefixArr as $toPrefix=>$toDir) {
            if(strpos($path, $toPrefix."/") === 0&&strlen($toPrefix)>strlen($prefix)){
                $prefix=$toPrefix;
                $dir=$toDir;
                $find=true;
            }
        }
        if(!$find){
            //内有定义直接返回
            return null;
        }
        $path = substr($path,strlen($prefix));
        $path = str_replace($this->separator, '|', $path);
        $array=explode('|',$path);
//        $items=[];
//        $search=[];
//        foreach ($array as $item) {
//            if($item==(int)$item){
//                $search[]=$item;
//            }else{
//                $items[]=$item;
//            }
//        }
//        $request->search($search);
//        $array=$items;
        if(count($array)!=2){
            array_splice($array,count($array)-2,0,[$this->controllerDir]);
        }
        $method=array_pop($array);
        if(count($array)!=1) {
            $array[count($array)-1]=ucfirst($array[count($array)-1]);
            $classPath = $dir . implode('\\', $array) . $this->controllerPostfix;
        }else{
            $classPath = $dir . implode('\\', $array);
        }
        $handlerAdapter=new ControllerHandlerAdapter($classPath,$method);
        $handlerAdapter->pattern($path);
        return $handlerAdapter;
    }

}