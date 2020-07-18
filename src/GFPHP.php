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

        //==项目网址根目录
        define('ROOT_URL', ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https:' : 'http:') . ('//' . $_SERVER['HTTP_HOST'] . (($_SERVER["SERVER_PORT"] == '80' || $_SERVER["SERVER_PORT"] == '443') ? '' : ':' . $_SERVER["SERVER_PORT"])));

        //==当前时间
        define('__NOW__', $_SERVER['REQUEST_TIME']);

        //==请求类型
        define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);

        //==是否为GET请求
        define('IS_GET', REQUEST_METHOD === 'GET');

        //==是否为POST请求
        define('IS_POST', REQUEST_METHOD === 'POST');

        //==是否为PUT请求
        define('IS_PUT', REQUEST_METHOD === 'PUT');

        //==是否为DELETE请求
        define('IS_DELETE', REQUEST_METHOD === 'DELETE');

        //==是否为AJAX请求
        define('IS_AJAX', (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'));

        session_start();

        $time_zone = Config::config('time_zone');

        $time_zone = $time_zone ?: 'PRC';

        date_default_timezone_set($time_zone);

        Debug::start();

        Cache::init();

        Hooks::init();

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