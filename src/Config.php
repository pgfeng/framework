<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/24
 * Time: 20:57
 */

namespace GFPHP;

/**
 * Class Config
 * @package GFPHP
 * 存放配置 --- 直接COPY过来了,O(∩_∩)O哈哈~
 * 可以将配置全部放入此类，方便使用,或者在其中放置数组对象都可以
 * 创建时间：2014-08-08 13:12 PGF
 * 修改时间：2015-06-18 18:10 PGF 修改set方法，可以将设置保存到文件
 * @method static template( $key = '' ) return string|array
 * @method static cache( $key = '' )
 * @method static hooks( $key = '' )
 * @method static autoload( $key = '' )
 * @method static config( $key = '' )
 * @method static view_vars( $key = '' )
 * @method static database( $key = '' )
 * @method static router( $key = '' )
 * @method static values( $key = '' )
 * @method static debug( $key = '' )
 * @method static file( $key = '' )
 */
class Config
{
    public static $config = [ ];

    /**
     * 修改或者保存配置
     *
     * @param  array   $config 数组格式配置
     * @param string   $type   配置文件名称
     * @param bool|int $save   修改后是否同时保存到配置文件
     *
     * @return bool|int
     */
    public static function set ( $config, $type = 'config' )
    {
        if ( is_array ( $config ) )
            foreach ( $config as $k => $v ) {
                self::$config[ $type ][ $k ] = $v;
            }
        else
            throw new \Exception('$config must is array');
        return TRUE;
    }


    /**
     * 使用静态方式调用配置
     *
     * @param $a
     * @param $v
     *
     * @return static
     */
    public static function __callStatic ( $a, $v )
    {
        if ( !empty( $v ) )
            return count ( $v ) == 1 ? self::get ( $a, $v[ 0 ] ) : self::get ( $a, $v[ 0 ], $v[ 1 ] );

        return self::get ( $a );
    }

    /**
     * 获取配置内容
     *
     * @param      $type
     * @param bool $name
     * @param bool $value
     *
     * @return mixed
     */
    public static function &get ( $type, $name = FALSE, $value = FALSE )
    {
        if ( !isset( self::$config[ $type ] ) )
            self::$config[ $type ] = include dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.'Config/'.$type.'.php';
        if ( $name ) {
            if ( FALSE == $value ) {
                return self::$config[ $type ][ $name ];
            } else {
                self::$config[ $type ][ $name ] = $value;

                return self::$config[ $type ][ $name ];
            }
        } else {
            return self::$config[ $type ];
        }
    }


}