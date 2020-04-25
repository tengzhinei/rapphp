<?php
/**
 * User: jinghao@duohuo.net
 * Date: 2019/5/26 5:24 PM
 */

namespace rap;

class ServerEvent
{
    
     const ON_APP_INIT            = 'onAppInit';
     const ON_BEFORE_SERVER_START = 'onBeforeServerStart';
     const ON_SERVER_START        = 'onServerStart';
     const ON_SERVER_WORK_START   = 'onServerWorkStart';
     const ON_REQUEST_START       = 'onRequestStart';
     const ON_REQUEST_DEFER       = 'onRequestDefer';
     const ON_SERVER_WORKER_STOP  = 'onServerWorkerStop';
     const ON_SERVER_SHUTDOWN     = 'onServerShutdown';
}
