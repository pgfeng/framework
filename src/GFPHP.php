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
        if (!defined('BASE_PATH')) {
            exit('Not Define BASE_PATH');
        }

        //==项目网址根目录
        define('ROOT_URL', ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https:' : 'http:') . ('//' . $_SERVER['HTTP_HOST'] . (($_SERVER["SERVER_PORT"] == '80' || $_SERVER["SERVER_PORT"] == '443') ? '' : ':' . $_SERVER["SERVER_PORT"])));

        //==当前时间
        define('__NOW__', $_SERVER['REQUEST_TIME']);

        session_start();

        $time_zone = Config::config('time_zone');

        $time_zone = $time_zone ?: 'PRC';

        date_default_timezone_set($time_zone);

        Debug::start();

        Cache::init();

        Hooks::init();

        Router::init();
        
        self::$Template = new Template();
        if (Config::config('develop_mod')) {
            $whoops = new \Whoops\Run;
            foreach (Config::debug('Whoops_handler') as $handler) {
                $whoops->pushHandler($handler);
            }
            $whoops->register();
        }

        self::$app_name = $app_name;
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