<?php

namespace GFPHP;

/**
 * 模板引擎
 * Class Template
 * @package GFPHP
 */
class Template
{
    /**
     * 模板中的变量
     *
     * @var array
     */
    public $var = ['_router' => __URI__,];
    private $literal;
    private $blacks = [];
    /**
     * 是否有布局
     * @var string|bool
     */
    public static $layout = false;

    /**
     * 将默认的模板数据填充进去
     * Template constructor.
     */
    final function __construct()
    {
        $temp_vars = Config::view_vars();
        if ($temp_vars) {
            foreach ($temp_vars as $key => $value) {
                $this->var[$key] = $value;
            }
        }
    }

    /**
     * 判断模板是否应该重新编译
     *
     * @param      $template
     *
     * @param int $template_changeTime
     *
     * @param bool $is_layout
     * @return bool
     */
    public function TemplateChange($template, $template_changeTime = 0, $is_layout = false)
    {
        $template = parse_uri($template);
        $leftDelim = Config::template('leftDelim');
        $rightDelim = Config::template('rightDelim');
        $path['template'] = $this->get_path($template);
        $path['template_c'] = $this->get_path($template, 'view_c');
        if (!$is_layout && !file_exists($path['template_c'])) {
            return TRUE;
        }
        if (!$template_changeTime) {
            $template_changeTime = filemtime($path['template_c']);
        }
        $templateContent = file_get_contents($path['template']);
        //如果已编译模板的不存在或者模板修改时间大于已编译模板的时间将重新编译

        if (filemtime($path['template']) > $template_changeTime) {
            return TRUE;
        } //--判断是否有父级模板
        else if (!preg_match_all('/' . $leftDelim . 'extend\s+[\'|"](.*?)[\'|"]' . $rightDelim . '/is', $templateContent, $matches)) {
            unset($templateContent);
            return FALSE;
        } else {
            foreach ($matches[1] as $template) {
                if ($this->TemplateChange($template, $template_changeTime, true)) {
                    return TRUE;
                }
            }

            return FALSE;
        }
    }

    /**
     * 传入模板名称，返回模板编译后的内容
     *
     * @param      $template
     * @param bool $cacheTime
     * @param bool $cacheKey
     * @return String
     * @throws Exception
     */
    public function fetchTemplate($template, $cacheTime = FALSE, $cacheKey = FALSE)
    {
        $template = parse_uri($template);
        $path['template_c'] = $this->get_path($template, 'view_c');
        $path['template'] = $this->get_path($template);
        //当缓存时间未设置时，将自动获取配置中的缓存时间
        $cache = $cacheTime ? intval($cacheTime) : Config::template('view_cache_time');
        $cache = isset($_POST) && !empty($_POST) ? 0 : $cache;
        $kVar = empty($cacheKey) ? NULL : $cacheKey;
        if (file_exists($path['template'])) {
            if ($this->TemplateChange($template)) {
                $this->write($path['template_c'], $this->template_parse(file_get_contents($path['template'])));
            }
            if (($cache > 0 || $cache < 0) && Config::template('view_cache')) {
                if (!Cache::is_cache($this->get_temp_name($template, $cacheKey), Config::template('view_cache_dir'))) {
                    $content = self::cache_compile($template, $cacheKey);
                    $kVar = $kVar == '' ? '' : '[' . $kVar . ']';
                    Debug::add('Template:写入缓存 ' . $path['template'] . $kVar . ' 缓存时间:' . $cache . '秒.');

                } elseif (($cache < 0 || Cache::time($this->get_temp_name($template, $cacheKey), Config::template('view_cache_dir')) + $cache > time()) && filemtime($path['template_c']) < Cache::time($this->get_temp_name($template, $cacheKey), Config::template('view_cache_dir'))) {
                    $content = Cache::get($this->get_temp_name($template, $cacheKey), Config::template('view_cache_dir'));
                    $kVar = $kVar == '' ? '' : '[' . $kVar . ']';
                    Debug::add('Template:读取缓存 ' . $path['template'] . $kVar . ' 缓存时间:' . $cache . '秒.');

                } else {
                    $content = self::cache_compile($template, $cacheKey);
                    $kVar = $kVar == '' ? '' : '[' . $kVar . ']';
                    Debug::add('Template:更新缓存 ' . $path['template'] . $kVar . ' 缓存时间:' . $cache . '秒.');

                }
            } else {

                foreach ($this->var as $k => $v) {
                    $$k = $v;
                }
                ob_start();
                include $path['template_c'];
                $content = ob_get_contents();
                ob_end_clean();
                Debug::add('Template:使用模板 ' . $path['template'] . ' 未使用缓存.');
            }

            return $content;
        } else {

            header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");
            throw new Exception('Template:模板' . $path['template'] . ' 不存在.');
        }
    }

