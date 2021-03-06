<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/2/5
 * Time: 下午10:18
 */

namespace rap\console;

use rap\aop\Event;
use rap\ServerEvent;

/**
 * 命令
 */
abstract class Command
{
    /**
     * @var string
     */
    public $name   = "";
    public $asName = "";
    public $des    = "";
    public $params = [];

    /**
     * 名称
     *
     * @param string $name
     *
     * @return mixed
     */
    public function name($name = "")
    {
        if (!$name) {
            return $this->name;
        }
        $this->name = $name;
        return $this;
    }

    /**
     * 显示名字
     *
     * @param $name
     *
     * @return $this
     */
    public function asName($name)
    {
        $this->asName = $name;
        return $this;
    }

    /**
     * 描述
     *
     * @param $des
     *
     * @return $this
     */
    public function des($des)
    {
        $this->des = $des;
        return $this;
    }

    /**
     * 参数
     *
     * @param string $name
     * @param string $opt
     * @param string $des
     * @param string $default
     *
     * @return $this
     */
    public function param($name, $opt, $des, $default)
    {
        $param = ['name' => $name,
                  'opt' => $opt,
                  'des' => $des,
                  'default' => $default];
        $this->params[] = $param;
        return $this;
    }

    /**
     * 设置配置信息
     * @return mixed
     */
    abstract public function configure();

    /**
     * 打印帮助信息
     */
    public function help()
    {
        $this->writeln("");
        $this->writeln($this->name . "  " . $this->asName);
        $this->writeln("参数说明");
        foreach ($this->params as $param) {
            $this->writeln("     -" . $param[ 'name' ] . ' ' . $param[ 'des' ]
                . ' ' . ($param[ 'opt' ] ? '可选' : '必选')
                . ($param[ 'default' ] ? (' 默认:' . $param[ 'default' ]) : ''));
        }
        $this->writeln("描述");
        $this->writeln($this->des);
        $this->writeln("");
    }


    /**
     * 写入
     *
     * @param $msg
     */
    protected function writeln($msg)
    {
        echo "  " . $msg;
        echo "\n";
    }

    /**
     * 完成需要在 worker 进程中完成的初始化
     */
    public function initWork()
    {
        Event::trigger(ServerEvent::ON_SERVER_WORK_START);
    }
}
