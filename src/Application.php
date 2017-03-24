<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/24
 * Time: 20:34
 */

namespace GFPHP;

/**
 * Class Application
 * @package GFPHP
 */
class Application
{

    private $router = [];
    private $database = [];
    /**
     * 路由配置
     * @param array $routers
     *
     * @return $this
     */
    public function router($routers = array()){
        return $this;
    }

    /**
     *
     */
    public function database($database = array()){
        return $this;
    }

    /**
     *
     */
    public function view($views = []){
        return $this;
    }
    /**
     *
     */
    public static function init($app_dir='app'){
        \lunatic\freamwork\Router::get(':all','app\System\Index@index');
        return new static();
    }
    public function run(){
        \lunatic\freamwork\Router::dispatch();
    }
}