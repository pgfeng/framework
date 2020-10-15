<?php


namespace GFPHP\Http;


use GFPHP\Http\Request\Data;
use GFPHP\Http\Request\Methods;

/**
 * Class Request
 * @package GFPHP\Http
 */
class Request
{
    use Methods;

    /**
     * 头部信息
     * @var Data
     */
    public static $header;

    /**
     * Server信息
     * @var Data
     */
    public static $server;

    /**
     * Get参数
     * @var Data
     */
    public static $get;

    /**
     * Post参数
     * @var Data
     */
    public static $post;

    /**
     * Request参数
     * @var Data
     */
    public static $request;

    /**
     * File上传数据
     * @var Data
     */
    public static $file;

    /**
     * Cookie数据
     * @var Data
     */
    public static $cookie;

    /**
     * Session数据
     * @var Data
     */
    public static $session;

    /**
     * 请求类型
     * @var string
     */
    public static $method;

    /**
     * 真实请求地址 携带参数
     * @var null | string
     */
    public static $requestUri = null;

    /**
     * 路由地址 不携带参数
     * @var null|string
     */
    public static $routeUri = null;

    /**
     * 初始化
     */
    public static function init()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP_') === 0) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        self::$header = new Data($headers);
        self::$server = new Data($_SERVER);
        self::$routeUri = $_GET['_router'];
        unset($_GET['_router'], $_REQUEST['_router']);
        self::$get = new Data($_GET);
        self::$post = new Data($_POST);
        self::$request = new Data($_REQUEST);
        self::$file = new Data($_FILES);
        self::$cookie = new Data($_COOKIE);
        self::$session = new Data($_SESSION);
        self::$method = strtoupper(self::$server->get('REQUEST_METHOD'));
        self::$requestUri = self::prepareRequestUri();
    }

    /**
     * Returns current script name.
     *
     * @return string
     */
    public static function getScriptName()
    {
        return self::$server->get('SCRIPT_NAME', self::$server->get('ORIG_SCRIPT_NAME', ''));
    }

    public static function getMethod()
    {
        return self::$method;
    }

    /**
     * 获取客户端IP地址
     * @return string
     */
    public static function getClientIp()
    {
        if (self::$server) {

            if (isset(self::$server["HTTP_X_FORWARDED_FOR"])) {

                $ip_address = self::$server["HTTP_X_FORWARDED_FOR"];

            } else if (isset(self::$server["HTTP_CLIENT_IP"])) {

                $ip_address = self::$server["HTTP_CLIENT_IP"];

            } else {

                $ip_address = self::$server["REMOTE_ADDR"];

            }

        } else {

            if (getenv("HTTP_X_FORWARDED_FOR")) {

                $ip_address = getenv("HTTP_X_FORWARDED_FOR");

            } else if (getenv("HTTP_CLIENT_IP")) {

                $ip_address = getenv("HTTP_CLIENT_IP");

            } else {

                $ip_address = getenv("REMOTE_ADDR");

            }

        }

        return preg_match('/[\d\.]{7,15}/', $ip_address, $matches) ? $matches [0] : '';
    }

    private static function prepareRequestUri()
    {
        $requestUri = '';

        if ('1' === self::$server->get('IIS_WasUrlRewritten') && '' !== self::$server->get('UNENCODED_URL')) {
            // IIS7 with URL Rewrite: make sure we get the unencoded URL (double slash problem)
            $requestUri = self::$server->get('UNENCODED_URL');
            self::$server->remove('UNENCODED_URL');
            self::$server->remove('IIS_WasUrlRewritten');
        } elseif (self::$server->has('REQUEST_URI')) {
            $requestUri = self::$server->get('REQUEST_URI');

            if ('' !== $requestUri && strpos($requestUri, '/') === 0) {
                // To only use path and query remove the fragment.
                if (false !== $pos = strpos($requestUri, '#')) {
                    $requestUri = substr($requestUri, 0, $pos);
                }
            } else {
                // HTTP proxy reqs setup request URI with scheme and host [and port] + the URL path,
                // only use URL path.
                $uriComponents = parse_url($requestUri);

                if (isset($uriComponents['path'])) {
                    $requestUri = $uriComponents['path'];
                }

                if (isset($uriComponents['query'])) {
                    $requestUri .= '?' . $uriComponents['query'];
                }
            }
        } elseif (self::$server->has('ORIG_PATH_INFO')) {
            // IIS 5.0, PHP as CGI
            $requestUri = self::$server->get('ORIG_PATH_INFO');
            if ('' !== self::$server->get('QUERY_STRING')) {
                $requestUri .= '?' . self::$server->get('QUERY_STRING');
            }
            self::$server->remove('ORIG_PATH_INFO');
        }

        // normalize the request URI to ease creating sub-requests from this request
        self::$server->set('REQUEST_URI', $requestUri);

        return $requestUri;
    }


    /**
     * @return bool
     */
    public static function isXmlHttpRequest()
    {
        return 'XMLHttpRequest' === self::$header->get('X-Requested-With');
    }

    /**
     * 是否是Ajax请求
     * @return bool
     */
    public static function isAjaxHttpRequest()
    {
        return self::$server->has('HTTP_X_REQUESTED_WITH') && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * @return string
     */
    public static function domain()
    {
        return self::getScheme() . '://' . self::getHttpHost();
    }

    /**
     * @return bool
     */
    private static function isSecure()
    {
        $https = self::$server->get('HTTPS');

        return !empty($https) && 'off' !== strtolower($https);
    }

    /**
     * @return string|null
     */
    private static function getHttpHost()
    {
        $scheme = self::getScheme();
        $port = self::getPort();

        if (('http' === $scheme && 80 === $port) || ('https' === $scheme && 443 === $port)) {
            return self::getHost();
        }

        return self::getHost() . ':' . $port;
    }

    /**
     * 获取Host
     * @return string|null
     */
    public static function getHost()
    {
        return self::$server->get('SERVER_NAME');
    }

    /**
     * 获取当前完整网址
     */
    public static function currentUrl()
    {
        return self::domain() . self::$requestUri;
    }

    /**
     * 获取协议
     * @return string
     */
    public static function getScheme()
    {
        return self::isSecure() ? 'https' : 'http';
    }

    /**
     * 获取端口号
     * @return int
     */
    public static function getPort()
    {
        return (int)self::$server->get('SERVER_PORT');
    }
}