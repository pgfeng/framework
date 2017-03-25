<?php
/**
 * Created by PhpStorm.
 * User: PGF(592472116@qq.com)
 * Date: 2016/12/5
 * Time: 10:47
 */

namespace GFPHP;


class DB
{
    /**
     * @param        $table_name
     * @param string $config_name
     * @return DBase
     * @throws \Exception
     */
	public static function table ( $table_name, $config_name = 'default' )
	{
	    $config = Config::database();
	    if(!isset($config[$config_name])){
	        throw new \Exception('数据库配置 ['.$config_name.'] 不存在!');
        }
        $driver = $config[$config_name]['driver'];
        $driver = '\\GFPHP\\Database\\'.$driver;
        $db = new $driver;
        $db->connect($config_name);
		if ( !$db ) {
			throw new \Exception( '数据库配置有误!' );
		}
		if ( !$db ) {
			throw new \Exception( '数据库配置有误!' );
		}
		$db->table = $table_name;
		$db->_reset ();
		return $db;
	}

}