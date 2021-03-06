<?php
/**
 * Created by PhpStorm.
 * User: PGF(592472116@qq.com)
 * Date: 2016/9/22
 * Time: 15:17
 */

namespace GFPHP;

use ArrayObject;
use IteratorAggregate;

/**
 * 数据对象
 * Class DataObject
 *
 * @package GFPHP
 */
class DataObject extends ArrayObject implements \JsonSerializable
{
    /**
     * @var array
     */
    private $storage;
    /**
     * @var string
     */
    private $table;
    /**
     * @var string
     */
    private $DBName;
    /**
     * @var bool
     */
    private $is_row;

    /**
     * DataObject constructor.
     *
     * @param array $data
     *
     * @param bool $is_row
     * @param string $table
     * @param string $configName
     *
     * @internal param bool|string $table
     */
    public function __construct(array $data = [], $is_row = FALSE, $table = 'Data', $configName = 'default')
    {
        $this->storage = $data;
        $this->is_row = $is_row;
        $this->table = $table;
        $this->DBName = $configName;
    }


    /**
     * 设置为模型
     *
     * @param $model
     */
    public function setModel(&$model)
    {
        $this->model = $model;
    }

    /**
     * @param string $primary_key 主键字段名
     *
     * @return Bool|int
     * @throws \Exception
     */
    public function save($primary_key = 'id')
    {
        if (!$this->is_row && $this->table === 'Data') {
            new Exception("数据不是单行数据库数据!");
            return FALSE;
        }

        return DB::table($this->table, $this->DBName)->save($this->storage, $primary_key);
    }

    /**
     * 删除此条数据
     *
     * @param string $primary_key
     *
     * @return bool|int
     * @throws \Exception
     */
    public function delete($primary_key = 'id')
    {
        if (!$this->is_row && $this->table === 'Data') {
            new Exception("数据不是单行数据库数据!");
            return FALSE;
        }

        return DB::table($this->table, $this->DBName)->where($primary_key, $this->storage->$primary_key)->delete();
    }

    /**
     * @return array|DataObject
     */
    public function jsonSerialize()
    {
        return $this->storage;
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->storage[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return isset($this->storage[$offset]) ? $this->storage[$offset] : NULL;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
        $this->storage[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->storage[$offset]);
    }

    /**
     * 是否为空
     * @return bool
     */
    public function isEmpty()
    {
        if (empty($this->storage)) {
            return true;
        }
        return false;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->storage[$name];
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->storage[$key] = $value;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->storage[$key]);
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        unset($this->storage[$name]);
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return count($this->storage);
    }

    public function rewind()
    {
        reset($this->storage);
    }

    /**
     * @return DataObject|null
     */
    public function current()
    {
        $data = current($this->storage);
        if (!empty($data)) {
            return new DataObject($data);
        }

        return NULL;
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return key($this->storage);
    }

    /**
     * @return mixed
     */
    public function next()
    {
        return next($this->storage);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->current() !== NULL;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->storage;
    }

    /**
     * @return mixed
     */
    public function toJson()
    {
        return json_encode($this, JSON_OBJECT_AS_ARRAY);
    }

    /**
     * @return mixed
     */
    public function toXml()
    {
        return xml_encode($this->storage, $this->table);
    }

    /**
     * @return ArrayObject|DataObject
     */
    public function getIterator()
    {
        return new \ArrayObject($this->storage);
    }

    /**
     * 自动生成树形结构
     *
     * @param string $idK
     * @param string $pidK
     * @param string $childK
     * @param String $pid
     *
     * @param array $data
     *
     * @return DataObject | array
     */
    public function toTree($idK = 'id', $pidK = 'pid', $childK = 'child', $pid = '0', $data = [])
    {
        if (!$data) {
            $data = $this->storage;
        }

        $tree = [];
        foreach ($data as $key => $item) {
            if ((string)$item[$pidK] === (string)$pid) {
                unset($data[$key]);
                $item[$childK] = $this->toTree($idK, $pidK, $childK, $item[$idK], $data);
                $tree[] = $item;
            }
            continue;
        }
        if ($tree) {
            return new DataObject($tree);
        }

        return [];
    }

    /**
     * 将数据转为primary_key为键名并返回
     *
     * @param $primary_key
     *
     * @return DataObject
     */
    public function transPrimaryIndex($primary_key)
    {
        $newData = [];
        if ($this->storage) {
            foreach ($this->storage as $item) {
                $newData[$item[$primary_key]] = $item;
            }
        }
        $newData = new DataObject($newData);
        return $newData;
    }
}