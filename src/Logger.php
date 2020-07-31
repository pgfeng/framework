<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/7/0004
 * Time: 下午 6:57
 */

namespace GFPHP;

use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\ChromePHPHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\FirePHPHandler;


/**
 * Class Log
 * @package GFPHP
 */
class Logger
{
    static $monolog = [
    ];

    /**
     * @param string $name
     * @return \Monolog\Logger
     */
    public static function getInstance($name = 'GFPHP')
    {
        if (isset(self::$monolog[$name])) {
            return self::$monolog[$name];
        }

        self::$monolog[$name] = new \Monolog\Logger($name);
        foreach (Config::debug('log_handler') as $handler) {
            self::$monolog[$name]->pushHandler($handler);
        }
        self::$monolog[$name]->pushProcessor(Config::debug('log_processor'));
        return self::$monolog[$name];
    }
}