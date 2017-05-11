<?php
namespace GFPHP;

/**
 * SQL语句处理类
 * 提供简单的SQL语句构造方法
 * 数据库驱动类需要继承此类
 * 用最少的代码做最不可能的事   @PGF
 * 创建时间：2014-08-06 14:20 PGF
 * 修改时间：2014-08-18 15:20 PGF 修改增删改查方法，同时支持链式操作和简易操作
 * 修改时间：2014-08-11 13:18 PGF 默认操作表名，使用模型时直接默认表名，增加简易型
 * 修改时间：2014-08-13 22:12 PGF 修改SELECT&UPDATE&DELETE&INSERT方法，使调用后立即执行语句
 * 修改时间：2015-02-25 17:08 PGF SELECT不在自动执行，需query()方法执行，方便多表操作
 * 修改时间：2015-06-14 11:02 PGF 修改select存在表名时自动加上表前缀
 * 修改时间：2016-04-16 10:20 PGF 自动解决注入问题，将不用再手动使用addslashes去做转义操作
 * 修改时间: 2016-06-28 21:03 PGF 为了程序安全,将错误屏蔽,并且将错误存放至日志
 */

/**
 * 本类所有表名字段名为了符合大部分数据库的SQL规范字段未加转义符号
 * 构造数据库时请注意所使用的数据库的保留字
 */
abstract class DBase
{
	/**
	 * @var string
	 */
	public $table = '';
	public $config = [ ];

	/**
	 * @var string $section
	 */
	public $section = [
		'handle'  => 'select',
		'select'  => '*',
		'insert'  => '',
		'set'     => '',
		'where'   => '',
		'join'    => '',
		'group'   => '',
		'orderby' => '',
		'limit'   => '',
	];
	public $data = [ ];
	/**
	 * @var string $sql
	 */
	public $sql = '';
	public $lastSql = '';

	final function lastSql ()
	{
		return $this->lastSql;
	}

	/**
	 * @return String
	 */
	final public function version ()
	{
		$version = $this->query ( 'SELECT VERSION()' );
		return $version ? $version[0]['VERSION()'] : NULL;
	}


	/**
	 * 获取分页内容
	 *
	 * @param int $number
	 * @param int $page
	 *
	 * @return array|mixed
	 */
	final public function paginate ( $number = 10, $page = 1 )
	{
		$page = $page > 0 ? $page : 1;
		$min = ( intval ( $page ) - 1 ) * $number;

		return $this->limit ( $min, intval ( $number ) )->query ();
	}

	/**
	 * 获取最后自增ID
	 *
	 * @return boolean LAST_INSERT_ID
	 */
	final public function lastInsertId ()
	{
		$query = $this->query ( 'SELECT LAST_INSERT_ID()' );

		return $query[ 0 ][ 'LAST_INSERT_ID()' ];
	}

	/**
	 * @param $field
	 *
	 * @return int    获取到的数量
	 */
	final public function Count ( $field = '*' )
	{
		$count = $this->getOne ( 'count(' . $field . ')' );

		return isset( $count[ 'count(' . $field . ')' ] ) ? $count[ 'count(' . $field . ')' ] : 0;
	}

	/**
	 * 获取一条数据
	 *
	 * @param array|string $field
	 *
	 * @return DataObject||bool
	 */
	final public function getOne ( $field = '*' )
	{
		$this->select ( $field );
		$this->limit ( 0, 1 );
		$fetch = $this->query ();
		if ( empty( $fetch ) ) return FALSE; else
			return $fetch[ 0 ];
	}

	/**
	 * 设置字段值
	 *
	 * @param $field_name
	 * @param $field_value
	 *
	 * @return boolean
	 */
	final public function setField ( $field_name, $field_value )
	{
		return $this->update ( [
			$field_name => $field_value,
		] );
	}

	/**
	 * 获取一个字段值
	 *
	 * @param $field_name
	 *
	 * @return string|int
	 */
	final public function getField ( $field_name )
	{
		$this->select ( $field_name );
		$this->limit ( 0, 1 );
		$fetch = $this->query ();
		if ( empty( $fetch ) ) return FALSE; else
			return $fetch[ 0 ][ $field_name ];
	}


