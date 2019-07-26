<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/1
 * Time: 下午10:19
 */

namespace rap\web\mvc;


use rap\web\Request;

/**
 *具有匹配
 * @author: 藤之内
 */
class RouterPattern {

    private $method  = [];
    private $header  = [];
    private $url;
    private $ext     = [];
    private $extDeny = [];
    private $https   = false;

    private $int     = [];
    private $pattern = [];
    /**
     * @var HandlerAdapter
     */
    private $handlerAdapter;

    /**
     * RouterPattern constructor.
     *
     * @param $url
     */
    public function __construct($url) {
        $this->url = $url;
    }

    /**
     * @param Request $request
     * @param         $pathArray
     *
     * @return null|HandlerAdapter
     */
    public function match(Request $request, $pathArray) {

        $urlArray = explode('/', $this->url);
        if (count($urlArray) != count($pathArray)) {
            return null;
        }
        /**
         * 检查路径
         */
        $paramsKey = [];
        $c = count($urlArray);
        for ($i = 0; $i < $c; $i++) {
            $url = $urlArray[ $i ];
            if (strpos($url, ':') === 0) {
                $paramsKey[ substr($url, 1) ] = $pathArray[ $i ];
                continue;
            }
            $path = $pathArray[ $i ];
            if ($path != $url) {
                return null;
            }
        }
        //检查方法
        if (count($this->method) > 0 && !in_array($request->method(), $this->method)) {
            return null;
        }
        //检查请求头
        if (count($this->header) > 0) {
            foreach ($this->header as $head => $value) {
                if ($request->header($head) != $value) {
                    return null;
                }
            }
        }

        //后缀检查
        if (count($this->ext) > 0 && !in_array($request->ext(), $this->ext)) {
            return null;
        }
        if (count($this->extDeny) > 0 && in_array($request->ext(), $this->extDeny)) {
            return null;
        }
        if ($this->https && !$request->isSsl()) {
            return null;
        }

        foreach ($paramsKey as $param => $value) {
            //匹配数字
            if (in_array($param, $this->int)) {
                if (!is_numeric($value)) {
                    return null;
                }
                $value = (int)$value;
            }

            //匹配正则
            if (key_exists($param, $this->pattern)) {
                if (!preg_match($this->pattern[ $param ], $value)) {
                    return null;
                }
            }
            $this->handlerAdapter->addParam($param, $value);
        }

        return $this->handlerAdapter;
    }


    /**
     * 绑定控制器
     *
     * @param $ctr
     * @param $method
     */
    public function bindCtr($ctr, $method) {
        $this->handlerAdapter = new ControllerHandlerAdapter($ctr, $method);
    }

    /**
     * 绑定方法
     *
     * @param \Closure $closure
     */
    public function toDo(\Closure $closure) {
        $this->handlerAdapter = new ClosureHandlerAdapter($closure);
    }

    /**
     * 匹配后缀
     *
     * @param string $ext 后缀
     *
     * @return $this
     */
    public function ext($ext) {
        $this->ext[] = $ext;
        return $this;
    }

    /**
     * 禁止后缀
     *
     * @param string $ext 后缀
     *
     * @return $this
     */
    public function extDeny($ext) {
        $this->extDeny[] = $ext;
        return $this;
    }

    /**
     * 匹配https
     *
     * @param bool $https
     *
     * @return $this
     */
    public function https($https = true) {
        $this->https = $https;
        return $this;
    }

    /**
     * 匹配方法 get
     * @return $this
     */
    public function get() {
        $this->method[] = 'GET';
        return $this;
    }


    /**
     * 匹配方法 post
     * @return $this
     */
    public function post() {
        $this->method[] = "POST";
        return $this;
    }

    /**
     * 匹配方法 put
     * @return $this
     */
    public function put() {
        $this->method[] = "PUT";
        return $this;
    }

    /**
     * 匹配方法 patch
     * @return $this
     */
    public function delete() {
        $this->method[] = "DELETE";
        return $this;
    }

    /**
     * 匹配方法 patch
     * @return $this
     */
    public function patch() {
        $this->method[] = "PATCH";
        return $this;
    }

    /**
     * 匹配请求头
     *
     * @param string $key   键
     * @param string $value 值
     *
     * @return $this
     */
    public function header($key, $value) {
        $this->header[ $key ] = $value;
        return $this;
    }

    public function cache() {
        return $this;
    }

    /**
     * 匹配数字
     *
     * @param $key
     *
     * @return $this
     */
    public function int($key) {
        $this->int[] = $key;
        return $this;
    }

    /**
     * 空实现
     *
     * @param string $key
     *
     * @return $this
     */
    public function letters($key) {
        return $this;
    }

    /**
     * 匹配是否符合正则
     *
     * @param string $key     参数
     * @param string $pattern 表达式
     *
     * @return $this
     */
    public function pattern($key, $pattern) {
        $this->pattern[ $key ] = $pattern;
        return $this;
    }


}