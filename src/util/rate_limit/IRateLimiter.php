<?php


namespace rap\util\rate_limit;


interface IRateLimiter {



    /**
     * 获取令牌
     *
     * @param string $id 标识当前用户的身份,默认为 sessionId
     *
     * @return bool
     */
    public function get($id = '');



    /**
     * 删除令牌
     * 当用户已经在规定时间内完成任务了,应该主动删除令牌,让更多人进来
     *
     * @param string $id 标识当前用户的身份,默认为 sessionId
     */
    public function remove($id = '') ;


    /**
     * 检查是否拥有令牌
     * 如果有二级限速,只会检查二级
     *
     * @param string $id 标识当前用户的身份,默认为 sessionId
     *
     * @return bool
     */
    public function check($id = '') ;




}