	/**
	 * 设置查询
	 * 参数为一个时设置查询字段
	 * 当为多个时可看成
	 * SELECT($table,$where,$orderby,$limit,$column);
	 *
	 * @param array|string $select
	 *
	 * @return $this
	 */
	final function select ( $select = '*' )
	{
		$this->section[ 'handle' ] = 'select';
		$arg_num = func_num_args ();
		$arg_num = $arg_num > 5 ? 5 : $arg_num;
		if ( $arg_num > 1 ) {
			$arg_list = func_get_args ();
			for ( $i = 0; $i < $arg_num; $i ++ ) {
				switch ( $i ) {
					case 0:
						$this->setTable ( $arg_list[ $i ] );
						break;
					case 1:
						$this->where ( $arg_list[ $i ] );
						break;
					case 2:
						$this->orderBy ( $arg_list[ $i ] );
						break;
					case 3:
						$this->limit ( $arg_list[ $i ] );
						break;
					case 4:
						$this->select ( $arg_list[ $i ] );
						break;
				}
			}

			return $this->query ();        //多参数将自懂执行query，返回数组；
		} else {
			//==判断是否为数组
			if ( is_array ( $select ) ) {
				$allField = '';
				foreach ( $select as $field ) {
					if ( $allField == '' ) {
						if ( strpos ( $field, '.' ) !== FALSE ) {
							//==自动加上表名前缀
							$allField = $this->config [ 'table_pre' ] . $field;
						} else {
							$allField = $field;
						}
					} else
						if ( strpos ( $field, '.' ) !== FALSE ) {
							$allField .= ',' . $this->config [ 'table_pre' ] . $field;
						} else {
							$allField .= ',' . $field;
						}
				}
				$this->_set ( $allField, 'select' );
			} else {
				$this->_set ( $select, 'select' );
			}

			return $this;
		}
	}

	/**
	 * 设置表名
	 *
	 * @param     $table
	 * @param int $forget
	 *
	 * @return $this
	 */
	final function setTable ( $table, $forget = 1 )
	{
		if ( $forget == 0 )
			$this->table = $this->config [ 'table_pre' ] . $table;
		$this->_set ( $this->config [ 'table_pre' ] . $table, 'table' );
		$this->compile ();

		return $this;
	}

	/**
	 * 设置section
	 *
	 * @param $data
	 * @param $type
	 */
	final function _set ( $data, $type )
	{
		if ( is_array ( $data ) ) {
			$this->section[ $type ] = implode ( ',', $data );
		} else {
			$this->section[ $type ] = $data;
		}
	}

	/**
	 * @param $field
	 * @param $Between
	 *
	 * @return Object
	 */
	final function between ( $field, $Between )
	{
		$Between = implode ( ' AND ', $Between );

		return $this->where ( "{$field} BETWEEN {$Between}" );
	}

	/**
	 * @param $field
	 * @param $Between
	 *
	 * @return Object
	 */
	final function notBetween ( $field, $Between )
	{
		$Between = implode ( ' AND ', $Between );

		return $this->where ( "{$field} NOT BETWEEN {$Between}" );
	}

	/**
	 * @param $field
	 * @param $in
	 *
	 * @return Object
	 */
	final function in ( $field, $in )
	{
        if(is_array($in)) {
            $pin = '\'';
            $pin .= implode('\',\'', $in);
            $pin .= '\'';
        }else{
            $pin = $in;
        }

		return $this->where ( "{$field} IN ({$pin})" );
	}

