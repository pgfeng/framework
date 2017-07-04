<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/24
 * Time: 20:43
 */

namespace GFPHP;

use GFPHP\Router;


/**
 * DEBUG类
 * 存放程序运行信息
 * 方便调试项目使用
 * 创建日期 2014-08-08 15:20 PGF 一年前写的挪过来基本没动
 */
class Debug
{
    static $startTime;
    static $stopTime;
    static $msg = [];
    static $sqls = [];
    static $include = [];
    static $debugs = [];
    static $errors = [];
    static $length;

    //-------添加程序执行信息--------
    static function add($msg, $type = 0)
    {

        switch ($type) {
            case 0:
                self::$msg[] = $msg;                            //把运行信息添加进去
                Logger::getInstance()->info($msg);
                break;
            case '1':
                self::$debugs[] = $msg;                        //调试信息
                Logger::getInstance()->debug($msg);
                break;
            case '2':
                self::$sqls[] = $msg;                            //把sql语句添加进去
                Logger::getInstance()->info('运行SQL ' . $msg);
        }
    }


    //-------获取开始微秒值-----------
    static function start()
    {
        header('X-Powered-By:GFPHP');
        if (Config::config('gzip')) {
            ob_start('ob_gzhandler');
        }
        Logger::getInstance()->info('请求开始 ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . ' ' . $_SERVER['SERVER_PROTOCOL']);
        Logger::getInstance()->info('GET参数', (array)$_GET);
        Logger::getInstance()->info('POST参数', (array)$_POST);
        Logger::getInstance()->info('POST源数据', (array)file_get_contents('php://input'));
        self::$startTime = microtime(TRUE);
    }

    /**
     * @param $msg
     */
    public static function debug($msg)
    {
        self::add($msg, 1);
    }

    //在脚本结束处调用获取脚本结束时间的微秒值

    /**
     *
     */
    static function stop()
    {
        self::$stopTime = microtime(TRUE);   //将获取的时间赋给成员属性$stopTime
        if (Config::config('develop_mod') && Config::debug('debugbar')) {
            //如果是开发模式并且已经开启debugbar
            include __DIR__ . DIRECTORY_SEPARATOR . 'debugbar.html';
        }
        Logger::getInstance()->info('运行结束 ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . ' 耗时:' . self::spent() . ' 内存:' . round((memory_get_usage() / 1024), 4) . ' kb');
        if (extension_loaded('zlib') && Config::config('gzip')) ob_end_flush();
    }

    static function getRuntime()
    {
        return round((microtime(TRUE) - self::$startTime), 4);  //计算后以4舍5入保留4位返回
    }

    /**
     * 获取执行的SQL
     *
     * @param int|bool $index 如果传入参数,获取倒数第$index条执行的SQL,否则获取所有的SQL,$index从0开始
     *
     * @return mixed
     */
    static function getSql($index = FALSE)
    {
        $sql_count = count(self::$sqls);
        if ($index !== FALSE && is_numeric($index)) {
            if ($index > $sql_count) {
                return self::$sqls[0];
            } else {
                return self::$sqls[$sql_count - $index];
            }
        } else {
            return self::$sqls;
        }
    }

    /**
     * 计算执行时间
     * @return float
     */
    static function spent()
    {

        return round((self::$stopTime - self::$startTime), 4);  //计算后以4舍5入保留4位返回

    }
}