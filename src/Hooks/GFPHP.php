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
        $replaces[] = "<?php echo view('\\1',array('\\2'=>'\\3')); ?>";

        //====引入带缓存时间和缓存键值的的模板,允许键值为变量
        $patterns[] = "/" . $leftDelim . "template\s+['|\"](.+?)['|\"]\:(-?\d+?):\\$(.+?)" . $rightDelim . "/i";
        $replaces[] = "<?php echo view('\\1',array(),\\2,$\\3); ?>";

        //====引入带缓存时间和缓存键值的的模板
        $patterns[] = "/" . $leftDelim . "template\s+['|\"](.+?)['|\"]\:(-?\d+?):(.+?)" . $rightDelim . "/i";
        $replaces[] = "<?php echo view('\\1',array(),\\2,\\3); ?>";

        //====引入带缓存时间的模板
        $patterns[] = "/" . $leftDelim . "template\s+['|\"](.+?)['|\"]\:(-?\d+?)" . $rightDelim . "/i";
        $replaces[] = "<?php echo view('\\1',array(),\\2); ?>";

        //====引入模板
        $patterns[] = "/" . $leftDelim . "template\s+(.+?)" . $rightDelim . "/i";
        $replaces[] = "<?php echo view(\\1); ?>";

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
        $replaces[] = "<?php } \\\\\\n else { ?>";


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

        //==== 赋值
        $patterns[] = "/" . $leftDelim . "(.+?)=(.+?)" . $rightDelim . "/";
        $replaces[] = "<?php $\\1=='\\2'; ?>";

        //==== ++ --
        $patterns[] = "/" . $leftDelim . "\+\+(.+?)" . $rightDelim . "/";
        $replaces[] = "<?php ++$\\1; ?>";
        $patterns[] = "/" . $leftDelim . "\-\-(.+?)" . $rightDelim . "/";
        $replaces[] = "<?php --$\\1; ?>";
        $patterns[] = "/" . $leftDelim . "(.+?)\-\-" . $rightDelim . "/";
        $replaces[] = "<?php $\\1--; ?>";
        $patterns[] = "/" . $leftDelim . "(.+?)\+\+" . $rightDelim . "/";
        $replaces[] = "<?php $\\1++; ?>";

        //== LOOP循环 相当于foreach 去掉as去掉括号
        $patterns[] = "/" . $leftDelim . "loop\s+(\S+)\s+(\S+)" . $rightDelim . "/i";
        $replaces[] = "<?php if(((is_array( \\1 ) && !empty( \\1 )) || (\\1 instanceof \\Traversable)) && \$loop_index=1) foreach(\\1 AS \\2) { ?>";
        $patterns[] = "/" . $leftDelim . "loop\s+(\S+)\s+(\S+)\s+(\S+)" . $rightDelim . "/i";
        $replaces[] = "<?php if(((is_array( \\1 ) && !empty( \\1 )) || (\\1 instanceof \\Traversable)) && \$loop_index=1) foreach(\\1 AS \\2 => \\3) { ?>";
        $patterns[] = "/" . $leftDelim . "\/loop" . $rightDelim . "/i";
        $replaces[] = '<?php $loop_index++;} ?>';

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
            $cssArray = explode(',', $css);
            $css = '';
            foreach ($cssArray as $c) {
                if (preg_match('/^https?:\/\//i', $c))
                    $css .= '<link href="' . $c . '" type="text/css" rel="stylesheet">';
                else
                    $css .= '<link href="' . Config::template('css_path') . $c . '" type="text/css" rel="stylesheet">';
            }

            return $css;
        }, $str);
        $str = preg_replace_callback('/' . $leftDelim . 'MODULE_NAME' . $rightDelim . '/i', function () {
            return MODULE_NAME;
        }, $str);
        $str = preg_replace_callback('/' . $leftDelim . 'CONTROLLER_NAME' . $rightDelim . '/i', function () {
            return CONTROLLER_NAME;
        }, $str);
        $str = preg_replace_callback('/' . $leftDelim . 'METHOD_NAME' . $rightDelim . '/i', function () {
            return METHOD_NAME;
        }, $str);
        //** 引用js标签
        $str = preg_replace_callback("/" . $leftDelim . "includeScript\s+['|\"](.+?)['|\"]" . $rightDelim . "/i", function ($matches) {
            $js = trim($matches[1]);
            $jsArray = explode(',', $js);
            $js = '';
            foreach ($jsArray as $j) {
                $js .= '<script type="text/javascript" src="' . Config::template('js_path') . $j . '"></script>';
            }

            return $js;
        }, $str);
        $str = preg_replace('/' . $leftDelim . 'JS_PATH' . $rightDelim . '/i', Config::template('js_path'), $str);
        $str = preg_replace('/' . $leftDelim . 'CSS_PATH' . $rightDelim . '/i', Config::template('css_path'), $str);
        $str = preg_replace('/' . $leftDelim . 'PUBLIC_PATH' . $rightDelim . '/i', Config::template('public_path'), $str);
        $str = preg_replace('/' . $leftDelim . 'IMG_PATH' . $rightDelim . '/i', Config::template('img_path'), $str);
        $str = preg_replace($patterns, $replaces, $str);
        return $str;
    }

}