<?php
namespace rap\help;
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/10/18
 * Time: 上午10:48
 */
class ArrayHelper{

    public static function copyArray($array,&$to){
        foreach ($array as $item=>$value) {
            if(is_array($to)){
                $to[$item]=$value;
            }else{
                $to->$item=$value;
            }
        }
    }

}