    /**
     * 传入模板内容,返回模板编译执行后的内容
     *
     * @param $templateCon
     *
     * @return mixed
     */
    public function fetch($templateCon)
    {
        $template = '_tmp' . DIRECTORY_SEPARATOR . time() . random('6');
        /** @var string $template */
        $template_c = $this->get_path($template, 'view_c');
        $this->write($template_c, $this->template_parse($templateCon));
        foreach ($this->var as $k => $v) {
            $$k = $v;
        }
        ob_start();
        include $template_c;
        $content = ob_get_contents();
        ob_end_clean();
        unlink($template_c);

        return $content;
    }

    /**
     * 引用模板
     *
     * @param $template
     * @param bool $cacheTime
     * @param bool $cacheKey
     * @return mixed|String
     * @throws Exception
     */
    public function display($template, $cacheTime = FALSE, $cacheKey = FALSE)
    {
        if (Template::$layout) {
            $content = $this->fetchTemplate($template, $cacheTime, $cacheKey);
            $layout_content = $this->fetchTemplate(Template::$layout, $cacheTime, $cacheKey);
            return str_replace('[ _ _ _ CONTENT _ _ _ ]', $content, $layout_content);
        } else {
            return $this->fetchTemplate($template, $cacheTime, $cacheKey);
        }
    }

    /**
     * 获取路径
     *
     * @param        $templateName
     * @param string $type
     * @param bool $key KEY
     *
     * @return String
     */
    private function get_path($templateName, $type = 'template', $key = FALSE)
    {
        $templateName = str_replace('\\', '/', $templateName);
        switch ($type) {
            case 'template':
                $path = BASE_PATH . Config::template('view_dir') . DIRECTORY_SEPARATOR . GFPHP::$app_name . DIRECTORY_SEPARATOR . $templateName . Config::template('view_suffix');
                if (!$fileExits = file_exists($path)) {
                    $parseTemp = explode('/', $templateName);
                    $module = array_shift($parseTemp);
                    $tempName = implode('/', $parseTemp);
                    $dPath = BASE_PATH . GFPHP::$app_name . DIRECTORY_SEPARATOR . parseDir($module, 'view') . $tempName . Config::template('view_suffix');
                    if ($fileExits = file_exists($dPath)) {
                        $path = $dPath;
                    }
                }
                return $path;
            case 'cache':
                return BASE_PATH . Config::cache('cache_dir') . DIRECTORY_SEPARATOR . Config::template('cache_dir') . DIRECTORY_SEPARATOR . Config::template('view_cache_dir') . DIRECTORY_SEPARATOR . GFPHP::$app_name . DIRECTORY_SEPARATOR . $templateName . $key;
            case 'view_c':
                return BASE_PATH . Config::cache('cache_dir') . DIRECTORY_SEPARATOR . GFPHP::$app_name . DIRECTORY_SEPARATOR . Config::template('view_c_dir') . DIRECTORY_SEPARATOR . Config::template('view_name') . DIRECTORY_SEPARATOR . $templateName . '.php';
        }

        return '';
    }

    /**
     * 写入文件
     * 模板静态缓存不使用此方法
     *
     * @param $path
     * @param $content
     *
     * @return int
     */
    private function write($path, $content)
    {
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, TRUE);

        /** @var string $path */
        return file_put_contents($path, $content);
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function get_var($key)
    {
        return isset($this->var[$key]) ? $this->var[$key] : '';
    }

    /**
     * 编译模板
     *
     * @param string $content 模板内容
     *
     * @return string 编译后的模板内容
     */
    private function template_parse($content)
    {
        $leftDelim = Config::template('leftDelim');
        $rightDelim = Config::template('rightDelim');

        //--替换literal标签
        $content = preg_replace_callback('/' . $leftDelim . 'literal' . $rightDelim . '(.*?)' . $leftDelim . '\/literal' . $rightDelim . '/is', [$this, 'parseLiteral'], $content);

        //==使用过滤器处理标签
        $content = Hooks::filter('template_filter', [$content]);
        preg_match_all('/' . $leftDelim . 'extend [\'|"](.*?)[\'|"]' . $rightDelim . '(.*?)' . $leftDelim . '\/extend' . $rightDelim . '/is', $content, $matches);
        //==处理模板继承
        $this->parseExtend($matches, $content);

        //--还原被替换的literal标签
        $content = preg_replace_callback('/<!--###literal(\d+)###-->/is', [$this, 'restoreLiteral'], $content);
        $content = "<?php /**  GFPHP TemplateBuildTime:" . date("Y/m/d H:i:s") . " **/ ?>" . $content;

        return $content;
    }

