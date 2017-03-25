<?php
namespace GFPHP\Database;
use GFPHP\DataObject;
use GFPHP\DBase, GFPHP\Config;
use GFPHP\Exception;

/**
 * Class mysqliDriver
 */
class mysqliDriver extends DBase
{
    /**
     * @var mysqli
     */
    public $mysqli;
    private $configName = 'default';

    /**
     * @param $configName
     *
     * @return bool
     */
    function _connect($configName)
    {
        $config = Config::database($configName);
        //=====使用长连接
        $this->configName = $configName;
            $mysqli = new mysqli('p:' . $config['host'], $config['user'], $config['pass'], $config['name']);
        if ($mysqli->connect_error) {
            new Exception('连接数据库失败：' . $mysqli->connect_error);
        } else {
            $this->mysqli = $mysqli;
            $this->mysqli->set_charset($config['charset']);
            return TRUE;
        }
    }
    
    /**
     * @param $string
     *
     * @return string
     */
    function real_escape_string($string){
        return mysqli_real_escape_string($string,$this->con);
    }
    /**
     * 返回错误信息
     * @return string
     */
    function getError()
    {
        return $this->mysqli->error;
    }

    /**
     * @param $sql
     * @return array
     */
    function &_query($sql)
    {
        $query = $this->mysqli->query($sql);
        if ($query) {
            $result = [];
            while ($row = $query->fetch_assoc()) {
                $result[] = new DataObject($row,TRUE,$this->table,$this->configName);;
            }
            unset($query);
            return $result;
        } else {
            return $query;
        }
    }

    /**
     * @return bool
     */
    function close()
    {
        return mysqli_close($this->mysqli);
    }

    /**
     * @param $sql
     * @return bool|mysqli_result
     */
    function _exec($sql)
    {
        return $this->mysqli->query($sql);
    }

    /**
     * @return bool
     */
    function rollBack()
    {
        return $this->mysqli->rollback();
    }

    /**
     * @return bool
     */
    function commit()
    {
        return $this->mysqli->commit();
    }

    /**
     * @return bool
     */
    function beginTransaction()
    {
        return $this->mysqli->autocommit(FALSE);
    }
}