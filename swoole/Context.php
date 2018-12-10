<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/5
 * Time: 下午10:44
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\swoole;


class Context {

    /**
     * 获取当前id
     * @return int
     */
    public static function id() {
        return CoContext::id();
    }

    public static function request() {
        return CoContext::getContext()->getRequest();
    }

    public static function response() {
        return CoContext::getContext()->getResponse();
    }

    public static function set($name, $bean = null) {
        CoContext::getContext()->set($name, $bean);
    }

    public static function get($name) {
        return CoContext::getContext()->get($name);
    }

    public static function remove($name) {
        CoContext::getContext()->remove($name);
    }

    public static function release() {
        CoContext::getContext()->release();
    }

}