    /**
     * 模板继承编译
     *
     * @param $match
     * @param $content
     *
     * @return bool
     */
    private function parseExtend($match, &$content)
    {
        $leftDelim = Config::template('leftDelim');
        $rightDelim = Config::template('rightDelim');
        if (count($match) === 3) {
            foreach ($match[1] as $k => $v) {
                $v = parse_uri($v);
                preg_match_all('/' . $leftDelim . 'block\s+[\'|"](.*?)[\'|"]' . $rightDelim . '(.*?)' . $leftDelim . '\/block' . $rightDelim . '/is', $match[2][$k], $match_blocks);
                if (count($match_blocks) !== 3) {
                    continue;

                }
                //匹配出来的block 合并
                $this->blacks = array_merge($this->blacks, array_combine($match_blocks[1], $match_blocks[2]));
                $matches_blocks = $this->blacks;
                $content = str_replace($match[0][$k], $this->template_parse(file_get_contents($this->get_path($v))), $content);
                $content = preg_replace_callback('/' . $leftDelim . 'block\s+[\'|"](.*?)[\'|"]' . $rightDelim . '(.*?)' . $leftDelim . '\/block' . $rightDelim . '/is', function ($matches) use (&$match, $matches_blocks) {
                    if (count($matches) === 3) {
                        if (isset($matches_blocks[$matches[1]])) {
                            return $matches_blocks[$matches[1]];
                        }

                        return $matches[2];
                    }

                    return '';
                }, $content);
            }

            return TRUE;
        }

        return FALSE;
    }

    /**
     * 替换页面中的literal标签
     *
     * @access private
     *
     * @param string|array $content 模板内容
     *
     * @return string|false
     */
    private function parseLiteral($content)
    {
        if (is_array($content)) {
            $content = $content[1];
        }
        if (trim($content) === '') {
            return '';
        }
        $i = count($this->literal);
        $parseStr = "<!--###literal{$i}###-->";
        $this->literal[$i] = $content;

        return $parseStr;
    }

    /**
     * 还原被替换的literal标签
     *
     * @access private
     *
     * @param string|array $tag literal标签序号
     *
     * @return string|false
     */
    private function restoreLiteral($tag)
    {
        if (is_array($tag)) {
            $tag = $tag[1];
        }
        // 还原literal标签
        $parseStr = $this->literal[$tag];
        // 销毁literal记录
        unset($this->literal[$tag]);

        return $parseStr;
    }

    ///=================获取文件位置======================///

    /**
     * @param $templateName
     * @param $cacheKey
     * @return string
     * @internal param $template
     */
    private function get_temp_name($templateName, $cacheKey)
    {
        $templateName = str_replace('\\', '/', $templateName);

        $templateName = GFPHP::$app_name . DIRECTORY_SEPARATOR . $templateName;
        if ($cacheKey) return $templateName . '-' . $cacheKey; else
            return $templateName;
    }

    /**
     * @param $templateName
     *
     * @return string
     */
    private function runTemp($templateName)
    {

        foreach ($this->var as $k => $v) {
            $$k = $v;
        }
        ob_start();

        include $this->get_path($templateName, 'view_c');

        $content = ob_get_contents();

        ob_end_clean();

        return $content;
    }

    /**
     * 编译保存静态缓存
     *
     * @param string $template template_c
     * @param string $cacheKey template_key
     *
     * @return string
     */
    private function cache_compile($template, $cacheKey)
    {
        $content = $this->runTemp($template);
        Cache::set($this->get_temp_name($template, $cacheKey), $content, Config::template('view_cache_dir'));

        return $content;
    }

    /**
     * 设置变量
     * 一个参数时必须为数组
     * 两个参数是第一个是定义的变量名，第二个是值
     *
     * @param $data
     */
    public function assign($data)
    {
        $arg_num = func_num_args();
        if ($arg_num === 1) {
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    $this->var[$k] = $v;
                }
            }
        } else {
            $this->var[func_get_arg(0)] = func_get_arg(1);
        }
    }
}
