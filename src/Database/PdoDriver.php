<?php
namespace GFPHP\Database;
use GFPHP\Config, GFPHP\DBase;
use GFPHP\DataObject;

/**
 * Class PdoDriver
 */
class PdoDriver extends DBase
{
	/**
	 * @var \PDO
	 */
	private $db;
	private $configName = 'default';

	/**
	 * @param $configName
	 *
	 * @return bool
	 */
	public function _connect ($configName)
	{
		$config = Config::database($configName);
		$this->configName = $configName;
		try {
			$this->db = new \pdo('mysql:dbname='.$config['name'].';host='.$config['host'].';port='.$config['port'].';', $config[ 'user' ], $config[ 'pass' ] );
			$this->db->setAttribute ( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		} catch ( \PDOException $e ) {

            new \Exception('连接数据库失败：' . $e->getMessage() ,0);
			
		}
		if ( !$this->db )
			return FALSE;
		else {
			$this->exec ( 'set names ' . $config[ 'charset' ] );

			return TRUE;
		}
	}

	/**
	 * 返回错误信息
	 *
	 * @return string
	 */
	function getError ()
	{
		return implode ( ' | ', $this->db->errorInfo () );
	}

	/**
	 * 数据库驱动必须创建下列方法
	 * 并且必须返回正确的值
	 *
	 * @param $sql
	 *
	 * @return array
	 */
	public function _query ( $sql )
	{
		try {
			$query = $this->db->query ( $sql );
		} catch ( PDOException $e ) {
			return FALSE;
		}
		if ( !$query )
			return [ ];
		$result = $query->fetchAll ( \PDO::FETCH_ASSOC );   //只获取键值
		foreach ($result as &$item){
			$item = new DataObject($item, TRUE, $this->table, $this->configName );
		}
		unset( $query );

		return $result;

	}
	
	/**
	 * @param $string
	 *
	 * @return string
	 */
	function real_escape_string($string){
		return $this->db->quote($string);
	}

	/**
	 * @param $sql
	 *
	 * @return mixed
	 */
	public function _exec ( $sql )
	{
		try {
			return $this->db->exec ( $sql );
		} catch ( \PDOException $e ) {
			return FALSE;
		}
	}

	public function beginTransaction ()
	{
		$this->db->beginTransaction ();
	}

	public function commit ()
	{
		$this->db->commit ();
	}

	public function rollBack ()
	{
		$this->db->rollBack ();
	}

	public function close ()
	{

	}
}
