<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/3/25
 * Time: 13:36
 */

namespace GFPHP\Hooks;

use GFPHP\Config;

/**
 * Class GFPHPHooks
 */
class GFPHP
{
    /**
     * @param $str
     * @return mixed
     */
    public function template_parse($str)
    {
        $leftDelim = Config::template('leftDelim');
        $rightDelim = Config::template('rightDelim');
        $patterns = [];
        $replaces = [];

        //注入变量
        $patterns[] = "/" . $leftDelim . "template\s+['|\"](.+?)['|\"]\,['|\"](.+?)['|\"],['|\"](.+?)['|\"]" . $rightDelim . "/iU";
        $replaces[] = "<?php view('\\1',array('\\2'=>'\\3')); ?>";

        //====引入带缓存时间和缓存键值的的模板,允许键值为变量
        $patterns[] = "/" . $leftDelim . "template\s+['|\"](.+?)['|\"]\:(-?\d+?):\\$(.+?)" . $rightDelim . "/i";
        $replaces[] = "<?php view('\\1',array(),\\2,$\\3); ?>";

        //====引入带缓存时间和缓存键值的的模板
        $patterns[] = "/" . $leftDelim . "template\s+['|\"](.+?)['|\"]\:(-?\d+?):(.+?)" . $rightDelim . "/i";
        $replaces[] = "<?php view('\\1',array(),\\2,\\3); ?>";

        //====引入带缓存时间的模板
        $patterns[] = "/" . $leftDelim . "template\s+['|\"](.+?)['|\"]\:(-?\d+?)" . $rightDelim . "/i";
        $replaces[] = "<?php view('\\1',array(),\\2); ?>";

        //====引入模板
        $patterns[] = "/" . $leftDelim . "template\s+(.+?)" . $rightDelim . "/i";
        $replaces[] = "<?php view(\\1); ?>";

        //====引入PHP文件
        $patterns[] = "/" . $leftDelim . "include\s+(.+?)" . $rightDelim . "/i";
        $replaces[] = "<?php include \\1; ?>";

        //====PHP标记
        $patterns[] = "/" . $leftDelim . "php\s+(.+?)" . $rightDelim . "/i";
        $replaces[] = "<?php \\1?>";


        //====IF判断
        $patterns[] = "/" . $leftDelim . "if\s+(.+?)" . $rightDelim . "/i";
        $replaces[] = "<?php if(\\1) { ?>";
        $patterns[] = "/" . $leftDelim . "else" . $rightDelim . "/i";
        $replaces[] = "<?php } else { ?>";

        //当为空时只需
        $patterns[] = "/" . $leftDelim . "elseLoop" . $rightDelim . "/i";
        $replaces[] = "<?php } else { ?>";


        $patterns[] = "/" . $leftDelim . "elseif\s+(.+?)" . $rightDelim . "/";
        $replaces[] = "<?php } elseif (\\1) { ?>";

        $patterns[] = "/" . $leftDelim . "\/if" . $rightDelim . "/i";
        $replaces[] = "<?php } ?>";

        //====FOR循环
        $patterns[] = "/" . $leftDelim . "for\s+(\\$[a-zA-Z_\x7f-\xff][a-zA-Z_\x7f-\xff]*)\s+in+\s+(\d+)\.\.\.(\d+)" . $rightDelim . "/i";
        $replaces[] = "<?php for(\\1=\\2;\\1<=\\3;\\1++) { ?>";
        $patterns[] = "/" . $leftDelim . "for\s+(\\$[a-zA-Z_\x7f-\xff][a-zA-Z_\x7f-\xff]*)\s+in+\s+(\d+)\.\.(\d+)" . $rightDelim . "/i";
        $replaces[] = "<?php for(\\1=\\2;\\1<\\3;\\1++) { ?>";
        $patterns[] = "/" . $leftDelim . "for\s+(.+?)" . $rightDelim . "/i";
        $replaces[] = "<?php for(\\1) { ?>";

        $patterns[] = "/" . $leftDelim . "\/for" . $rightDelim . "/i";
        $replaces[] = "<?php } ?>";

        //==== ++ --
        $patterns[] = "/" . $leftDelim . "\+\+(.+?)" . $rightDelim . "/";
        $replaces[] = "<?php ++\\1; ?>";
        $patterns[] = "/" . $leftDelim . "\-\-(.+?)" . $rightDelim . "/";
        $replaces[] = "<?php --\\1; ?>";
        $patterns[] = "/" . $leftDelim . "(.+?)\-\-" . $rightDelim . "/";
        $replaces[] = "<?php \\1--; ?>";
        $patterns[] = "/" . $leftDelim . "(.+?)\+\+" . $rightDelim . "/";
        $replaces[] = "<?php \\1++; ?>";
        //== LOOP循环 相当于Foreach 去掉as去掉括号

//        {loop $all $item}
//        {elseLoop}
//        {/loop}
        $patterns[] = "/" . $leftDelim . "loop\s+(\S+)\s+(\S+)" . $rightDelim . "/i";
        $replaces[] = "<?php if((is_array( \\1 ) && !empty( \\1 )) || (\\1 instanceof \\Traversable)) foreach(\\1 AS \\2) { ?>";
        $patterns[] = "/" . $leftDelim . "loop\s+(\S+)\s+(\S+)\s+(\S+)" . $rightDelim . "/i";
        $replaces[] = "<?php if((is_array( \\1 ) && !empty( \\1 )) || (\\1 instanceof \\Traversable)) foreach(\\1 AS \\2 => \\3) { ?>";
        $patterns[] = "/" . $leftDelim . "\/loop" . $rightDelim . "/i";
        $replaces[] = "<?php } ?>";

        $patterns[] = "/" . $leftDelim . "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))" . $rightDelim . "/";
        $replaces[] = "<?php echo \\1;?>";

        //public_path目录
        $patterns[] = '/' . $leftDelim . 'PUBLIC_PATH' . $rightDelim . '/i';
        $replaces[] = '<?php echo $this->var[\'view_vars\'][\'public_path\'];?>';
        $str = preg_replace_callback("/" . $leftDelim . "(\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)" . $rightDelim . "/s", function ($matches) {
            $match = '<?php echo ' . $matches[1] . ';?>';

            return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $match));
        }, $str);
        // 解析用.链接的变量
        $str = preg_replace_callback("/" . $leftDelim . "\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff.?]*)" . $rightDelim . "/", function ($matchs) {
            $v = $matchs[1];
            $v = explode('.', $v);
            $key = array_shift($v);
            $p = '[\'' . implode('\'][\'', $v) . '\']';
            $var = '$' . $key . $p;
            $match = '<?php echo ' . $var . ';?>';

            return $match;
        }, $str);

        //** 引入CSS标签
        $str = preg_replace_callback("/" . $leftDelim . "includeStyle\s+['|\"](.+?)['|\"]" . $rightDelim . "/i", function ($matches) {
            $css = trim($matches[1]);
            $cssarray = explode(',', $css);
            $css = '';
            foreach ($cssarray as $c) {
                if (preg_match('/^https?:\/\//i', $c))
                    $css .= '<link href="' . $c . '" type="text/css" rel="stylesheet">';
                else
                    $css .= '<link href="' . Config::template('css_path') . $c . '" type="text/css" rel="stylesheet">';
            }

            return $css;
        }, $str);
        $str = preg_replace('/' . $leftDelim . 'CSS_PATH' . $rightDelim . '/i', '<?php echo $this->var[\'view_vars\'][\'css_path\'];?>', $str);
        $str = preg_replace('/' . $leftDelim . 'BASE_DIR' . $rightDelim . '/i', '<?php echo BASE_DIR;?>', $str);
        $str = preg_replace_callback('/' . $leftDelim . 'MODULE_PATH' . $rightDelim . '/i', function () {
            return Config::runtime('module_path');
        }, $str);
        //** 引用js标签
        $str = preg_replace_callback("/" . $leftDelim . "includeScript\s+['|\"](.+?)['|\"]" . $rightDelim . "/i", function ($matches) {
            $js = trim($matches[1]);
            $jsarray = explode(',', $js);
            $js = '';
            foreach ($jsarray as $j) {
                $js .= '<script type="text/javascript" src="' . Config::template('js_path') . $j . '"></script>';
            }

            return $js;
        }, $str);
        //**  将系统设定的JS文件路径出解析
        $str = preg_replace('/' . $leftDelim . 'JS_PATH' . $rightDelim . '/i', '<?php echo $this->var[\'view_vars\'][\'js_path\'];?>', $str);
        if (Config::view_vars('img_path'))
            $str = preg_replace('/' . $leftDelim . 'IMG_PATH' . $rightDelim . '/i', '<?php echo $this->var[\'view_vars\'][\'img_path\'];?>', $str);
        $str = preg_replace($patterns, $replaces, $str);

        return $str;
    }

}