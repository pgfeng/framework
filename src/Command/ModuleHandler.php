<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/7/0010
 * Time: 下午 2:33
 */

namespace GFPHP\Command;

/**
 * 模块生成
 * Class ModuleHandler
 * @package GFPHP\Command
 */
class ModuleHandler extends Handler
{
    public $name = 'module';

    /**
     * @param $argv
     * @return mixed
     */
    function handler($argv)
    {
        $this->argv = $argv;
        $this->help();
        return;
    }

    /**
     * @return mixed
     */
    function help()
    {
        if (!isset($this->argv[0]) || is_null($this->argv[0])) {
            $this->argv[0] = $this->command->getStdin("请输入应用名称:")[0];
        }
        if (!isset($this->argv[1]) || is_null($this->argv[1])) {
            $this->argv[1] = $this->command->getStdin("请输入模块名称:")[0];
        }
        if (!isset($this->argv[2]) || is_null($this->argv[2])) {
            $this->argv[2] = $this->command->getStdin("请输入控制器名称:")[0];
        }
        $this->buildModule();
        return;
    }

    /**
     * 生成模块
     */
    private function buildModule()
    {
        $this->command->Handler['controller']->handler([$this->argv[0], $this->argv[1], $this->argv[2], $this->argv[3]]);
        $argv = [];
        if ($this->argv[4])
            $argv[] = $this->argv[4];
        $this->command->Handler['model']->handler($argv);
    }
}