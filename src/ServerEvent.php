<?php
/**
 * User: jinghao@duohuo.net
 * Date: 2019/5/26 5:24 PM
 */

namespace rap;


class ServerEvent {
    
    public const onAppInit           = 'onAppInit';
    public const onBeforeServerStart = 'onBeforeServerStart';
    public const onServerStart       = 'onServerStart';
    public const onServerWorkStart   = 'onServerWorkStart';
    public const onRequestDefer      = 'onRequestDefer';
    public const onServerWorkerStop  = 'onServerWorkerStop';
    public const onServerShutdown    = 'onServerShutdown';


}