<?php
namespace GFPHP\Cache;
use GFPHP\Config, GFPHP\Cache;
use GFPHP\DBase;


/**
 * 数据库存储缓存类
 * 将缓存放入数据库指定表中
 * 'CREATE TABLE ' . Config::database('table_pre') . $config['table'] . ' ( `cachekey` varchar(125) NOT NULL,`cachedata` text,`cachetime` int(10) NOT NULL,`cachespace` varchar(125) NOT NULL, KEY `cachekey` (`cachekey`,`cachetime`,`cachespace`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8'
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
                $this->config[ $k ] = $v;
            }
        }


        //--链接数据库，并且获取模型

        if (!$this->model) {

            $this->model = model($config['table']);

        }

    }


    public function _get($key, $space)
    {

        $key = $this->model->addslashes($key);
        $space = $this->model->addslashes($space);
        $res = $this->model->where('cachekey', $key)->where('cachespace', $space)->select('cachedata')->limit(1)->query();
        if (empty($res))
            return FALSE;

        return $res[0]['cachedata'];

    }

    public function _is_cache($key, $space)
    {
        $key = $this->model->addslashes($key);
        $space = $this->model->addslashes($space);
        $res = $this->model->where('cachekey', $key)->where('cachespace', $space)->select('cachekey')->query();
        if (empty($res)) {
            return FALSE;
        }

        return TRUE;
    }

    public function _time($key, $space)
    {
        $key = $this->model->addslashes($key);
        $space = $this->model->addslashes($space);
        $res = $this->model->where('cachekey', $key)->where('cachespace', $space)->select('cachetime')->limit(1)->query();
        if (empty($res))
            return FALSE;

        return $res[0]['cachetime'];
    }

    public function _set($key, $content, $space)
    {
        if ($this->is_cache($key, $space)) {
            $update = [
                'cachetime' => time(),
                'cachedata' => $this->model->addslashes($content),
            ];
            if ($this->model->where('cachekey', $key)->where('cachespace', $space)->update($update) !== FALSE) {
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            $insert = [
                'cachekey'   => $this->model->addslashes($key),
                'cachedata'  => $this->model->addslashes($content),
                'cachetime'  => time(),
                'cachespace' => $this->model->addslashes($space),
            ];
            if ($this->model->insert($insert) !== FALSE) {
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }

    public function _delete($key, $space)
    {
        if ($this->is_cache($key, $space)) {
            $key = $this->model->addslashes($key);
            $space = $this->model->addslashes($space);

            return $this->model->where('cachekey', $key)->where('cachespace', $space)->delete();
        }

        return TRUE;
    }

    public function _flush($space)
    {
        if ($space != Config::database('cache_dir') . '/' . $this->model->db->table) {
            $space = $this->model->addslashes($space);
            if ($this->model->where('cachespace like \'' . $space . '%\'')->delete() !== FALSE) {
                return TRUE;
            } else {
                return FALSE;
            }
        }

        return FALSE;
    }

    public function _delete_timeout($space, $lifetime)
    {
        $space = $this->model->addslashes($space);
        $time = time() - $lifetime;
        if ($this->model->where('cachetime <' . $time)->where('cachespace', $space)->delete() !== FALSE)
            return TRUE;
        else
            return FALSE;
    }
} 
