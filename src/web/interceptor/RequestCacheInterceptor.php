<?php


namespace rap\web\interceptor;

use rap\cache\Cache;
use rap\web\Request;
use rap\web\Response;

/**
 * 请求缓存
 * 可以对请求直接进行缓存,而不需要经过后面的控制器
 * @author: 藤之内
 */
class RequestCacheInterceptor implements Interceptor, AfterInterceptor {


    private $cache_urls = [];

    private $refresh_key = '';

    private $refresh_header = '';

    const CACHE_PRE = "request_cache:";

    public function cache($url, $time=60) {
        $this->cache_urls[ $url ] = $time;
    }

    /**
     * 根据get的参数刷新缓存
     * @param string $get_refresh get缓存的key
     */
    public function refreshGet($get_refresh){
        $this->refresh_key=$get_refresh;
    }

    /**
     * 根据请求头中的参数刷新缓存
     * @param $refresh_header
     */
    public function refreshHeader($refresh_header){
        $this->refresh_header=$refresh_header;
    }

    public function handler(Request $request, Response $response) {
        $path = $request->path();
        if (!key_exists($path, $this->cache_urls)) {
            return null;
        }
        $get = $request->get();
        $get_refresh = false;
        if ($this->refresh_key) {
            $get_refresh = $get[ $this->refresh_key ];
            unset($get[ $this->refresh_key ]);
        }
        sort($get);
        $key =self::CACHE_PRE. md5(serialize($get));
        $refresh_header=false;
        if($this->refresh_header){
            $refresh_header= $request->header($this->refresh_header);
        }
        if ($get_refresh||$refresh_header) {
            return null;
        }
        $data = Cache::get($key);
        if(!$data){
            return null;
        }
        $response->contentType($data['contentType']);
        $response->header('Rap-Request-HIT','HIT');
        return body($data['body']);
    }

    public function afterDispatcher(Request $request, Response $response) {
        $path = $request->path();
        if (!key_exists($path, $this->cache_urls)) {
            return null;
        }
        $get = $request->get();
        if ($this->refresh_key) {
            unset($get[ $this->refresh_key ]);
        }
        sort($get);
        $key = md5(serialize($get));
        $type = $response->contentType();
        $time=$this->cache_urls[$path];
        $response->header('Rap-Request-Cache',$time);
        Cache::set(self::CACHE_PRE.$key,[
            "contentType"=>$type,
            "body"=>$response->getContent()
        ],$time);

    }


}