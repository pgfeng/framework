<?php

namespace GFPHP\Http\Request;

use ArrayObject;
use GFPHP\DataObject;
use JsonSerializable as JsonSerializableAlias;

class Data extends ArrayObject implements JsonSerializableAlias
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @param $data
     */
    public function __construct(&$data)
    {
        $this->data = &$data;
        parent::__construct($data);
    }

    /**
     * 获取数据
     * @param $key
     * @param null $default_value
     * @return mixed|null|string
     */
    public function get($key = false, $default_value = null)
    {
        if (!$key) {
            return $this->data;
        }
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return $default_value;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value)
    {
        return $this->data[$key] = $value;
    }

    /**
     * @param $key
     * @return void
     */
    public function remove($key)
    {
        unset($this->data[$key]);
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : NULL;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->data[$name];
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * @return string
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
        unset($this->data[$name]);
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return count($this->data);
    }

    public function rewind()
    {
        reset($this->data);
    }

    /**
     * @return Data|null
     */
    public function current()
    {
        $data = current($this->data);
        if (!empty($data)) {
            return new Data($data);
        }

        return NULL;
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * @return mixed
     */
    public function next()
    {
        return next($this->data);
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
        return $this->data;
    }

    /**
     * @return string
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
        return xml_encode($this->data, $this->table);
    }

    /**
     * @return ArrayObject|DataObject
     */
    public function getIterator()
    {
        return new ArrayObject($this->data);
    }

}