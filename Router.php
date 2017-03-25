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
    public static function init(){
        Router::all('(.*)', function ($router) {
            $cuts = explode('/', $router);
            $count = count($cuts);
            $uri = '';
            if($count==1){
                if($cuts[0])
                    $uri = $cuts[0].'@index';
                else
                    $uri = 'Home@index';
            }else {
                for ($i = 0; $i < $count; $i++) {
                    if (!$cuts[$i])
                        continue;
                    if ($i == $count - 1) {
                        $uri .= '@' . $cuts[$i];
                    } else {
                        if($i!=0)
                            $uri .= '/';
                        $uri .= ucfirst($cuts[$i]);
                    }
                }
            }
            return Router::runCallback($uri, false);
        });
    }
    /**
     * 向路由中存储
     * @param $method
     * @param $params
     */
    public static function __callstatic($method, $params)
    {
        $method = strtoupper($method);
        self::$routes[$method][str_replace('\\', '/', dirname($_SERVER['PHP_SELF']) . '') . $params[0]] = $params[1];
    }

    /**
     * 路由运行
     */
    public static function run()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        $callback = false;
        $params = false;
        if (array_key_exists($uri, self::$routes[$method])) {
            $callback = self::$routes[$method][$uri];
        } elseif(array_key_exists($uri, self::$routes['ALL'])) {
            $callback = self::$routes['ALL'][$uri];
        }else{
            $routers = array_keys(self::$routes[$method]);
            foreach ($routers as $router){
                if(preg_match('#^'.$router.'$#',$uri,$params)) {
                    $callback = self::$routes[$method][$router];
                    array_shift($params);
                    break;
                }
            }
            if(!$callback){
                $routers = array_keys(self::$routes['ALL']);
                foreach ($routers as $router){
                    if(preg_match('#^'.$router.'$#',$uri,$params)) {
                        $callback = self::$routes['ALL'][$router];
                        array_shift($params);
                        break;
                    }
                }
            }
        }
        if(!$callback) {
            $callback = function () {
                header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");
                return '页面不存在!';
            };
        }
        return self::runCallBack($callback,$params);
    }

    /**
     * 运行callback
     */
    public static function runCallBack($callback,$params){
        if (is_object($callback)) {
            if (!$params) {
                return call_user_func($callback);
            }else{
                return call_user_func_array($callback,$params);
            }
        } else {
            $callback = Application::$app_name.'/'.$callback;
            $callback = str_replace('/', '\\', $callback);
            $segments = explode('@', $callback);
            $controller = new $segments[0]();
            if(!method_exists($controller, $segments[1])){
                header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");
                return 'method '.$segments[1].' not find!';
            }
            if (!is_array($params)) {
                return $controller->{$segments[1]}();
            }else{
                return call_user_func_array(array($controller, $segments[1]), $params);
            }
        }
    }
}