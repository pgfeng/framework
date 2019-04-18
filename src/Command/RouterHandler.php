<?php
/**
 * Created by PhpStorm.
 * User: pgf
 * Date: 2019-04-18
 * Time: 14:52
 */

namespace GFPHP\Command;

use GFPHP\Router;

class RouterHandler extends Handler
{
    public $name = 'router';

    /**
     * @param $argv
     * @return mixed
     */
    function handler($argv)
    {
        if (!$argv[0]) {
            $this->argv[0] = $this->command->getStdin("请输入模块目录:")[0];
            return $this->handler($this->argv);
        } else {
            Router::buildAnnotation($argv[0]);
            $this->command->writeln('生成规则成功！');
        }
    }

    /**
     * @return mixed
     */
    function help()
    {
        $this->command->writeln('生成路由规则');
    }
}