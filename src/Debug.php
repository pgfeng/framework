<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/24
 * Time: 20:43
 */

namespace GFPHP;
use PhpConsole;


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
    static $msg = [ ];
    static $sqls = [ ];
    static $include = [ ];
    static $length;
    static $Connector;
    /**
     * @var bool| PhpConsole\Handler
     */
    static $Handler = false;

    //-------添加程序执行信息--------
    static function add ( $msg, $type = 0 )
    {
        if ( !Config::debug ( 'debug' ) )
            return;
        switch ( $type ) {
            case 0:
                self::$msg[] = $msg;                            //把运行信息添加进去
                break;
            case '1':
                self::$include[] = $msg;                        //把包含文件添加进去
                break;
            case '2':
                self::$sqls[] = $msg;                            //把sql语句添加进去
        }
    }


    //-------获取开始微秒值-----------
    static function start ()
    {
        header ( 'X-Powered-By:GFPHP' );
        if ( Config::config ( 'gzip' ) ) {
            ob_start ( 'ob_gzhandler' );
        }
        $Connector = PhpConsole\Connector::getInstance();
        $isActiveClient = $Connector->isActiveClient();
        if($isActiveClient){
            $Connector->setPassword(Config::debug('password'));
            $Handler = PhpConsole\Handler::getInstance();
            $Handler->start();
            $Handler->checkFatalErrorOnShutDown();
            self::$Connector = $Connector;
            self::$Handler = $Handler;
        }
        self::$startTime = microtime ( TRUE );
    }
    public static function debug($msg,$tag){
        self::$Handler->debug($msg,$tag,TRUE);
    }
    public static function error($msg,$tag){
        self::$Handler->handleError($tag,$msg);
    }

    //在脚本结束处调用获取脚本结束时间的微秒值

    /**
     *
     */
    static function stop ()
    {
        self::$stopTime = microtime ( TRUE );   //将获取的时间赋给成员属性$stopTime

        if ( Config::debug ( 'debug' ) )                                                        //显示DEBUG信息
            Debug::message ();
        if ( extension_loaded ( 'zlib' ) && Config::config ( 'gzip' ) ) @ob_end_flush ();
        exit;
    }

    static function getRuntime ()
    {
        return round ( ( microtime ( TRUE ) - self::$startTime ), 4 );  //计算后以4舍5入保留4位返回
    }

    /**
     * 获取执行的SQL
     *
     * @param int|bool $index 如果传入参数,获取倒数第$index条执行的SQL,否则获取所有的SQL,$index从0开始
     *
     * @return mixed
     */
    static function getSql($index = FALSE){
        $sql_count = count(self::$sqls);
        if($index!==FALSE && is_numeric($index)) {
            if ( $index > $sql_count ) {
                return self::$sqls[ 0 ];
            } else {
                return self::$sqls[ $sql_count - $index ];
            }
        }else{
            return self::$sqls;
        }
    }

    static function message ()
    {
        $runTime = self::spent ();
        self::debug ( $runTime, '运行耗时' );
        self::debug ( number_format ( 1 / $runTime, 2 ) . 'rps/s', '吞吐率' );
        self::debug ( round ( ( memory_get_usage () / 1024 ), 4 ) . ' kb', '内存占用' );
        self::debug ( self::$msg, '运行信息' );
        self::debug ( self::$include, '包含文件' );

        IF ( self::$sqls )
            self::debug ( self::$sqls, '运行SQL' );
    }

    static function spent ()
    {

        return round ( ( self::$stopTime - self::$startTime ), 4 );  //计算后以4舍5入保留4位返回

    }
}