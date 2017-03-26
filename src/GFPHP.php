<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/25
 * Time: 10:09
 */

namespace GFPHP;


class GFPHP
{
    /**
     * @var string $app_name
     */
    public static $app_name;
    public static function init($app_name = 'app'){
        date_default_timezone_set('PRC');
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        $whoops->register();
        Debug::start();
        self::$app_name = $app_name;
        Router::init();
    }
    public static function run(){
        echo Router::run();
        Debug::stop();
    }
}