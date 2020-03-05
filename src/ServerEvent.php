<?php
/**
 * User: jinghao@duohuo.net
 * Date: 2019/5/26 5:24 PM
 */

namespace rap;

class ServerEvent
{
    
     const onAppInit           = 'onAppInit';
     const onBeforeServerStart = 'onBeforeServerStart';
     const onServerStart       = 'onServerStart';
     const onServerWorkStart   = 'onServerWorkStart';
     const onRequestDefer      = 'onRequestDefer';
     const onServerWorkerStop  = 'onServerWorkerStop';
     const onServerShutdown    = 'onServerShutdown';
}
