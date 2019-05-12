<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/2/5
 * Time: 下午10:16
 */

namespace rap\console;


use rap\config\Config;
use rap\console\command\AopFileBuild;
use rap\console\command\RecordBuild;
use rap\ioc\Ioc;
use rap\swoole\web\SwooleHttpServer;
use rap\swoole\websocket\WebSocketServer;

class Console {

    /**
     * 默认命令行
     * @var array
     */
    private $defaultCommand = [];


    public function _initialize() {
        $this->addConsole(Ioc::get(SwooleHttpServer::class));
        $this->addConsole(Ioc::get(WebSocketServer::class));
        $this->addConsole(Ioc::get(RecordBuild::class));
        $this->addConsole(Ioc::get(AopFileBuild::class));
        $cmds = Config::getFileConfig()[ 'cmds' ];
        if ($cmds) {
            foreach ($cmds as $cmd) {
                try {
                    $this->addConsole(Ioc::get($cmd));
                } catch (\Error $exception) {
                    echo "对应的" . $cmd . "命令不存在,请检查 config.php的 cmds配置";
                }
            }
        }
    }

    /**
     * 添加命令行
     *
     * @param object $command
     */
    public function addConsole($command) {
        /* @var $command Command */
        $command->configure();
        $name = $command->name();
        $this->defaultCommand[ $name ] = $command;
    }

    /**
     * 执行命令行
     *
     * @param $argv
     */
    public function run($argv) {

        if (count($argv) == 1) {
            $this->help();
            return;
        }
        array_shift($argv);
        $command = array_shift($argv);
        $params = [];
        for ($i = 0; $i < count($argv); $i += 2) {
            $key = $argv[ $i ];
            $value = $argv[ $i + 1 ];
            if ($value === null || strpos('-', $value) === 0) {
                $params[ substr($key, 1) ] = true;
                $i--;
                continue;
            }
            if ($value == 'true') {
                $value = true;
            }
            if ($value == 'false') {
                $value = false;
            }
            $params[ substr($key, 1) ] = $value;
        }
        if (strpos($command, '-') === 0) {
            $this->help();
            return;
        }
        /* @var $command_obj Command */
        $command_obj = $this->defaultCommand[ $command ];
        if (key_exists('h', $params)) {
            $command_obj->help();
            return;
        }
        $this->invoke($command_obj, $params);
    }


    public function invoke($command, $command_params) {
        $method = new \ReflectionMethod(get_class($command), 'run');
        $args = [];
        if ($method->getNumberOfParameters() > 0) {
            $params = $method->getParameters();
            /* @var $param \ReflectionParameter */
            foreach ($params as $param) {
                $name = $param->getName();
                $default = null;
                if ($param->isDefaultValueAvailable()) {
                    $default = $param->getDefaultValue();
                }
                if (key_exists($name, $command_params)) {
                    $args[] = $command_params[ $name ];
                } else {
                    $args[] = $default;
                }
            }
        }
        $method->invokeArgs($command, $args);
    }

    /**
     * 打印帮助
     */
    public function help() {
        $this->writeln("");
        $this->writeln("           欢迎使用 rap 命令行工具");
        $this->writeln("");
        $this->writeln("语法结构:php index.php 命令 参数格式(-s xxx -m ssss)");
        $this->writeln("         php index.php 查看所有命令");
        $this->writeln("         php index.php 命令 -h 查看命令的帮助");
        $this->writeln("");
        $this->writeln("所有命令:");
        /* @var $command Command */
        foreach ($this->defaultCommand as $command) {
            $this->writeln("        " . $command->name() . '  ' . $command->asName);
        }
        $this->writeln("");
    }

    /**
     * 写入
     *
     * @param string $msg
     */
    protected function writeln($msg) {
        echo "  " . $msg;
        echo "\n";
    }
}