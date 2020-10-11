<?php

namespace GFPHP\Cache;

use GFPHP\Config, GFPHP\Cache;
use GFPHP\DBase;


/**
 * 数据库存储缓存类
 * 将缓存放入数据库指定表中
 * 'CREATE TABLE ' . Config::database('table_pre') . $config['table'] . ' ( `cache_key` varchar(125) NOT NULL,`cache_data` text,`cache_time` int(10) NOT NULL,`cache_space` varchar(125) NOT NULL, KEY `cache_key` (`cache_key`,`cache_time`,`cache_space`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8'
 * 创建时间：2014-8-24 10:20 PGF
 * 更新时间：2015-7-18 10:35 PGF 实现数据库缓存
 */
class DB extends Cache
{

    /**
     * @var DBase
     */
    private $model = FALSE;
    public $config = [];


    public function __construct($config)
    {
        if ($config) {
            foreach ($config as $k => $v) {
                $this->config[$k] = $v;
            }
        }


        //--链接数据库，并且获取模型

        if (!$this->model) {
            $this->model = \GFPHP\DB::table($config['table'], $config['database_config_name']);
        }

    }


    public function _get($key, $space)
    {

        $key = $this->model->addslashes($key);
        $space = $this->model->addslashes($space);
        $res = $this->model->where('cache_key', $key)->where('cache_space', $space)->select('cache_data')->limit(1)->query();
        if (empty($res)) {
            return FALSE;
        }

        return $res[0]['cache_data'];

    }

    public function _is_cache($key, $space)
    {
        $key = $this->model->addslashes($key);
        $space = $this->model->addslashes($space);
        $res = $this->model->where('cache_key', $key)->where('cache_space', $space)->select('cache_key')->query();
        if (empty($res)) {
            return FALSE;
        }

        return TRUE;
    }

    public function _time($key, $space)
    {
        $key = $this->model->addslashes($key);
        $space = $this->model->addslashes($space);
        $res = $this->model->where('cache_key', $key)->where('cache_space', $space)->select('cache_time')->limit(1)->query();
        if (empty($res))
            return FALSE;

        return $res[0]['cache_time'];
    }

    public function _set($key, $content, $space)
    {
        if ($this->is_cache($key, $space)) {
            $update = [
                'cache_time' => time(),
                'cache_data' => $this->model->addslashes($content),
            ];
            if ($this->model->where('cache_key', $key)->where('cache_space', $space)->update($update) !== FALSE) {
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            $insert = [
                'cache_key' => $this->model->addslashes($key),
                'cache_data' => $this->model->addslashes($content),
                'cache_time' => time(),
                'cache_space' => $this->model->addslashes($space),
            ];
            return $this->model->insert($insert) !== FALSE;
        }
    }

    public function _delete($key, $space)
    {
        if ($this->is_cache($key, $space)) {
            $key = $this->model->addslashes($key);
            $space = $this->model->addslashes($space);

            return $this->model->where('cache_key', $key)->where('cache_space', $space)->delete();
        }

        return TRUE;
    }

    /**
     * @param string|array $space
     * @return bool|mixed
     */
    public function _flush($space='')
    {
        if ((string)$space !== (string)Config::database('cache_dir') . '/' . $this->model->db->table) {
            $space = $this->model->addslashes($space);
            return $this->model->where('cache_space like \'' . $space . '%\'')->delete() !== FALSE;
        }

        return FALSE;
    }

    public function _delete_timeout($space, $lifetime)
    {
        $space = $this->model->addslashes($space);
        $time = time() - $lifetime;
        if ($this->model->where('cache_time <' . $time)->where('cache_space', $space)->delete() !== FALSE)
            return TRUE;
        else
            return FALSE;
    }
} 
