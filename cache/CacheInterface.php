<?php
namespace rap\cache;
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/6
 * Time: 上午10:19
 */
interface CacheInterface{

    public  function set($name,$value,$expire);

    public  function get($name,$default);

    public  function has($name);

    public  function inc($name, $step = 1);

    public  function dec($name, $step = 1);

    public  function remove($name);

    public function clear();

    public function hashSet($name, $key, $value);
    public function hashGet($name, $key,$default);

    public function hashRemove($name, $key);


}