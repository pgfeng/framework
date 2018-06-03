<?php
namespace GFPHP;

/**
 * 缓存类
 * 缓存对象&&数组
 * 创建时间：2014-08-08 23:29  PGF
 * @property  config
 */
abstract class Cache
{
    /**
     * @var Cache
     */
    public static $cache;
    protected $config;

    /**
     * 初始化Cache
     */
    public static function init()
    {
        $config = Config::cache();
        $cache_driver = 'GFPHP\\Cache\\'.$config['driver'];
        self::$cache = new $cache_driver($config);
    }

    /**
     * 获取缓存
     * @param $name
     * @param bool $space 缓存空间
     * @return string
     */
    public static function get($name, $space = FALSE)
    {
        Debug::add('Cache:' . $space . DIRECTORY_SEPARATOR . $name . ' 读取成功.', 0);

        return self::$cache->_get($name, $space);
    }

    /**
     * 获取缓存最后更新的时间
     * @param string $name
     * @param bool $space
     * @return int       返回Unix时间戳
     */
    public static function time($name, $space = FALSE)
    {
        return self::$cache->_time($name, $space);
    }

    /**
     * 设置缓存
     * @param $name
     * @param $con
     * @param bool $space 缓存空间
     * @return mixed
     */
    public static function set($name, $con, $space = FALSE)
    {
        Debug::add('Cache:' . $space . DIRECTORY_SEPARATOR . $name . ' 更新成功.', 0);

        return self::$cache->_set($name, $con, $space);
    }

    /**
     * 是否有缓存
     * @param $name
     * @param bool $space 缓存空间
     * @return mixed
     */
    public static function is_cache($name, $space = FALSE)
    {
        return self::$cache->_is_cache($name, $space);
    }

    /**
     * 删除缓存
     * @param $name
     * @param bool $space 缓存空间
     * @return mixed
     */
    public static function delete($name, $space = FALSE)
    {
        $res = self::$cache->_delete($name, $space);
        if ($res) {
            Debug::add('Cache:' . $space . DIRECTORY_SEPARATOR . $name . ' 删除成功.', 0);
        } else {
            Debug::add('Cache:' . $space . DIRECTORY_SEPARATOR . $name . ' 删除失败.', 0);
        }

        return $res;
    }

    /**
     * 清空一个缓存空间
     * @param bool $space 缓存空间
     * @return bool
     */
    public static function flush($space = FALSE)
    {
        if ($space == FALSE)
            $space = self::$cache->config['default_space'];
        $res = self::$cache->_flush($space);
        if ($res) {
            Debug::add('Cache:缓存空间 ' . $space . ' 清空成功.', 0);
        } else {
            Debug::add('Cache:缓存空间 ' . $space . ' 清空失败.', 0);
        }

        return $res;
    }

    /**
     * 删除过期的缓存 需要在程序上处理
     * @param bool $space 缓存空间
     * @param bool $lifetime 生存时间,如果缓存时间没有设置,将会使用配置默认的生存时间
     * @return mixed
     */
    public static function delete_timeout($space = FALSE, $lifetime = FALSE)
    {
        if ($space == FALSE)
            $space = self::$cache->config['default_space'];
        if ($lifetime == FALSE)
            $lifetime = Config::cache('lifetime');

        return self::$cache->_delete_timeout($space, $lifetime);
    }

    /**
     * @param $name
     * @param $space
     * @return mixed
     */
    abstract function _get($name, $space);

    /**
     * @param $name
     * @param $con
     * @param $space
     * @return mixed
     */
    abstract function _set($name, $con, $space);

    /**
     * @param $name
     * @param $space
     * @return mixed
     */
    abstract function _is_cache($name, $space);

    /**
     * @param $name
     * @param $space
     * @return mixed
     */
    abstract function _time($name, $space);

    /**
     * @param $name
     * @param $space
     * @return mixed
     */
    abstract function _delete($name, $space);

    /**
     * @param $space
     * @return mixed
     */
    abstract function _flush($space);

    /**
     * @param $space
     * @param $lifetime
     * @return mixed
     */
    abstract function _delete_timeout($space, $lifetime);
}