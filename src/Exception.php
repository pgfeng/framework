<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/24
 * Time: 20:42
 */

namespace GFPHP;


use Throwable;

class Exception extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if(Config::config('develop_mod')){
            parent::__construct($message, $code, $previous);
        }else{
            echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <title>'.$message.'</title>
    <!--样式-->
    <style type="text/css">

        *{ padding: 0; margin: 0; }
        html{ overflow-y: scroll; }
        body{ background: #fff; font-family: \'Source_Code_Pro-雅黑 混合体\'; color: #333; font-size: 16px; }
        img{ border: 0; }
        .error{ width: 90%;padding-top: 15%; margin: 0 auto;text-align: center}
        .face{ font-size: 100px; font-weight: normal; line-height: 120px; margin-bottom: 12px; padding-bottom: 5%}
        h1{ font-size: 32px; line-height: 48px; }
        .error .content{padding-top: 50px;}
        .error .info{ margin-bottom: 12px; }
        .error .info .title{ margin-bottom: 15px; }
        .error .info .title h3{ color: #000; font-weight: 700; font-size: 16px; }
        .error .info .text{ line-height: 54px; }
    </style>
    <!--头部-->
    <style>
        body{
            background: #fefefe;
        }
        *{
            transition: all 1s;
        }
    </style>
    <script src="http://apps.bdimg.com/libs/jquery/1.11.1/jquery.min.js"></script>
</head>
<body>
<!--内容-->
<div class="error">
    <p class="face">o(>﹏<)o</p>
    <h1>'.$message.'</h1>
    <div class="content">
        <div class="info">
            <div class="title">
                <h3></h3>
            </div>
            <div class="text">
                  
            </div>
        </div>
    </div>
</div>
</body>
</html>
HTML;
        }
    }
}