	/**
	 * 设置条件
	 * 当参数是两个，第一个为字段名，第二个为值
	 * 当参数为三个,第一个为字段名,第二个为逻辑字符,第三个为值
	 * 如果为数组，则是多个条件如array('条件一','条件二'.......);
	 *
	 * @param $where
	 *
	 * @return DBase
	 */
	final function where ( $where )
	{
		if ( func_num_args () > 1 ) {
			$field = func_get_arg ( 0 );
			if ( strpos ( $field, '.' ) !== FALSE ) {
				$field = $this->config [ 'table_pre' ] . $field;
			}
			$fieldAnd = explode ( '&', $field );
			$hasAnd = count ( $fieldAnd ) > 1 ? TRUE : FALSE;
			$fieldOr = explode ( '|', $field );
			$hasOr = count ( $fieldOr ) > 1 ? TRUE : FALSE;
			if ( $hasAnd && $hasOr ) {
				//TODO 待解决 同时处理OR和AND
				new Exception( 'Where 字段目前不能同时包含&和|' );
			}
			if ( func_num_args () == 2 ) {

				if ( $hasOr ) {
					$wheres = [ ];
					foreach ( $fieldOr as $f ) {
						$wheres[] = '' . $f . '=' . $this->addslashes ( func_get_arg ( 1 ) );
					}
					$where = implode ( ' or ', $wheres );
					unset( $wheres );
				} elseif ( $hasAnd ) {
					$wheres = [ ];
					foreach ( $fieldAnd as $f ) {
						$wheres[] = '' . $f . '=' . $this->addslashes ( func_get_arg ( 1 ) );
					}
					$where = implode ( ' and ', $wheres );
					unset( $wheres );
				} else {
					$where = '' . $field . '=' . $this->addslashes ( func_get_arg ( 1 ) );
				}
			} elseif ( func_num_args () == 3 ) {

				if ( $hasOr ) {
					$wheres = [ ];
					foreach ( $fieldOr as $f ) {
						$wheres[] = '' . $f . ' ' . func_get_arg ( 1 ) . ' ' . $this->addslashes ( func_get_arg ( 2 ) );
					}
					$where = implode ( ' or ', $wheres );
					unset( $wheres );
				} elseif ( $hasAnd ) {
					$wheres = [ ];
					foreach ( $fieldAnd as $f ) {
						$wheres[] = '' . $f . ' ' . func_get_arg ( 1 ) . ' ' . $this->addslashes ( func_get_arg ( 2 ) );
					}
					$where = implode ( ' and ', $wheres );
					unset( $wheres );
				} else {
					$where = '' . $field . ' ' . func_get_arg ( 1 ) . ' ' . $this->addslashes ( func_get_arg ( 2 ) );
				}
			}
		}
		if ( is_array ( $where ) )
			$where = implode ( ' and ', $where );
		if ( isset( $this->section[ 'where' ] ) && !empty( $this->section[ 'where' ] ) )
			$this->section[ 'where' ] .= ' and ' . $where;
		else
			$this->section[ 'where' ] = $where;

		return $this;
	}

