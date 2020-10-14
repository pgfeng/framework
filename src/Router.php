<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/24
 * Time: 22:19
 */

namespace GFPHP;

use GFPHP\Http\Request;
use Nette\Reflection\ClassType;

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
    public static $router = [];

    public static $module_name = '';

    public static $controller_name = '';

    public static $action_name = '';

    /**
     * 初始化路由
     */
    public static function init()
    {
        error_reporting(Config::config('error_reporting'));
        self::all('(.*)', static function ($router) {
            $uris = explode('/', $router);
            $uris = array_filter($uris, static function ($value) {
                return $value !== '';
            });
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
        if (Config::router('auto_build')) {
            self::buildAnnotation(GFPHP::$app_name);
        }
        foreach (glob(BASE_PATH . "Router" . DIRECTORY_SEPARATOR . GFPHP::$app_name . DIRECTORY_SEPARATOR . "*.php") as $filename) {
            include $filename;
        }

        Request::init();
        //==请求类型
        define('REQUEST_METHOD', Request::$method);

        //==是否为GET请求
        define('IS_GET', Request::isGetMethod());

        //==是否为POST请求
        define('IS_POST', Request::isPostMethod());

        //==是否为PUT请求
        define('IS_PUT', Request::isPutMethod());

        //==是否为DELETE请求
        define('IS_DELETE', Request::isDeleteMethod());

        //==是否为AJAX请求
        define('IS_AJAX', Request::isAjaxHttpRequest());
    }

    public static function buildAnnotation($app_name)
    {
        $paths = str_replace([BASE_PATH, '.php', '/'], ['', '', '\\'], glob(BASE_PATH . $app_name . '/*/*Controller.php'));
        $Router_Content = "<?php\n/**\n * Created by GFPHP.\n * BuildTime: " . date('Y-m-d H:i:s') . "\n */\n";
        foreach ($paths as $class) {
            $ref = new ClassType($class);
            $methods = $ref->getMethods();
            foreach ($methods as $item) {
                $route = $ref->getMethod($item->name)->getAnnotation('Route');
                $class_map = explode('\\', $class);
                if ($route && substr($item->name, -6) === 'Action') {
                    $Annotations = $ref->getMethod($item->name)->getAnnotations();
                    $route = preg_replace('/\\s+/is', " ", $route);
                    $route = explode(' ', $route);
                    $request_type = 'all';
                    if (count($route) > 1) {
                        $route[0] = trim($route[0]);
                        if (in_array($route[0], [
                            'GET',
                            'POST',
                            'PUT',
                            'DELETE',
                            'OPTIONS',
                            'HEAD',
                            'ALL',
                        ])) {
                            $request_type = strtolower($route[0]);
                            array_shift($route);
                        }
                    }
                    if (!count($route)) {
                        break;
                    }
                    $annotation = "\n/**\n";
                    $annotation .= " * @var {$class}\n";
                    foreach ($Annotations as $key => $value) {
                        foreach ($value as $k => $v) {
                            if ($key === 'description') {
                                $annotation .= ' * ' . $v . "\n";
                            } else {
                                $annotation .= ' * @' . $key . ' ' . $v . "\n";
                            }
                        }
                    }
                    $annotation .= " */\n";
                    $router_code = '';
                    foreach ($route as $rule) {
                        $router_code .= "\GFPHP\Router::" . $request_type . "('{$rule}' , '{$class_map[1]}/" . str_replace('Controller', '', $class_map[2]) . "@" . str_replace('Action', '', $item->name) . "');\n";
                    }
                    $Router_Content .= $annotation . $router_code;
                }
            }
        }
        mkPathDir(BASE_PATH . 'Router' . DIRECTORY_SEPARATOR . $app_name . DIRECTORY_SEPARATOR . 'Annotation.php', 0777);
        file_put_contents(BASE_PATH . 'Router' . DIRECTORY_SEPARATOR . $app_name . DIRECTORY_SEPARATOR . 'Annotation.php', $Router_Content, LOCK_EX);
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
     * @throws \Exception
     */
    public static function run()
    {
        $uri = preg_replace('#(/+)#', '/', '/' . __URI__);
        $uri = parse_url($uri, PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        $callback = false;
        $params = [];
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
            $callback = static function () {
                header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");
                if (Config::router('default_404')) {
                    return self::runCallBack(Config::router('default_404'), []);
                }

                throw new \RuntimeException('没有匹配到路由!');
            };
        }
        return self::runCallBack($callback, $params);
    }

    /**
     * 生成链接,路由反查返回
     * @param string $uri
     * @param array $get
     * @param string $method
     * @return string
     */
    public static function url($uri = '', $get = [], $method = 'GET')
    {
        $uri = $old_uri = parse_uri($uri);
        $uris = explode('/', $uri);
        $uris = array_filter($uris, static function ($v) {
            if ($v === '') {
                return 0;
            }

            return 1;
        });
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
        $params = array_filter($params, static function ($v) {
            if ($v === '') {
                return 0;
            }

            return 1;
        });
        $params_count = count($params);

        $explode_str = '[ ROUTER _ _ PARAMS ]';

        foreach (self::$routes[$method] as $pattern => $callback) {
            if (!is_callable($callback)) {
                $pattern_seg = preg_replace('#\(.*?\)#', $explode_str, $pattern);
                $num = substr_count($pattern_seg, $explode_str);
                if ($num !== $params_count) {
                    continue;
                }
                if (strcasecmp($callback, $uri) === 0) {
                    $exp_array = explode($explode_str, $pattern_seg);
                    $uri_compile = '';
                    for ($i = 0; $i <= $params_count; $i++) {
                        if ($i === $params_count) {
                            $uri_compile .= $exp_array[$i];
                        } else {
                            $uri_compile .= $exp_array[$i] . $params[$i];
                        }
                    }
                    if ($get) {
                        $uri_compile .= '?' . http_build_query($get);
                    }
                    return $uri_compile;
                }
            }
        }
        foreach (self::$routes['ALL'] as $pattern => $callback) {
            if (!is_callable($callback)) {
                $pattern_seg = preg_replace('#\(.*?\)#', $explode_str, $pattern);
                $num = substr_count($pattern_seg, $explode_str);
                if ($num !== $params_count) {
                    continue;
                }
                if (strcasecmp($callback, $uri) === 0) {
                    $exp_array = explode($explode_str, $pattern_seg);
                    $uri_compile = '';
                    for ($i = 0; $i <= $params_count; $i++) {
                        if ($i === $params_count) {
                            $uri_compile .= $exp_array[$i];
                        } else {
                            $uri_compile .= $exp_array[$i] . $params[$i];
                        }
                    }

                    if ($get) {
                        $uri_compile .= '?' . http_build_query($get);
                    }
                    return $uri_compile;
                }
            }
        }
        if ($get) {
            $uri_compile = '/' . $old_uri . '?' . http_build_query($get);
        } else {
            $uri_compile = '/' . $old_uri;
        }
        return $uri_compile;
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
        self::$router = [
            'callback' => $callback,
            'params' => $params,
        ];
        if (!is_callable($callback)) {
            Logger::getInstance()->info('路由调度 ' . $callback, $params);
        } else {
            Logger::getInstance()->info('路由调度 (Closure)' . closure_dump($callback), $params);
        }
        if (is_callable($callback)) {
            if (!$params) {
                return $callback();
            }

            return call_user_func_array($callback, $params);
        }

        $callback = str_replace('/', '\\', $callback);
        $segments = explode('@', $callback);
        if (count($segments) === 1) {
            throw new \RuntimeException('必须传入操作的行为!');
        }
        $seg = explode('\\', $segments[0]);
        if (count($seg) !== 2) {
            throw new \RuntimeException('传入的控制器必须两层结构!');
        }
        define('MODULE_NAME', self::$module_name = ucfirst(strtolower($seg[0])));
        define('CONTROLLER_NAME', self::$controller_name = ucfirst(strtolower($seg[1])));
        define('METHOD_NAME', self::$action_name = strtolower($segments[1]));
        $controllerName = GFPHP::$app_name . '\\' . MODULE_NAME . '\\' . CONTROLLER_NAME . Config::router('controllerSuffix');

        /** @var Controller $controller */
        $controller = new $controllerName();
        $method = METHOD_NAME . Config::router('methodSuffix');
        if (!method_exists($controller, $method)) {
            if (Config::router('default_404')) {
                return self::runCallBack(Config::router('default_404'), []);
            }

            header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");
            throw new \RuntimeException('method ' . $method . ' not find!');
        }
        if (!is_array($params)) {
            return $controller->$method();
        }

        return call_user_func_array(array($controller, $method), $params);
    }
}