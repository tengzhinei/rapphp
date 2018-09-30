<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/9/9
 * Time: 上午10:44
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\util;


use rap\config\Config;
use rap\web\Request;

class Lang {

    /**
     * 提供默认语言包
     * @var array
     */
    static $sys_lang = [];

    /**
     * 应用默认语言包
     * @var array
     */
    static $app_lang = [];

    /**
     * 当前
     * @var string
     */
    private static $current_lang = '';

    /**
     * 加载系统语言包
     */
     private static function loadSys() {
        $lang = Config::get('app', 'lang', 'zh-cn');
        $lang_array1=[];
        if(in_array($lang,['zh-cn','en-us'])){
            $lang_array1 = include __DIR__ .DS. 'lang' . DS . $lang . ".php";
        }
        if (is_file(APP_PATH . 'lang' . DS . $lang . ".php")) {
            $lang_array2 = include APP_PATH . 'lang' . DS . $lang . ".php";
            foreach ($lang_array2 as $key => $items) {
                $lang_array1[ $key ] = array_merge($lang_array1[ $key ], $items);
            }
        }
        static::$sys_lang= $lang_array1;
    }

    /**
     * @param Request $request
     */
    public static function loadLand(Request $request){
         $lang_switch_on=Config::get('app','lang_switch_on');
         if(!$lang_switch_on)return;
        $lang=$request->get('lang');
        if(!$lang){
            $lang=$request->cookie('rap_lang');
        }
        if(!$lang){
            static::$current_lang='';
            return;
        }
        if($lang!=$request->cookie('rap_lang')){
           $response=$request->response();
           $response->cookie('rap_lang',$lang);
        }
        $lang_sys = Config::get('app', 'lang', 'zh-cn');
        if($lang==$lang_sys){
            static::$current_lang='';
            return;
        }
        static::$current_lang=$lang;
        $lang_array=static::$app_lang[$lang];
        if(!$lang_array){
            if(in_array($lang,['zh-cn','en-us'])){
                $lang_array1 = include __DIR__ .DS. 'lang' . DS . $lang . ".php";
            }

            if (is_file(APP_PATH . 'lang' . DS . $lang . ".php")) {
                $lang_array2 = include APP_PATH . 'lang' . DS . $lang . ".php";
                foreach ($lang_array2 as $key => $items) {
                    $lang_array1[ $key ] = array_merge($lang_array1[ $key ], $items);
                }
            }
            static::$app_lang[$lang]=$lang_array1;
        }
    }

    public static function get($moudle, $key,$vars=[]){
        $value = self::loadMsg($moudle,$key);
       if($vars){
           if (key($vars) === 0) {
               // 数字索引解析
               array_unshift($vars, $value);
               $value = call_user_func_array('sprintf', $vars);
           } else {
               // 关联索引解析
               $replace = array_keys($vars);
               foreach ($replace as &$v) {
                   $v = ":$v";
               }
               $value = str_replace($replace, $vars, $value);
           }
       }
       return $value;
    }

    /**
     * 获取信息
     * @param $moudle
     * @param $key
     *
     * @return string
     */
    private static function loadMsg($moudle, $key) {
        if(static::$current_lang){
            $lang=static::$app_lang[static::$current_lang];
            $array=$lang[$moudle];
            if($array){
               if($array[$key]){
                    return $array[$key];
<<<<<<< HEAD
=======

>>>>>>> dev
               }
            }
        }
        if(!static::$sys_lang){
           static::loadSys();
        }
        $array=static::$sys_lang[$moudle];
        if($array){
            return $array[$key];
        }
        return "";
    }


}