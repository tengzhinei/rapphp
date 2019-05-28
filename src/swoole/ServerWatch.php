<?php
/**
 * User: jinghao@duohuo.net
 * Date: 2019/5/5 5:28 PM
 */

namespace rap\swoole;


use rap\config\Config;

/**
 * 监听服务器的变化
 */
class ServerWatch {

    public $last_time_id = 0;

    private $events = [IN_MODIFY => 'File Modified',
                       IN_MOVED_TO => 'File Moved In',
                       IN_MOVED_FROM => 'File Moved Out',
                       IN_CREATE => 'File Created',
                       IN_DELETE => 'File Deleted'];

    public function init($server) {
        $callback=function() use ($server) {
            //重启 worker 进车功能
            if ($this->last_time_id) {
                swoole_timer_clear($this->last_time_id);
            }
            $this->last_time_id = swoole_timer_after(2 * 1000, function() use ($server) {
                $this->last_time_id = 0;
                $server->reload();
            });
        };
        $dir = Config::get('swoole_http')[ 'auto_reload_dir' ];
        if(!$dir){
            foreach ($dir as $item) {
                $this->watchDir(ROOT_PATH.$item,$callback);
            }
        }else{
            $this->watchDir(APP_PATH,$callback);
        }
    }


    function watchDir($directory, $callback) {


        $my_event = array_sum(array_keys($this->events));
        $ifd = inotify_init();
        inotify_add_watch($ifd, $directory, $my_event);
        foreach ($this->getAllDirs($directory) as $dir) {
            inotify_add_watch($ifd, $dir, $my_event);
        }
        swoole_event_add($ifd, function($fd) use (&$callback) {
            $event_list = inotify_read($fd);
            foreach ($event_list as $arr) {
                $ev_mask = $arr[ 'mask' ];
                $ev_file = $arr[ 'name' ];
                if (isset($this->events[ $ev_mask ]) && strpos($ev_file, '.php') == strlen($ev_file) - 4) {
                    $callback($ev_file);
                }
            }
        });

    }

    // 使用迭代器遍历目录
    protected function getAllDirs($base) {
        $files = scandir($base);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $filename = $base . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filename)) {
                yield $filename;
                yield from $this->getAllDirs($filename);
            }
        }

    }
}