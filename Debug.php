<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/24
 * Time: 20:43
 */

namespace GFPHP;



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
    /**
     * @var \Vendor\PhpConsole
     */
    static $PhpConsole;

    //-------添加程序执行信息--------
    static function add ( $msg, $type = 0 )
    {
        if ( !Config::config ( 'debug' ) )
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

    /**
     * 抓取致命错误
     */
    static function fatalError ()
    {
        $e = error_get_last ();
        self::$PhpConsole->error ( $e, '错误' );
        if ( Config::debug ( 'debug' ) && $e ) {
            switch ( $e[ 'type' ] ) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    ob_end_clean ();
                    self::halt ( $e );
                    break;
            }
        }
    }

    /**
     * 抛出错误信息
     *
     * @param bool $error
     * @param int  $index
     */
    static function halt ( $error = FALSE, $index = 1 )
    {
        $e = [ ];
        if ( !$error || !is_array ( $error ) ) {
            if ( is_string ( $error ) ) {
                $message = $error;
                $error = debug_backtrace ()[ $index ];
                $error [ 'message' ] = $message;
            }
        }
        if ( Config::config ( 'debug' ) ) {
            //调试模式下输出错误信息
            if ( !is_array ( $error ) ) {
                $error_page = Config::debug ( 'error_redirect' );
                if ( !empty( $error_page ) ) {
                    redirect ( $error_page );
                }
                $trace = debug_backtrace ();
                $e[ 'message' ] = $error;
                $e[ 'file' ] = isset( $trace[ 0 ][ 'file' ] ) ? $trace[ 0 ][ 'file' ] : '';
                $e[ 'line' ] = isset( $trace[ 0 ][ 'line' ] ) ? $trace[ 0 ][ 'line' ] : '';
                ob_start ();
                debug_print_backtrace ();
                $e[ 'trace' ] = ob_get_clean ();
            } else {
                $e = $error;
            }
        }
        $template_c_path = str_replace ( '/', DIRECTORY_SEPARATOR, __ROOT__ . parseDir ( Config::config ( 'app_dir' ), Config::cache ( 'cache_dir' ), Config::template ( 'view_c_dir' ), Config::template ( 'view_name' ) ) );
        $e[ 'type' ] = FALSE;
        if ( isset( $e[ 'file' ] ) )
            if ( ( $path = str_replace ( $template_c_path, '', $e[ 'file' ] ) ) != $e[ 'file' ] ) {
                $e[ 'message' ] = '模板错误 : ' . $e[ 'message' ];
                $e[ 'file' ] = str_replace ( '.php', Config::template ( 'view_suffix' ), $path );
                $e[ 'type' ] = 'template';
            }

        if ( $e[ 'type' ] == 'template' ) {
            $content = '======================== ' . date ( 'Y-m-d H:i:s' ) . ' TEMPLATE ERROR MESSAGE ========================' . PHP_EOL;
            $content .= '== ERROR PATH : ' . $e[ 'file' ] . PHP_EOL;
            $content .= '== ERROR LINE : ' . $e[ 'line' ] . PHP_EOL;
            $content .= '== ERROR MESSAGE : ' . $e[ 'message' ] . PHP_EOL;
        } else {
            $content = '======================== ' . date ( 'Y-m-d H:i:s' ) . ' APPLICATION ERROR MESSAGE =====================' . PHP_EOL;
            if ( isset( $e[ 'file' ] ) )
                $content .= '== ERROR PATH : ' . $e[ 'file' ] . PHP_EOL;
            if ( isset( $e[ 'line' ] ) )
                $content .= '== ERROR LINE : ' . $e[ 'line' ] . PHP_EOL;
            $content .= '== ERROR MESSAGE : ' . $e[ 'message' ] . PHP_EOL;
        }

        $content .= '=============================================================================================' . PHP_EOL . PHP_EOL;
        logMessage ( $e[ 'type' ] === 'template' ? 'template' : 'application', $content );
        self::$PhpConsole->error ( $content, $e[ 'type' ] );
        // 包含异常页面模板
        IF ( Config::debug ( 'debug' ) ) {
            $exceptionFile = Config::config ( 'core_dir' ) . DIRECTORY_SEPARATOR . 'Tpl' . DIRECTORY_SEPARATOR . 'Exception.php';
            require $exceptionFile;
        }
        self::stop ();
        exit;
    }

    //-------获取开始微秒值-----------
    static function start ()
    {
        header ( 'X-Powered-By:GFPHP' );
        if ( Config::config ( 'gzip' ) ) {
            ob_start ( 'ob_gzhandler' );
        }
        self::$PhpConsole = new PhpConsole;
//        new Vendor\PhpConsole();
        register_shutdown_function ( 'GFPHP\Debug::fatalError' );
        set_error_handler ( 'GFPHP\Debug::appError' );
        self::$startTime = microtime ( TRUE );
    }

    /**
     * 此方法处理非致命错误
     *
     * @param $errno
     * @param $errstr
     *
     * @internal param $errfile
     * @internal param $errline
     */
    static function appError ( $errno, $errstr )
    {
        self::$PhpConsole->error ( $errstr, '错误' );
        if ( Config::debug ( 'debug' ) == TRUE && in_array ( $errno, Config::debug ( 'app_listen_error' ) ) ) {
            ob_end_clean ();
            self::halt ( $errstr );
        }
    }

    //在脚本结束处调用获取脚本结束时间的微秒值

    static function stop ()
    {
        self::$stopTime = microtime ( TRUE );   //将获取的时间赋给成员属性$stopTime

        if ( Config::config ( 'debug' ) )                                                        //显示DEBUG信息
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
        self::$PhpConsole->debug ( $runTime, '运行耗时' );
        self::$PhpConsole->debug ( number_format ( 1 / $runTime, 2 ) . 'rps/s', '吞吐率' );
        self::$PhpConsole->debug ( round ( ( memory_get_usage () / 1024 ), 4 ) . ' kb', '内存占用' );
        self::$PhpConsole->debug ( self::$msg, '运行信息' );
        self::$PhpConsole->debug ( self::$include, '包含文件' );

        IF ( self::$sqls )
            self::$PhpConsole->debug ( self::$sqls, '运行SQL' );
    }

    static function spent ()
    {

        return round ( ( self::$stopTime - self::$startTime ), 4 );  //计算后以4舍5入保留4位返回

    }
}