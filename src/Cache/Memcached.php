<?php

namespace GFPHP\Cache;

use GFPHP\Config, GFPHP\Cache;
use GFPHP\Debug;

/**
 * memcache缓存驱动
 * 实现更方便的memcache操作
 * 创建时间：2014-09-01 09:23 PGF 可以按键值添加删除缓存
 * 修改时间：2014-09-17 08:56 PGF 使用设置键值空间实现可以批量删除功能
 */
class Memcached extends Cache
{
    /**
     * 默认设置
     */
    public $config = [
        'memcached_host' => '127.0.0.1',
        'memcached_port' => '11211',
        'default_space' => 'default_space',
    ];

    /**
     * 存放实例过的memcache语柄
     * @var bool | \Memcached
     */
    protected $mem = FALSE;

    /**
     * 实例化memcache
     * 如果设置$config则按照$config设置，否则按照config下cache.php设置
     *
     * @param bool|array $config
     */
    public function __construct($config = FALSE)
    {
        if ($config) {
            foreach ($config as $k => $v) {
                $this->config[$k] = $v;
            }
        } else {
            $this->config = Config::cache();
        }
        $this->connect();
    }

    /**
     * 链接memcache,保存实例化的memcache
     * @return $this|bool|\Memcached
     */
    private function connect()
    {
        if ($this->mem) {
            return $this;
        } else {
            $this->mem = new \Memcached();
            $this->mem->addServer($this->config['memcached_host'], $this->config['memcached_port']) or Debug::add('memcached 链接失败！');
            return $this;
        }
    }

    public function _is_cache($key, $space = FALSE)
    {
        return $this->_get($key, $space);
    }

    /**
     * 获取内容
     *
     * @param      $key
     * @param bool $space
     *
     * @return mixed|string
     */
    public function _get($key, $space = FALSE)
    {
        $key = $this->toKey($key, $space);
        return $this->mem->get($key);
    }

    /**
     * @param $key
     * @param $space
     *
     * @return string
     */
    protected function toKey($key, $space)
    {
        $space = $space ? $space : $this->config['default_space'];
        return md5($key . $space);
    }


    /**
     *
     * 设置内容
     *
     * 不存在添加，存在则修改
     *
     * @param      $key
     * @param      $value
     * @param bool $space
     *
     * @param int $expiration
     * @return mixed
     */
    public function _set($key, $value, $space = FALSE, $expiration = 0)
    {
        return $this->mem->set($this->toKey($key, $space), $value, $expiration);
    }


    /**
     * 删除一个指定键缓存
     *
     * @param      $key
     * @param bool $space
     *
     * @return bool|mixed
     */
    public function _delete($key, $space = FALSE)
    {
        return $this->mem->delete($this->toKey($key, $space));
    }

    /**
     * 清空指定空间缓存
     *
     * @param string $name
     * @return bool|mixed
     */

    public function _flush($name='')
    {
        return $this->mem->flush();
    }
}
