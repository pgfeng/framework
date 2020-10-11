<?php

namespace GFPHP;

use Monolog\Handler\IFTTTHandler;

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
     * @param string $cache_driver
     */
    public static function init($cache_driver = '')
    {
        $config = Config::cache();
        if (!$cache_driver) {
            $cache_driver = $config['driver'];
        }
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
     * 设置缓存
     * @param $name
     * @param $con
     * @param bool $space 缓存空间
     * @param int $expiration
     * @return mixed
     */
    public static function set($name, $con, $space = FALSE, $expiration = 0)
    {
        Debug::add('Cache:' . $space . DIRECTORY_SEPARATOR . $name . ' 更新成功.', 0);
        return self::$cache->_set($name, $con, $space, $expiration);
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
     * @param string $space
     * @return bool
     */
    public static function flush($space='')
    {
        $res = self::$cache->_flush($space);
        if ($res) {
            Debug::add('Cache:缓存清空成功.', 0);
        } else {
            Debug::add('Cache:缓存清空失败.', 0);
        }

        return $res;
    }

    /**
     * @param $name
     * @param $space
     * @return mixed
     */
    abstract public function _get($name, $space);

    /**
     * @param $name
     * @param $con
     * @param $expiration
     * @param $space
     * @return mixed
     */
    abstract public function _set($name, $con, $space, $expiration);

    /**
     * @param $name
     * @param $space
     * @return mixed
     */
    abstract public function _is_cache($name, $space);

    /**
     * @param $name
     * @param $space
     * @return mixed
     */
    abstract public function _delete($name, $space);

    /**
     * @param string $space
     * @return mixed
     */
    abstract public function _flush($space='');
}