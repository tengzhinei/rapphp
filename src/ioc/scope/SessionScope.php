<?php


namespace rap\ioc\scope;

/**
 * 同一个 session 内对象相同,默认有效期为 30 min
 * 实现SessionScope的类必须可以serialize,必须使用redis做缓存
 * @author: 藤之内
 */
interface SessionScope
{


}
