<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/25
 * Time: 10:09
 */

namespace GFPHP;


/**
 * Class GFPHP
 * @package GFPHP
 */
class GFPHP
{
    /**
     * @var Template
     */
    public static $Template;
    /**
     * @var string $app_name
     */
    public static $app_name;

    /**
     * @param string $app_name
     */
    public static function init($app_name = 'app')
    {
        if (!defined('BASE_PATH'))
            exit('Not Define BASE_PATH');
        //==当前时间
        define('__NOW__', $_SERVER['REQUEST_TIME']);

        //==请求类型
        define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);

        //==是否为GET请求
        define('IS_GET', REQUEST_METHOD == 'GET' ? true : false);

        //==是否为POST请求
        define('IS_POST', REQUEST_METHOD == 'POST' ? true : false);

        //==是否为PUT请求
        define('IS_PUT', REQUEST_METHOD == 'PUT' ? true : false);

        //==是否为DELETE请求
        define('IS_DELETE', REQUEST_METHOD == 'DELETE' ? true : false);

        //==是否为AJAX请求
        define('IS_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) ? true : false);

        session_start();

        date_default_timezone_set('PRC');

        Debug::start();

        Cache::init();

        if (isset($_GET['_router'])) {
            define('__URI__', $_GET['_router']);
            unset($_GET['_router']);
        } else {
            define('__URI__', '');
        }
        self::$Template = new Template();
        if (Config::config('develop_mod')) {
            $whoops = new \Whoops\Run;
            foreach (Config::debug('Whoops_handler') as $handler) {
                $whoops->pushHandler($handler);
            }
            $whoops->register();
        }

        self::$app_name = $app_name;
        Router::init();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public static function run()
    {
        $response = Router::run();
        if (is_array($response)) {
            echo response_json($response);
        } else {
            echo $response;
        }
        Debug::stop();
    }
}