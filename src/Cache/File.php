<?php

namespace GFPHP\Cache;

use GFPHP\Config, GFPHP\Cache;


/**
 * fileSystem缓存操作   不支持自动删除缓存
 * 实现文件方式缓存
 * 创建时间：2014-09-19 07:40 PGF
 */
class file extends Cache
{

    public $config = [
        'default_space' => 'default_space',
    ];

    /**
     * fileCache constructor.
     *
     * @param bool|array $config
     */
    public function __construct($config = FALSE)
    {
        if ($config) {
            foreach ($config as $k => $v) {
                $this->config[$k] = $v;
            }
        }
    }

    /**
     * 获取内容
     *
     * @param        $key
     * @param string $space
     *
     * @return bool|mixed|string
     */
    public function _get($key, $space = '')
    {
        $this->check_time($key, $space);
        $path = $this->toPath($key, $space);
        return $this->read($path);
    }

    /**
     * 获取保存位置
     *
     * @param      $key
     * @param bool $dir
     *
     * @return string
     */
    private function toPath($key, $dir = FALSE)
    {

        if (!$dir) $dir = $this->config['default_space'];
        return BASE_PATH . Config::cache('cache_dir') . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $key . '.php';
    }

    /**
     * @param $key
     * @param bool $dir
     * @return string
     */
    private function toTimePath($key, $dir = false)
    {
        if (!$dir) $dir = $this->config['default_space'];
        return BASE_PATH . Config::cache('cache_dir') . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . '__file_time__' . DIRECTORY_SEPARATOR . $key . '.php';
    }

    /**
     * 读取文件
     *
     * @param $path
     *
     * @return bool|string
     */
    private function read($path)
    {
        if (file_exists($path))
            return file_get_contents($path);
        else
            return FALSE;
    }

    /**
     * 判断是否有缓存
     *
     * @param        $key
     * @param string $space
     *
     * @return bool
     */
    public function _is_cache($key, $space = '')
    {
        $this->check_time($key, $space);
        $path = $this->toPath($key, $space);
        return file_exists($path);
    }

    /**
     * @param $key
     * @param string $space
     * @return bool
     */
    private function check_time($key, $space = '')
    {
        $time_path = $this->toTimePath($key, $space);
        if (file_exists($time_path) && (((int)$this->_time($key, $space) + (int)$this->read($time_path)) < time())) {
            $this->_delete($key, $space);
        }
        return true;
    }

    /**
     * 获取修改或添加的时间
     *
     * @param        $key
     * @param string $space
     *
     * @return bool|int|mixed
     */
    public function _time($key, $space = '')
    {
        $path = $this->toPath($key, $space);
        if (file_exists($path))
            return filemtime($path);
        else
            return FALSE;

    }

    /**
     * 设置内容
     * 不存在就添加，存在就修改
     * @param        $key
     * @param        $content
     * @param string $space
     *
     * @param int $expiration
     * @return bool|mixed
     */
    public function _set($key, $content, $space = '', $expiration = 0)
    {
        $path = $this->toPath($key, $space);
        $timePath = $this->toTimePath($key, $space);
        if ($this->write($path, $content) && $this->write($timePath, $expiration))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * 写入文件
     *
     * @param $path
     * @param $content
     *
     * @return int
     */
    private function write($path, $content)
    {
        $dir = dirname($path);
        if (!file_exists($dir)) {
            if (!@mkdir($dir, 0777, TRUE)) {
                echo '创建缓存文件夹失败,没有写入权限';
            }
        }

        return file_put_contents($path, $content);
    }

    /**
     * 删除指定缓存
     *
     * @param $key
     * @param $space
     *
     * @return bool|mixed
     */
    public function _delete($key, $space)
    {
        $path = $this->toPath($key, $space);
        $timePath = $this->toTimePath($key, $space);
        @unlink($timePath);
        return @unlink($path);
    }

    /**
     * 清空指定文件夹的缓存
     *
     *
     * @return bool|mixed
     */
    public function _flush()
    {
        $dir = BASE_PATH . parseDir(Config::config('app_dir'), Config::cache('cache_dir'));
        if (!file_exists($dir))
            return TRUE;
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullPath = $dir . "/" . $file;
                if (!is_dir($fullPath)) {
                    @unlink($fullPath);
                } else {
                    $this->flush($file);
                }
            }
        }

        closedir($dh);
        //删除当前文件夹：
        if (rmdir($dir)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}