<?php
/**
 * Created by PhpStorm.
 * User: pgf
 * Date: 2019-01-22
 * Time: 19:04
 */

namespace GFPHP\Cache;


use GFPHP\Cache;
use GFPHP\Config;

class redis extends Cache
{
    private $redis = false;

    /**
     * redisCache constructor.
     */
    function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect(Config::cache('redis_host'), Config::cache('redis_port'));
    }

    /**
     * @param $name
     * @param $space
     * @return mixed
     */
    function _get($name, $space = false)
    {
        return $this->redis->get($this->toKey($name, $space));
    }

    /**
     * @param $key
     * @param $space
     *
     * @return string
     */
    protected function toKey($key, $space)
    {
        return md5($key . $this->getSpace($space));
    }

    /**
     * @param $name
     * @param $con
     * @param bool $space
     * @param int $expiration
     * @return mixed
     */
    function _set($name, $con, $space = false, $expiration = 0)
    {
        return $this->redis->set($this->toKey($name, $space), $con, $expiration);
    }

    /**
     * @param $name
     * @param $space
     * @return mixed
     */
    function _is_cache($name, $space = false)
    {
        return $this->redis->exists($this->toKey($name, $space));
    }

    /**
     * @param $name
     * @param $space
     * @return mixed
     */
    function _delete($name, $space = false)
    {
        return $this->redis->delete($this->toKey($name, $space));
    }

    /**
     * @param $space
     * @return mixed
     */
    function _flush($space = false)
    {
        return $this->redis->flushDB();
    }

    private function getSpace($space = '')
    {
        return ($space ? $space : $this->config['default_space']);
    }
}