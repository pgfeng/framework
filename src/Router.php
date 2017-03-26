<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/24
 * Time: 22:19
 */

namespace GFPHP;


/**
 * Class Router
 * @package GFPHP
 * @method static Router get(string $route, Callable $callback)
 * @method static Router post(string $route, Callable $callback)
 * @method static Router put(string $route, Callable $callback)
 * @method static Router delete(string $route, Callable $callback)
 * @method static Router options(string $route, Callable $callback)
 * @method static Router head(string $route, Callable $callback)
 * @method static Router all(string $route, Callable $callback)
 */
class Router
{
    /**
     * 存储路由
     * 如果当前请求类型不存在会自动向ALL中查找
     * @var array
     */
    public static $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'OPTIONS' => [],
        'HEAD' => [],
        'ALL' => [],
    ];

    /**
     * 初始化路由
     */
    public static function init()
    {
        Router::all('(.*)', function ($router) {
            $cuts = explode('/', $router);
            $uri = '';
            $cuts = array_filter($cuts);
            $count = count($cuts);
            if (!$count) {
                $uri = 'Home@index';
            }
            if ($count == 1) {
                $uri = $cuts[0] . '@index';
            } else {
                for ($i = 0; $i < $count; $i++) {
                    $cut = array_shift($cuts);
                    if ($i == $count - 1) {
                        $uri .= '@' . $cut;
                    } else {
                        if ($i != 0)
                            $uri .= '/';
                        $uri .= ucfirst($cut);
                    }
                }
            }
            return Router::runCallback($uri, false);
        });
    }

    /**
     * @param $method
     * @param $params
     */
    public static function __callstatic($method, $params)
    {
        $method = strtoupper($method);
        $routers = self::$routes[$method];
        self::$routes[$method] = [];
        self::$routes[$method][str_replace('\\', '/', dirname($_SERVER['PHP_SELF']) . '') . $params[0]] = $params[1];
        foreach ($routers as $uri => $route) {
            self::$routes[$method][$uri] = $route;
        }
    }


    /**
     * @return mixed
     */
    public static function run()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        $callback = false;
        $params = false;
        if (array_key_exists($uri, self::$routes[$method])) {
            $callback = self::$routes[$method][$uri];
        } elseif (array_key_exists($uri, self::$routes['ALL'])) {
            $callback = self::$routes['ALL'][$uri];
        } else {
            $routers = array_keys(self::$routes[$method]);
            foreach ($routers as $router) {
                if (preg_match('#^' . $router . '$#', $uri, $params)) {
                    $callback = self::$routes[$method][$router];
                    array_shift($params);
                    break;
                }
            }
            if (!$callback) {
                $routers = array_keys(self::$routes['ALL']);
                foreach ($routers as $router) {
                    if (preg_match('#^' . $router . '$#', $uri, $params)) {
                        $callback = self::$routes['ALL'][$router];
                        array_shift($params);
                        break;
                    }
                }
            }
        }
        if (!$callback) {
            $callback = function () {
                header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");
                throw new \Exception('没有匹配到路由!');
            };
        }
        return self::runCallBack($callback, $params);
    }


    /**
     * @param $callback
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public static function runCallBack($callback, $params)
    {
        if (is_object($callback)) {
            if (!$params) {
                return call_user_func($callback);
            } else {
                return call_user_func_array($callback, $params);
            }
        } else {
            $callback = GFPHP::$app_name . '/' . $callback;
            $callback = str_replace('/', '\\', $callback);
            $segments = explode('@', $callback);
            define('CONTROLLER_NAME', str_replace(GFPHP::$app_name.'\\','',$segments[0]));
            define('METHOD_NAME', $segments[1]);
            $controllerName = $segments[0].Config::router('controllerSuffix');
            $controller = new $controllerName();
            $method = $segments[1].Config::router('methodSuffix');
            if (!method_exists($controller, $method)) {
                header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");
                throw new \Exception('method ' . $method . ' not find!');
            }
            if (!is_array($params)) {
                return $controller->$method();
            } else {
                return call_user_func_array(array($controller, $method), $params);
            }
        }
    }
}