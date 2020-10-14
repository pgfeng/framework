<?php


namespace GFPHP\Http\Request;


use GFPHP\Http\Request;

trait Methods
{

    /**
     * 是否是Get请求
     * @return bool
     */
    public static function isGetMethod()
    {
        return self::isMethod(MethodsEnum::METHOD_GET);
    }

    /**
     * 是否是Post请求
     * @return bool
     */
    public static function isPostMethod()
    {
        return self::isMethod(MethodsEnum::METHOD_POST);
    }

    /**
     * 是否是Put请求
     * @return bool
     */
    public static function isPutMethod()
    {
        return self::isMethod(MethodsEnum::METHOD_PUT);
    }

    /**
     * 是否是Delete请求
     * @return bool
     */
    public static function isDeleteMethod()
    {
        return self::isMethod(MethodsEnum::METHOD_DELETE);
    }

    /**
     * 验证请求类型
     * @param string $request_method
     * @return bool
     */
    public static function isMethod($request_method)
    {
        return strtoupper($request_method) === self::$method;
    }



}