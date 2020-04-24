<?php

namespace rap\util\rate_limit;


/**
 * 空实现
 * 防止缓存没有配成 redis 出现错误
 * @author: 藤之内
 */
class EmptyRateLimter implements IRateLimiter {


    public function get($id = '') {

        return true;
    }

    public function remove($id = '') {

    }

    public function check($id = '') {
        return true;
    }


}