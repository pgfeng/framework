<?php

namespace GFPHP\Database;

use GFPHP\Config, GFPHP\DBase;
use GFPHP\DataObject;
use GFPHP\Exception;

/**
 * Class mysql
 * @package GFPHP\Database
 */
class Mysql extends DBase
{
    private $con = FALSE;
    private $configName = 'default';

    /**
     * @param $configName
     *
     * @return bool
     */
    public function _connect($configName)
    {
        $config = Config::database($configName);
        $this->configName = $configName;
        $con = @mysql_connect($config['host'] . ':' . $config['port'], $config['user'], $config['pass']);
        if ($con) {
            $r = mysql_select_db($config['name'], $con) or new Exception('连接数据库失败：<font color=red>' . mysql_error() . '</font>', 0);
            if ($r === TRUE) {
                $this->con = $con;
                $this->exec('set names ' . Config::database('charset'));

                return TRUE;
            }
        } else {
            new Exception('连接Mysql服务器失败：,' . mysql_error());
        }

        return FALSE;
    }

    /**
     * @param $string
     *
     * @return string
     */
    function real_escape_string($string)
    {
        $string = mysql_real_escape_string($string, $this->con);
        if(is_numeric($string)) {
            return $string;
        }else{
            return '\''.$string.'\'';
        }
    }

    /**
     * 返回错误信息
     *
     * @return string
     */
    function getError()
    {
        return mysql_error($this->con);
    }

    /**
     * @param $sql
     * @return array|bool
     */
    function _query($sql)
    {
        $query = mysql_query($sql, $this->con);
        if ($query) {
            $result = [];
            while ($row = mysql_fetch_assoc($query)) {
                $result[] = new DataObject($row, TRUE, $this->table, $this->configName);
            }

            return $result;
        } else {
            return FALSE;
        }
    }

    /**
     * @param $sql
     * @return resource
     */
    function _exec($sql)
    {
        return mysql_query($sql, $this->con);
    }

    function commit()
    {
        return $this->exec("commit");
    }

    function rollBack()
    {
        return $this->exec("rollback");
    }

    function beginTransaction()
    {
        $this->exec("set autocommit=0");
    }
}
