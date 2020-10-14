<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/25
 * Time: 13:57
 */
return [

    //-- 项目URL根目录
    'url_path' => \GFPHP\Http\Request::domain().'/',

    'domain_name' => \GFPHP\Http\Request::domain(),
    //-- 默认模块

    'default_module' => 'Home',

    //--默认控制器
    'default_controller' => 'Index',

    //--默认行为
    'default_method' => 'index',

    //-----默认404
    'default_404' => function () {
        return \GFPHP\GFPHP::$Template->display('@' . METHOD_NAME);
    },

    //-- 自动根据注释更新
    'auto_build' => true,
    
    //--控制器后缀
    'controllerSuffix' => 'Controller',

    //--行为后缀
    'methodSuffix' => 'Action',

];