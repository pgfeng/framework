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
    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->Init();
    }

    //---- 替代 __construct
    public function Init()
    {
    }

    /**
     * @param $template
     * @param bool $cacheTime
     * @param bool $cacheKey
     * @return String
     */
    final public function fetchTemplate($template, $cacheTime = FALSE, $cacheKey = FALSE)
    {
        return GFPHP::$Template->fetchTemplate($template, $cacheTime, $cacheKey);
    }

    /**
     * 传入模板内容,返回模板执行获取的内容
     * @param $templateCon
     * @return string
     */
    final public function fetch($templateCon)
    {
        return GFPHP::$Template->fetch($templateCon);
    }
    //-----给模板赋值变量

    /**
     * 当为一个参数时,必须为数组形式
     * @param string|array $key
     */
    final public function assign($key)
    {
        if (func_num_args() == 1) {
            GFPHP::$Template->assign(func_get_arg(0));
        } elseif (func_num_args() == 2) {
            GFPHP::$Template->assign(func_get_arg(0), func_get_arg(1));
        }
    }


    /**
     * 编译当前行为模板
     * @param bool $cacheTime
     * @param bool $cacheKey
     * @return mixed|String
     */
    final function display($cacheTime = FALSE, $cacheKey = FALSE)
    {
        $this->Assign('_ACT', ['module_name' => MODULE_NAME, 'controller_name' => CONTROLLER_NAME, 'method_name' => METHOD_NAME]);
        /** @var string $template */
        return GFPHP::$Template->display('@' . METHOD_NAME, $cacheTime, $cacheKey);
    }
}