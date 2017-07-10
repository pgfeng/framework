<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/7/0009
 * Time: 下午 2:33
 */

namespace GFPHP\Command;

use GFPHP\Config;
use GFPHP\Controller;


/**
 * 控制器生成
 * Class ControllerHandler
 * @package GFPHP\Command
 */
class ControllerHandler extends Handler
{
    public $name = 'controller';

    /**
     * 获取控制器位置
     */
    public function getControllerPath()
    {
        return BASE_PATH . $this->argv[0] . DIRECTORY_SEPARATOR . ucfirst($this->argv[1]) . DIRECTORY_SEPARATOR . ucfirst($this->argv[2]) . 'Controller.php';
    }

    /**
     * @return string
     */
    public function getController()
    {
        return ucfirst($this->argv[2]) . 'Controller';
    }

    /**
     * 获取命名空间
     */
    public function getNameSpace()
    {
        return $this->argv[0] . '\\' . ucfirst($this->argv[1]);
    }

    /**
     * @param $argv
     * @return mixed
     */
    function handler($argv)
    {
        $this->argv = $argv;

        if (!$argv || $argv[0] == '') {
            $this->argv[0] = $this->command->getStdin("请输入应用目录:")[0];
            return $this->handler($this->argv);
        }

        if (!isset($argv[1]) || $argv[1] == '') {
            $this->argv[1] = $this->command->getStdin("请输入模块名称:")[0];
            return $this->handler($this->argv);
        }

        if (!isset($argv[2]) || $argv[2] == '') {
            $this->argv[2] = $this->command->getStdin("请输入控制器名称:")[0];
            return $this->handler($this->argv);
        }
        if (!isset($argv[3]) || $argv[3] == '') {
            $this->argv[3] = $this->command->getStdin("请输入行为名称逗号分隔:")[0];
            return $this->handler($this->argv);
        }
        return $this->buildController();
    }

    /**
     * 生成控制器
     */
    public function buildController()
    {
        $controllerPath = $this->getControllerPath();
        if (file_exists($controllerPath)) {
            if (!preg_match('/yes/i', $this->command->getStdin('控制器已存在是否覆盖[yes or no]:')[0])) {
                return;
            }
        }
        $actions = $this->getActions();
        $nameSpace = $this->getNameSpace();
        $date = date('Y-m-d H:i:s');
        $controllerName = $this->getController();
        $ControllerContent = <<<CONTROLLER
<?php
/**
 * Created by GFPHP-GCLI.
 * Time: $date
 */
 
namespace $nameSpace;

use GFPHP\Controller;

/**
 * Class $controllerName
 * @package $nameSpace
 */
 class $controllerName extends Controller
{
$actions
}
CONTROLLER;
        mkPathDir($controllerPath);
        file_put_contents($controllerPath, $ControllerContent);
        $this->command->writeln('控制器' . $controllerPath . '生成成功!');
    }

    /**
     * 获取Action方法
     */
    public function getActions()
    {
        $actions = array_filter(explode(',', $this->argv[3]));
        foreach ($actions as &$action) {
            $action = strtolower($action);
        }
        unset($action);
        $actions = array_unique($actions);
        if (!$actions) {
            unset($this->argv[3]);
            return $this->handler($this->argv);
        }
        $actionContent = '';
        $methodSuffix = Config::router('methodSuffix');
        foreach ($actions as $action) {
            $actionContent .= <<<ACTION

    /**
     * {$action}{$methodSuffix}
     * @return mixed|String
     */
    public function {$action}{$methodSuffix}()
    {
        return \$this->display();
    }

ACTION;

        }
        return $actionContent;
    }

    /**
     * @return mixed
     */
    function help()
    {
        return;
    }
}