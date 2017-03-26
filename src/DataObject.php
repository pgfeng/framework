<?php
/**
 * Created by PhpStorm.
 * User: PGF(592472116@qq.com)
 * Date: 2016/9/22
 * Time: 15:17
 */

namespace GFPHP;
/**
 * 数据对象
 * Class DataObject
 *
 * @package GFPHP
 */
class DataObject extends \ArrayObject implements \IteratorAggregate, \JsonSerializable, \ArrayAccess, \Countable
{
	private $storage = [ ],
		$table = 'Data',
		$DBName = 'default',
		$is_row = FALSE;

	/**
	 * DataObject constructor.
	 *
	 * @param array  $data
	 *
	 * @param bool   $is_row
	 * @param string $table
	 * @param string $configName
	 *
	 * @internal param bool|string $table
	 */
	public function __construct ( array $data, $is_row=FALSE,$table='Data',$configName='default')
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
	public function setModel(&$model){
		$this->model = $model;
	}

	/**
	 * @param string $primary_key 主键字段名
	 *
	 * @return Bool|int
	 */
	public function save($primary_key='id'){
		if(!$this->is_row && $this->table=='Data'){
			new Exception("数据不是单行数据库数据!");
			return FALSE;
		}else{
			return DB::table($this->table,$this->DBName)->save($this->storage,$primary_key);
		}
	}

	/**
	 * 删除此条数据
	 *
	 * @param string $primary_key
	 *
	 * @return bool|int
	 */
	public function delete($primary_key='id'){
		if(!$this->is_row && $this->table=='Data'){
			new Exception("数据不是单行数据库数据!");
			return FALSE;
		}else{
			return DB::table($this->table,$this->DBName)->where($primary_key,$this->storage->$primary_key)->delete();
		}
	}

	/**
	 * @return array|DataObject
	 */
	public function jsonSerialize ()
	{
		return $this->storage;
	}

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists ( $offset )
	{
		return isset( $this->storage[ $offset ] );
	}

	/**
	 * @param mixed $offset
	 *
	 * @return mixed|null
	 */
	public function offsetGet ( $offset )
	{
		return isset( $this->storage[ $offset ] ) ? $this->storage[ $offset ] : NULL;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet ( $offset, $value )
	{
		// TODO: Implement offsetSet() method.
		$this->storage[ $offset ] = $value;
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset ( $offset )
	{
		unset( $this->storage[ $offset ] );
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	public function __get ( $name )
	{
		return $this->storage[ $name ];
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function __set ( $key, $value )
	{
		$this->storage[ $key ] = $value;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function __isset ( $key )
	{
		return isset( $this->storage[ $key ] );
	}

	/**
	 * @return mixed
	 */
	public function __toString ()
	{
		return $this->toJson ();
	}

	/**
	 * @param $name
	 */
	public function __unset ( $name )
	{
		unset( $this->storage[ $name ] );
	}

	/**
	 * @return mixed
	 */
	public function count ()
	{
		return count ( $this->storage );
	}

	public function rewind ()
	{
		reset ( $this->storage );
	}

	/**
	 * @return DataObject|null
	 */
	function current ()
	{
		$data = current ( $this->storage );
		if ( !empty( $data ) ) {
			return new DataObject( $data );
		} else {
			return NULL;
		}
	}

	/**
	 * @return mixed
	 */
	function key ()
	{
		return key ( $this->storage );
	}

	/**
	 * @return mixed
	 */
	function next ()
	{
		return next ( $this->storage );
	}

	/**
	 * @return bool
	 */
	function valid ()
	{
		return $this->current () !== NULL;
	}

	/**
	 * @return array
	 */
	public function toArray ()
	{
		return $this->storage;
	}

	/**
	 * @return mixed
	 */
	public function toJson ()
	{
		return json_encode ( $this, JSON_OBJECT_AS_ARRAY );
	}

	/**
	 * @return mixed
	 */
	public function toXml ()
	{
		return xml_encode ( $this->storage, $this->table );
	}

	/**
	 * @return \ArrayObject|DataObject
	 */
	public function getIterator ()
	{
		if ( $this->storage )
			$data = new \ArrayObject( $this->storage );
		else
			$data = NULL;
		return $data;
	}

	/**
	 * 自动生成树形结构
	 *
	 * @param string $idK
	 * @param string $pidK
	 * @param string $childK
	 * @param String $pid
	 *
	 * @param bool   $data
	 *
	 * @return array | DataObject
	 */
	public function toTree ( $idK = 'id', $pidK = 'pid', $childK = 'child', $pid = '0', $data = FALSE )
	{
		if ( $data === FALSE )
			$data = $this->storage;
		if ( !$data ) {
			return [ ];
		} else {
			$tree = [ ];
			foreach ( $data as $key => $item ) {
				if ( $item[ $pidK ] == $pid ) {
					unset( $data[ $key ] );
					$item[ $childK ] = $this->toTree ( $idK, $pidK, $childK, $item[ $idK ], $data );
					$tree[] = $item;
				}
				continue;
			}
			if ( $tree )
				$data = new DataObject( $tree );
			else
				$data = NULL;

			return $data;
		}
	}

	/**
	 * 将数据转为primary_key为键名并返回
	 *
	 * @param $primary_key
	 *
	 * @return DataObject
	 */
	public function transPrimaryIndex ( $primary_key )
	{
		$newData = [ ];
		if ( $this->storage ) {
			foreach ( $this->storage as $item ) {
				$newData[ $item[ $primary_key ] ] = $item;
			}
		}
		$newData = new DataObject( $newData );
		return $newData;
	}
}