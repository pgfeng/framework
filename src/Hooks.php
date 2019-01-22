<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/24
 * Time: 20:40
 */

namespace GFPHP;


/**
 * Class Hooks                  钩子基础类
 * 钩子使用任何基类,比如Model Controller 但是需要处理要引用包
 * @package GFPHP               框架核心类
 * 用最少的代码做最不可能的事       @PGF
 * Time:2016-01-26 09:16
 */
class Hooks
{
    /**
     * @var array 默认Hooks参数,为框架基础钩子行为,为了正常运行,请勿修改
     */
    public static $Hooks = [
        'template_filter' => [
            [
                'Hooks' => \GFPHP\Hooks\GFPHP::class,
                'Func' => 'template_parse',
            ],
        ],
    ];
    public static $load = [];

    public static function init()
    {
        self::import(Config::hooks());
    }

    /**
     * 执行钩子行为
     *
     * @param $name
     * @param array $params
     * @return mixed | array | string
     */
    public static function call($name, $params = [])
    {
        if (isset(self::$Hooks[$name])) {
            $act = end(self::$Hooks[$name]);
            if (!isset($act['Hooks'])) {
                Debug::add('Hooks: 钩子配置错误,数组必须包含`Hooks`');
            } else {
                $hooks = new $act['Hooks'];
                $func = isset($act['Func']) ? $act['Func'] : Config::hooks('DefaultFunc');
                if (method_exists($hooks, $func)) {
                    Debug::add('Hooks: 钩子 ' . $act['Hooks'] . ' 函数 ' . $func . ' 运行.');

                    return call_user_func_array([$hooks, $func], $params);
                } else {
                    Debug::add('Hooks: 钩子 ' . $act['Hooks'] . ' 函数 ' . $func . ' 没有定义.');
                }
            }
        }

        return FALSE;
    }

    /**
     * 监听钩子行为
     *
     * @param $name
     * @param array $params
     * @return void
     */
    public static function listen($name, $params = [])
    {
        if (isset(self::$Hooks[$name])) {
            foreach (self::$Hooks[$name] as $act) {
                if (!isset($act['Hooks'])) {
                    Debug::add('Hooks: 钩子配置错误,数组必须包含`Hooks`');
                } else {
                    $hooks = new $act['Hooks'];
                    $func = isset($act['Func']) ? $act['Func'] : Config::Hooks('DefaultFunc');
                    if (method_exists($hooks, $func)) {
                        Debug::add('Hooks: 钩子 ' . $act['Hooks'] . ' 函数 ' . $func . ' 运行.');
                        call_user_func_array([$hooks, $func], $params);
                    } else {
                        Debug::add('Hooks: 钩子 ' . $act['Hooks'] . ' 函数 ' . $func . ' 没有定义.');
                    }
                }
            }
        }
    }

    /**
     * 执行过滤器
     *
     * @param $name
     * @param $params
     * @return array|string
     */
    public static function filter($name, $params)
    {
        if (isset(self::$Hooks[$name])) {
            foreach (self::$Hooks[$name] as $act) {
                if (!isset($act['Hooks'])) {
                    Debug::add('Hooks: 钩子配置错误,数组必须包含`Hooks`');
                } else {
                    $hooks = str_replace('/', '\\', $act['Hooks']);
                    $hooks = new $hooks;
                    $func = isset($act['Func']) ? $act['Func'] : Config::Hooks('DefaultFunc');
                    if (method_exists($hooks, $func)) {
                        Debug::add('Hooks: 钩子 ' . $act['Hooks'] . ' 函数 ' . $func . ' 运行.');
                        $res = call_user_func_array([$hooks, $func], $params);
                        $params = [$res];
                    } else {
                        Debug::add('Hooks: 钩子 ' . $act['Hooks'] . ' 函数 ' . $func . ' 没有定义.');
                    }
                }
            }
        }
        if (count($params) == 1)
            return end($params);
        else
            return $params;
    }


    /**
     * 添加钩子
     *
     * @param $HooksName
     * @param $Class
     * @param string $Func
     * @return bool
     */
    public static function add($HooksName, $Class, $Func = '')
    {
        if (!isset(self::$Hooks[$HooksName]))
            self::$Hooks[$HooksName] = [];
        $hooks = [
            'Hooks' => $Class,
            'Func' => $Func,
        ];
        if (!in_array($hooks, self::$Hooks[$HooksName])) {
            self::$Hooks[$HooksName][] = $hooks;

            return TRUE;
        }

        return FALSE;
    }

    /**
     * 导入钩子,格式参考 SELF::$Hooks
     *
     * @param $Hooks
     */
    public static function import($Hooks)
    {
        foreach ($Hooks as $k => $v) {
            foreach ($v as $a) {
                if (!isset($a['Hooks']))
                    continue;
                if (isset($a['Func']))
                    self::add($k, $a['Hooks'], $a['Func']);
            }
        }
    }
}
