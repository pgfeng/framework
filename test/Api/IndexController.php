<?php
namespace test\Api;

use GFPHP\Controller;

class IndexController extends Controller
{
    /**
     * @Route all index.html
     * @return mixed|String
     */
    public function actionAction(){
        return $this->display();
    }
}