<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/11/27
 * Time: 下午4:18
 */

namespace rap\log;


class FileLog implements LogInterface{

    private $options=[
        'path'=>RUNTIME."log".DS,
        'logFormat'=>"%TIME% '%LEVEL%' '%MESSAGE%'\n",
        'splitFormat'=>'Y-m-d'
    ];

    public  function config($config){
      $this->options=array_merge($this->options,$config);
    }

    public function writeLog($level, $message){
        $log=  $this->logStr(time(),$level,$message);
        $file = fopen(getcwd().$this->options['path'].date($this->options['splitFormat'],time()).'.log', "a");
        fwrite($file, $log);
        fclose($file);
    }

    public function writeLogs(array $logs){
        $file = fopen($this->options['path'].date($this->options['splitFormat'],time()).'.log', "a");
        foreach ($logs as $log) {
            $log=  $this->logStr($log['time'],$log['level'],$log['message']);
            fwrite($file, $log);
        }
        fclose($file);
    }


    public function logStr($time,$level,$message){
        $log = str_replace(
            ['%TIME%', '%LEVEL%', '%MESSAGE%'],
            [
                $time,
                $level,
                $message,
            ], $this->options['logFormat']);
        return $log;
    }


}