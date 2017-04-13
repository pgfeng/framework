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
        error_reporting(Config::config('error_reporting'));
        Router::all('(.*)', function ($router) {
            $uris = explode('/', $router);
            $uris = array_filter($uris);
            $count = count($uris);
            $params = [];
            switch ($count) {
                case 0:
                    $uri = Config::router('default_module') . '/' . Config::router('default_controller') . '@index';
                    break;
                case 1:
                    $uri = $uris[0] . '/' . Config::router('default_controller') . '@index';
                    break;
                case 2:
                    $uri = $uris[0] . '/' . $uris[1] . '@index';
                    break;
                case 3:
                    $uri = $uris[0] . '/' . $uris[1] . '@' . $uris[2];
                    break;
                default:
                    $uri = $uris[0] . '/' . $uris[1] . '@' . $uris[2];
                    $params = array_slice($uris, 3);
            }
            return Router::runCallback($uri, $params);
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
        $_SERVER['REQUEST_URI'] = preg_replace('#(/+)#', '/', $_SERVER['REQUEST_URI']);
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
                if (preg_match('#^' . $router . '?.*$#', $uri, $params)) {
                    $callback = self::$routes[$method][$router];
                    array_shift($params);
                    break;
                }
            }
            if (!$callback) {
                $routers = array_keys(self::$routes['ALL']);
                foreach ($routers as $router) {
                    if (preg_match('#^' . $router . '?.*$#', $uri, $params)) {
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
                if (Config::router('default_404')) {
                    return self::runCallBack(Config::router('default_404'), []);
                } else {
                    throw new \Exception('没有匹配到路由!');
                }
            };
        }
        return self::runCallBack($callback, $params);
    }

    /**
     * 生成链接,路由反查返回
     * @param string $uri
     * @param array  $get
     * @param string $method
     * @return string
     */
    public static function url($uri = '', $get = [], $method = 'GET')
    {
        $old_uri = $uri;
        if ($uri) {
            $NM = strpos($uri, '#');
            if ($NM === 0) {          //没有填写 MODULE_NAME
                $uri = MODULE_NAME . '/' . substr($uri, 1);
                $old_uri = MODULE_NAME . '/' . substr($old_uri, 1);
            } else {
                $NC = strpos($uri, '@');
                if ($NC === 0) {    //没有填写 MODULE_NAME 和 CONTROLLER_NAME
                    $uri = MODULE_NAME . '/' . CONTROLLER_NAME . '/' . substr($uri, 1);
                    $old_uri = MODULE_NAME . '/' . CONTROLLER_NAME . '/' . substr($old_uri, 1);
                }
            }
        }
        $uris = explode('/', $uri);
        $uris = array_filter($uris);
        $count = count($uris);
        switch ($count) {
            case 0:
                $uri = ucfirst(Config::router('default_module')) . '/' . ucfirst(Config::router('default_controller')) . '@index';
                $params = [];
                break;
            case 1:
                $uri = ucfirst($uris[0]) . '/' . ucfirst(Config::router('default_controller')) . '@index';
                $params = [];
                break;
            case 2:
                $uri = ucfirst($uris[0]) . '/' . ucfirst($uris[1]) . '@index';
                $params = [];
                break;
            case 3:
                $uri = ucfirst($uris[0]) . '/' . ucfirst($uris[1]) . '@' . $uris[2];
                $params = [];
                break;
            default:
                $uri = ucfirst($uris[0]) . '/' . ucfirst($uris[1]) . '@' . $uris[2];
                $params = array_slice($uris, 3);
        }
        $params = array_filter($params);
        $params_count = count($params);
        foreach (self::$routes[$method] as $pattern => $callback) {
            if (!is_object($callback)) {
                $explode_str = 'ROUTER _ _ PARAMS';
                $pattern_seg = preg_replace('#\(.*\)#', $explode_str, $pattern);
                $num = substr_count($pattern_seg, $explode_str);

                if ($num != $params_count)
                    continue;
                if (strcasecmp($callback, $uri) == 0) {
                    $exp_array = explode($explode_str, $pattern_seg);
                    $uri_compile = '';
                    for ($i = 0; $i <= $params_count; $i++) {
                        if ($i == $params_count)
                            $uri_compile .= $exp_array[$i];
                        else
                            $uri_compile .= $exp_array[$i] . $params[0];
                    }
                    if ($get)
                        return $uri_compile . '?' . http_build_query($get);
                    else
                        return $uri_compile;
                }
            }
        }
        foreach (self::$routes['ALL'] as $pattern => $callback) {
            if (!is_object($callback)) {
                $explode_str = 'ROUTER _ _ PARAMS';
                $pattern_seg = preg_replace('#\(.*\)#', $explode_str, $pattern);
                $num = substr_count($pattern_seg, $explode_str);
                if ($num != $params_count)
                    continue;
                if (strcasecmp($callback, $uri) == 0) {
                    $exp_array = explode($explode_str, $pattern_seg);
                    $uri_compile = '';
                    for ($i = 0; $i <= $params_count; $i++) {
                        if ($i == $params_count)
                            $uri_compile .= $exp_array[$i];
                        else
                            $uri_compile .= $exp_array[$i] . $params[0];
                    }

                    if ($get)
                        return $uri_compile . '?' . http_build_query($get);
                    else
                        return $uri_compile;
                }
            }
        }
        if ($get) {
            return '/' . $old_uri . '?' . http_build_query($get);
        } else {
            return '/' . $old_uri;
        }
    }

    /**
     * 执行
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
            $callback = str_replace('/', '\\', $callback);
            $segments = explode('@', $callback);
            if (count($segments) == 1)
                throw new \Exception('必须传入操作的行为!');
            $seg = explode('\\', $segments[0]);
            if (count($seg) != 2) {
                throw new \Exception('传入的控制器必须两层结构!');
            }
            define('MODULE_NAME', ucfirst($seg[0]));
            define('CONTROLLER_NAME', ucfirst($seg[1]));
            define('METHOD_NAME', $segments[1]);
            $controllerName = GFPHP::$app_name . '\\' . $seg[0] . '\\' . $seg[1] . Config::router('controllerSuffix');
            $controller = new $controllerName();
            $method = $segments[1] . Config::router('methodSuffix');
            if (!method_exists($controller, $method)) {
                if (Config::router('default_404')) {
                    return self::runCallBack(Config::router('default_404'), []);
                } else {
                    header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");
                    throw new \Exception('method ' . $method . ' not find!');
                }
            }
            if (!is_array($params)) {
                return $controller->$method();
            } else {
                return call_user_func_array(array($controller, $method), $params);
            }
        }
    }
}