	/**
	 * 设置排序方式
	 *
	 * @param $orderby
	 *
	 * @return $this
	 */
	final function orderBy ( $orderby )
	{
		if ( is_array ( $orderby ) ) {
			$order = '';
			foreach ( $orderby as $field ) {
				if ( $order == '' ) {
					if ( strpos ( $field, '.' ) !== FALSE ) {
						//==自动加上表名前缀
						$order = $this->config [ 'table_pre' ] . $field;
					} else {
						$order = $field;
					}
				} else
					if ( strpos ( $field, '.' ) !== FALSE ) {
						$order .= ',' . $this->config [ 'table_pre' ] . $field;
					} else {
						$order .= ',' . $field;
					}
			}
			$this->section[ 'orderby' ] = $order;
		} else {
			$this->section[ 'orderby' ] = $orderby;
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	final function limit ()
	{
		$arg_num = func_num_args ();
		$arg_list = func_get_args ();
		if ( $arg_num == 1 )
			$this->section[ 'limit' ] = $arg_list[ 0 ];
		if ( $arg_num == 2 )
			$this->section[ 'limit' ] = $arg_list[ 0 ] . ',' . $arg_list[ 1 ];

		return $this;
	}

	/**
	 * @param $where
	 *
	 * @return $this
	 */
	final function orWhere ( $where )
	{
		if ( func_num_args () > 1 ) {
			$field = func_get_arg ( 0 );
			if ( strpos ( $field, '.' ) !== FALSE ) {
				$field = $this->config [ 'table_pre' ] . $field;
			}
			if ( func_num_args () == 2 ) {
				$where = '' . $field . '=' . $this->addslashes ( func_get_arg ( 1 ) );
			} elseif ( func_num_args () == 3 ) {
                $where = func_get_arg ( 2 );
                if ( is_array ( $where ) ) {
                    $where = implode ( ',', $this->addslashes ( $where ) );
                }
                $where = '' . $field . ' ' . func_get_arg ( 1 ) . " '" . $where . "'";
			}
		}
		if ( is_array ( $where ) )
			$where = implode ( ' or ', $where );
		if ( isset( $this->section[ 'where' ] ) && !empty( $this->section[ 'where' ] ) )
			$this->section[ 'where' ] .= ' or ' . $where;
		else
			$this->section[ 'where' ] = $where;

		return $this;
	}

	/**
	 * @param $from
	 *
	 * @return $this
	 */
	final function from ( $from )
	{
		$this->setTable ( $from );

		return $this;
	}

	//---字段自增
	final function setInc ( $column, $num )
	{

		$this->section[ 'handle' ] = 'update';
		$this->_set ( $column . '=' . $column . '+' . $num, 'update' );

		return $this->exec ();
	}

	//---字段自减
	final function setDnc ( $column, $num )
	{
		$this->section[ 'handle' ] = 'update';
		$this->_set ( $column . '=' . $column . '-' . $num, 'update' );

		return $this->exec ();
	}

	/**
	 * 一个参数是设置修改内容
	 * 多个参考下面参数使用
	 * UPDATE($table, $set, $where, $limit)
	 *
	 * @param $update
	 *
	 * @return bool
	 */
	final function update ( $update )
	{
		$this->section[ 'handle' ] = 'update';
		$arg_num = func_num_args ();
		$arg_num = $arg_num > 4 ? 4 : $arg_num;
		if ( $arg_num > 1 ) {
			$arg_list = func_get_args ();
			for ( $i = 0; $i < $arg_num; $i ++ ) {
				switch ( $i ) {
					case 0:
						$this->setTable ( $arg_list[ $i ] );
						break;
					case 1:
						$this->_set ( $arg_list[ $i ], 'update' );
						break;
					case 2:
						$this->where ( $arg_list[ $i ] );
						break;
					case 3:
						$this->limit ( $arg_list[ $i ] );
						break;
				}
			}

			return $this->exec ();
		} else {

			$this->section[ 'handle' ] = 'update';
			if ( is_string ( $update ) ) {
				$this->_set ( $update, 'update' );

				return $this->exec ();
			}
			$keys = array_keys ( $update );
			if ( in_array ( '0', $keys ) ) {
				$this->_set ( $update, 'update' );
			} else {
				$values = array_values ( $update );

				$size = count ( $keys );
				$ud = NULL;
				for ( $i = 0; $i < $size; $i ++ ) {
					if ( $i != 0 )
						$ud .= ',';
					$ud .= $keys[ $i ] . ' = ' . ( is_array ( $values[ $i ] ) ? $this->addslashes ( json_encode ( $values[ $i ], JSON_UNESCAPED_UNICODE ) ) : ( is_object ( $values[ $i ] ) ? $this->addslashes ( serialize ( $values[ $i ] ) ) : $this->addslashes ( $values[ $i ] ) ) ) . '';
				}
				$this->_set ( $ud, 'update' );
			}

			return $this->exec ();
		}
	}

	/**
	 * 清理缓存
	 * 由于目前没有找到好的数据库缓存方法,不再使用 [PGF]
	 *
	 * @return mixed
	 */
	final function clear_cache ()
	{
		return Cache::flush ( $this->config [ 'cache_dir' ] . '/' . $this->get_table () );
	}

	/**
	 * 获取表名
	 *
	 * @param bool $table 如果存在将会加入表前缀返回
	 *
	 * @return string
	 */
	final public function get_table ( $table = FALSE )
	{
		if ( !$table )
			return ( isset( $this->section[ 'table' ] ) && !empty( $this->section[ 'table' ] ) ) ? $this->section[ 'table' ] : $this->config [ 'table_pre' ] . $this->table;
		else
			return $this->config [ 'table_pre' ] . $table;
	}

	/**
	 * @param bool $sql
	 *
	 * @return mixed
	 */
	final function exec ( $sql = FALSE )
	{
		if ( !$sql )
			$this->compile ();
		$sql = $sql ? $sql : $this->sql;

		//--转表前缀
		$this->parseTablePre ( $sql );
		$this->lastSql = $sql;
		Debug::add ( $sql, 2 );
		$this->_reset ();
		if ( isset( $this->data[ $this->table ] ) )
			unset( $this->data[ $this->table ] );
		if ( $res = $this->_exec ( $sql ) !== FALSE ) {
			return $res;
		} else {
            Debug::error($this->getError(),'DB');
			return $res;
		}
	}

	/**
	 * 解析出完整的SQL命令
	 * 返回解析好的SQL命令或者返回false
	 *
	 * @return String or false
	 */

	final function compile ()
	{
		$this->section[ 'table' ] = $this->get_table ();
		if ( $this->section[ 'handle' ] == 'insert' ) {
			$this->sql .= 'INSERT' . ' INTO ' . $this->section[ 'table' ] . ' ' . $this->section[ 'insert' ];
		} else {
			if ( $this->section[ 'handle' ] == 'select' )
				$sql = "{$this->section['handle']} {$this->section['select']} from {$this->section['table']}";
			elseif ( $this->section[ 'handle' ] == 'update' )
				$sql = "{$this->section['handle']} {$this->section['table']} set {$this->section['update']}";
			elseif ( $this->section[ 'handle' ] == 'delete' )
				$sql = "{$this->section['handle']} from {$this->section['table']}";
			if ( !empty( $sql ) ) {
				$sql .= ( $this->section[ 'join' ] ? " " . $this->section[ 'join' ] : '' ) . ( $this->section[ 'where' ] ? " where {$this->section['where']}" : '' ) . ( $this->section[ 'group' ] ? " group by {$this->section['group']}" : '' ) . ( $this->section[ 'orderby' ] ? " order by {$this->section['orderby']}" : '' ) . ( $this->section[ 'limit' ] ? " limit  {$this->section['limit']}" : '' );

				return $this->sql .= $sql;
			} else {
				echo $this->section[ 'handle' ] . 'method is undefined!';
			}

			return FALSE;
		}

		return FALSE;
	}

	/**
	 * 重置查询
	 */
	final function _reset ()
	{
		$this->section = [
			'handle'  => 'select',
			'select'  => '*',
			'insert'  => '',
			'table'   => $this->get_table ( $this->table ),
			'set'     => '',
			'where'   => '',
			'join'    => '',
			'group'   => '',
			'orderby' => '',
			'limit'   => '',
		];
		$this->sql = '';
	}

	/**
	 * 一个参数时只设置插入内容
	 * 多个参数参考下面函数
	 * INSERT($table,$value)
	 *
	 * @param $insert
	 *
	 * @return bool
	 */
	final function insert ( $insert )
	{
		$this->section[ 'handle' ] = 'insert';
		$arg_num = func_num_args ();

		if ( $arg_num > 1 ) {
			$arg_list = func_get_args ();
			$this->setTable ( $arg_list[ 0 ] )->insert ( $arg_list[ 1 ] );

			return $this->exec ();
		} else {
			foreach ( $insert as $key => $value ) {
				$insert[ $key ] = is_array ( $value ) ? $this->addslashes ( json_encode ( $value, JSON_UNESCAPED_UNICODE ) ) : ( is_object ( $value ) ? $this->addslashes ( serialize ( $value ) ) : $this->addslashes ( $value ) );
			}
			$this->section[ 'insert' ] = is_array ( $insert ) ? '(' . implode ( ',', array_keys ( $insert ) ) . ') VALUES (' . implode ( ',', array_values ( $insert ) ) . ')' : "VALUES('{$insert}')";

			return $this->exec ();
		}
	}

	/**
	 * @param $table
	 * @param $on1
	 * @param $on2
	 *
	 * @return DBase
	 */
	final function leftJoin ( $table, $on1, $on2 )
	{
		return $this->join ( $table, $on1, $on2, 'left' );
	}

	/**
	 * @param $table
	 * @param $on1
	 * @param $on2
	 * @param $ori
	 *
	 * @return $this
	 */
	final function join ( $table, $on1, $on2, $ori )
	{
		if ( $this->section[ 'join' ] == '' )
			$this->section[ 'join' ] = $ori . ' join ' . $this->config [ 'table_pre' ] . $table . " on " . $this->config [ 'table_pre' ] . $on1 . '=' . $this->config [ 'table_pre' ] . $on2;
		else
			$this->section[ 'join' ] .= ' ' . $ori . ' join ' . $this->config [ 'table_pre' ] . $table . " on " . $this->config [ 'table_pre' ] . $on1 . '=' . $this->config [ 'table_pre' ] . $on2;

		return $this;
	}

	/**
	 * @param $table
	 * @param $on1
	 * @param $on2
	 *
	 * @return DBase
	 */
	final function rightJoin ( $table, $on1, $on2 )
	{
		return $this->join ( $table, $on1, $on2, 'right' );
	}

	/**
	 * @param $table
	 * @param $on1
	 * @param $on2
	 *
	 * @return DBase
	 */
	final function fullJoin ( $table, $on1, $on2 )
	{
		return $this->join ( $table, $on1, $on2, 'full' );
	}

	/**
	 * @param $table
	 * @param $on1
	 * @param $on2
	 *
	 * @return DBase
	 */
	final function innerJoin ( $table, $on1, $on2 )
	{
		return $this->join ( $table, $on1, $on2, 'inner' );
	}

	/**
	 * @param bool $all
	 *
	 * @return $this
	 */
	final function union ( $all = FALSE )
	{
		$handle = $this->section[ 'handle' ];
		$sql = $this->compile ();
		$this->_reset ();
		$this->sql = $sql;
		$this->section[ 'handle' ] = $handle;
		if ( $all )
			$this->sql .= ' union all ';
		else
			$this->sql .= ' union ';

		return $this;
	}

	/**
	 * 字段值不为NULL
	 *
	 * @param $field
	 *
	 * @return self
	 */
	final public function notNull ( $field )
	{
		if ( strpos ( $field, '.' ) )
			$field = $this->config[ 'table_pre' ] . $field;

		return $this->where ( $field . ' not null' );
	}

	/**
	 * 字段值为NULL
	 *
	 * @param $field
	 *
	 * @return self
	 */
	final public function isNull ( $field )
	{
		if ( strpos ( $field, '.' ) )
			$field = $this->config[ 'table_pre' ] . $field;

		return $this->where ( $field . ' is null' );
	}

	/**
	 * 删除记录
	 * 一个参数设置表名
	 * 多个参数参考如下
	 * DELETE($table, $where, $orderby, $limit)
	 *
	 * @param bool $delete
	 *
	 * @return bool|int
	 */
	final function delete ( $delete = FALSE )
	{
		$this->section[ 'handle' ] = 'delete';
		//$this->clear_cache();
		$arg_num = func_num_args ();
		$arg_list = func_get_args ();
		$arg_num = $arg_num > 4 ? 4 : $arg_num;
		if ( $arg_num > 1 ) {
			for ( $i = 0; $i < $arg_num; $i ++ ) {
				switch ( $i ) {
					case 0:
						$this->setTable ( $arg_list[ 0 ] );
						break;
					case 1:
						$this->where ( $arg_list[ 1 ] );
						break;
					case 2:
						$this->orderBy ( $arg_list[ 2 ] );
						break;
					case 3:
						$this->limit ( $arg_list[ 3 ] );
						break;

				}
			}

			return $this->exec ();
		}
		if ( $delete )
			$this->setTable ( $delete );

		return $this->exec ();
	}

	/**
	 * @param $group
	 *
	 * @return $this
	 */
	final function group ( $group )
	{
		$this->section[ 'group' ] = $group;

		return $this;
	}

	/**
	 * 替换表前缀
	 *
	 * @param $sql
	 *
	 * @return string
	 */
	final private function parseTablePre ( &$sql )
	{
		return $sql = str_replace ( '_PREFIX_', $this->config[ 'table_pre' ], $sql );
	}

	/**
	 * 保存数据或者更新数据
	 * 如果设置主键字段 $primary_key 将会判断此字段是否存在，如果存在则会为更新数据
	 *
	 * @param array  $data
	 * @param String $primary_key
	 *
	 * @return  Bool|int
	 */
	final public function save ( $data, $primary_key = '' )
	{
		if ( !is_array ( $data ) ) return FALSE;
		if ( $primary_key != '' ) {
			if ( isset( $data[ $primary_key ] ) && $data[ $primary_key ] ) {
				$primary_value = $data[ $primary_key ];
				unset( $data[ $primary_key ] );

				return $this->where ( $primary_key, $primary_value )->update ( $data );
			} else {
				return $this->insert ( $data );
			}
		} else {
			return $this->insert ( $data );
		}
	}

	/**
	 * 查询SQL
	 *
	 * @param bool $sql
	 *
	 * @return array | DataObject
	 */
	final public function &query ( $sql = FALSE )
	{
		if ( !$sql ) {
			$this->compile ();
		}

		$sql = $sql ? $sql : $this->sql;
		$this->parseTablePre ( $sql );
		$sqlMd5 = md5 ( $sql );
		$this->lastSql = $sql;
		if ( isset( $this->data[ $this->table ][ $sqlMd5 ] ) ) {
			Debug::add ( '从内存读取' . $this->sql, 2 );
			$this->_reset ();

			$data = $this->data[ $this->table ][ $sqlMd5 ];
		} else {
			Debug::add ( $sql, 2 );
			$this->_reset ();
			$this->lastSql = $sql;
			$data = $this->_query ( $sql );
			if ( $data === FALSE ) {
                Debug::error($this->getError(),'DB');
			}
			if ( $data == NULL )         //防止直接返回Null
				$data = [ ];
			$data = $this->stripslashes ( $data );

			if ( !isset( $sqlMd5 ) )
				$sqlMd5 = md5 ( $sql );
			$this->data[ $this->table ][ $sqlMd5 ] = $data;
		}
		if (!empty($data)) {
			$data = new DataObject($data);
		}else{
			$data = [];
		}
		return $data;
	}            //链接数据库方法


	/**
	 * 转义函数
	 * 参数可以为多参数或数组，返回数组
	 *
	 * @param string $var
	 *
	 * @return array
	 */
	public function addslashes ( $var )
	{
		if ( is_array ( $var ) ) {
			foreach ( $var as $k => &$v ) {
				$this->addslashes ( $v );
			}
		} else {
			$var = $this->real_escape_string ( $var );
		}

		return $var;
	}

	/**
	 * @param $var
	 *
	 * @return string
	 */
	public function stripslashes ( $var )
	{
		if ( is_array ( $var ) ) {
			foreach ( $var as $k => &$v ) {
				$this->stripslashes ( $v );
			}
		} else {
			$var = stripslashes ( $var );
		}

		return $var;
	}

	/**
	 * 链接数据库
	 *
	 * @param $configName
	 */
	public function connect ( $configName = 'default' )
	{
		$this->config = Config::database ( $configName );

		return $this->_connect ( $configName );
	}

	/**
	 * 数据库驱动必须创建下列方法
	 * 并且必须返回正确的值
	 *
	 * @param $sql
	 *
	 * @return
	 */

	abstract function _query ( $sql );         //返回值是查询出的数组

    /**
     * @return string
     */
	abstract function getError ();            //返回上一个错误信息
	
	abstract function real_escape_string ( $string ); //特殊字符转义

	/**
	 * @param $sql
	 *
	 * @return mixed
	 */
	abstract function _exec ( $sql );           //执行SQL

	abstract function _connect ( $configName );            //返回处理后的语柄

	abstract function beginTransaction ();   //开启事务

	abstract function commit ();             //关闭事务

	abstract function rollBack ();           //回滚事务
}

//====================    END DB.class.php      ========================//