<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/24
 * Time: 20:54
 */

namespace GFPHP;

/**
 * 控制器基类,就放在这里用不用随你
 * Class Controller
 * @package GFPHP
 */
class Controller
{
    protected $view = false;

    protected $layout = FALSE;
    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->view = new Template();

        $this->setLayout($this->layout);
        $this->Init();
    }
    final public function __destruct()
    {
        // TODO: Implement __destruct() method.
        unset($this->view);
    }
    //---- 替代 __construct
    public function Init()
    {
    }

    final public function fetchTemplate($template, $cacheTime = FALSE, $cacheKey = FALSE)
    {
        return $content = $this->view->fetchTemplate($template, $cacheTime, $cacheKey);
    }

    /**
     * 传入模板内容,返回模板执行获取的内容
     * @param $templateCon
     * @return string
     */
    final function fetch($templateCon)
    {
        return $content = $this->view->fetch($templateCon);
    }
    //-----给模板赋值变量
    /**
     * 当为一个参数时,必须为数组形式
     * @param string|array $key
     */
    final function assign($key)
    {
        if (func_num_args() == 1) {
            $this->view->assign(func_get_arg(0));
        } elseif (func_num_args() == 2) {
            $this->view->assign(func_get_arg(0), func_get_arg(1));
        }
    }

    /**
     * 设置layout布局文件，设置为false将会不使用
     *
     * @param bool|string $layout
     */
    final function setLayout($layout = FALSE){
        return $this->view->setLayout($layout);
    }

    /**
     * 编译当前行为模板
     * @param bool $cacheTime
     * @param bool $cacheKey
     * @return mixed|String
     */
    final function Display($cacheTime = FALSE, $cacheKey = FALSE)
    {
        $this->Assign('_ACT', ['controller' => CONTROLLER_NAME, 'method' => METHOD_NAME]);
        /** @var string $template */
        return $this->view->display(':'.METHOD_NAME, $cacheTime, $cacheKey);
    }
}