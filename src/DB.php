<?php
/**
 * Created by PhpStorm.
 * User: PGF(592472116@qq.com)
 * Date: 2016/12/5
 * Time: 10:47
 */

namespace GFPHP;


/**
 * 这其实只是一个静态调用Model的简写形式
 * Class DB
 * @package GFPHP
 */
class DB
{
    /**
     * @param        $table_name
     * @param string $config_name
     * @return Model
     * @throws \Exception
     */
    public static function table ( $table_name, $config_name = 'default' )
    {
        return new Model($table_name,$config_name);
    }

}