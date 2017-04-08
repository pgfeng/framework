<?php

use GFPHP\Config;
use GFPHP\Debug;
use GFPHP\Router, GFPHP\Cache, GFPHP\Loader, GFPHP\Hooks;
use GFPHP\Model;
/**
 * 全局函数
 * 此处函数可在任何地方使用
 * 创建时间：2014-08-08 13:38 PGF
 * 修改时间：2016-02-15 10:25 PGF 添加
 */

/**
 * 根据行为获取网址
 *
 * @param String
 * @param String
 *
 * @return String
 */
function url ( $action = '', $get = '' )
{
	if ( !$action )
		return Config::router ( 'url_path' );

	return Router::$router->url ( $action, $get );
}

/**
 *
 */
function is_empty ( $data )
{
	
}

/**
 * 展示错误信息
 *
 * @param $msg
 */
function show_error ( $msg = '未知错误' )
{
    if (IS_AJAX)
        echo_json($msg,false);
    else {
        include parseDir(Config::config('core_dir'), 'Tpl') . 'Error.php';
        Debug::stop();
    }
}

/**
 * @param        $name
 * @param string $space
 *
 * @return mixed
 */
function is_cache ( $name, $space = '' )
{
	return Cache::is_cache ( $name, $space );
}

/**
 * 在没有创建模型类的时候可以使用此方法，不建议使用
 * 调用模型
 *
 * @param string $table
 *
 * @param        $configName
 *
 * @return \Model\Model
 */
function model ( $table = '', $configName = FALSE )
{
	return new Model ( $table, $configName );
}


/**
 * 引入Helper
 *
 * @param string $helpername 函数库名称
 *
 * @return bool
 */
function Helper ( $helpername )
{
	return Loader::Helper ( $helpername );
}

/**
 * 编译视图
 *
 * @param        $name
 * @param bool   $data
 * @param int    $cacheTime
 * @param string $cacheKey
 *
 * @return bool
 */
function view ( $name, $data = FALSE, $cacheTime = 0, $cacheKey = '' )
{
	if($data)
        \GFPHP\GFPHP::$Template->assign($data);
	return \GFPHP\GFPHP::$Template->display($name,$cacheTime,$cacheKey);
}

/**
 * 钩子监听
 *
 * @param        $Hooks_name
 * @param array  $params
 * @param string $type 类型
 *
 * @return array|mixed|void
 */
function hooks ( $Hooks_name, $params, $type = 'call' )
{
	switch ( $type ) {
		case 'call':
			return Hooks::call ( $Hooks_name, $params );
			break;
		case 'listen':
			Hooks::listen ( $Hooks_name, $params );
			break;
		case 'filter':
			return Hooks::filter ( $Hooks_name, $params );
	}

	return FALSE;
}

/**
 * 运行控制器指定方法
 *
 * @param        $cname
 * @param string $method
 * @param string $var
 *
 * @return bool
 */
function controller ( $cname, $method = '', $var = '' )
{
	return Loader::controller ( $cname, $method, $var );
}

/**
 * 加载第三方类库
 *
 * @param $Vendor_name
 *
 * @return Object
 */
function Vendor ( $Vendor_name )
{
	return Loader::vendor ( $Vendor_name );
}

/**
 * 解析正确路径
 *
 * @return string
 */
function parseDir ()
{
	$dirs = func_get_args ();
	$dir = '';
	foreach ( $dirs as $d ) {
		$d = trim ( $d );
		if ( strlen ( $d ) > 0 ) {
			if ( $d[ 0 ] == '/' )
				$d = substr ( $d, 1, strlen ( $d ) - 1 );
			if ( $d[ strlen ( $d ) - 1 ] != '/' )
				$d .= '/';
			$dir .= $d;
		}
	}
	$dir = explode ( '/', $dir );
	$c = count ( $dir );
	for ( $i = 0; $i < $c; $i ++ ) {
		if ( $dir[ $i ] == '' )
			continue;
		if ( strpos ( '..', $dir[ $i ] ) !== FALSE ) {
			if ( $i > 0 ) {
				unset( $dir[ $i - 1 ] );
				unset( $dir[ $i ] );
			}
		}
	}

	return implode ( '/', $dir );
}

/**
 * 字符截取
 *
 * @param        $string
 * @param        $length
 * @param string $dot
 *
 * @return mixed|string
 */
function str_cut ( $string, $length, $dot = '...' )
{
	$length = intval ( $length );
	//--将html标签剔除
	$string = strip_tags ( $string );
	//--获取内容长度
	$strlen = mb_strlen ( $string, 'utf8' );
	//--如果没有超过直接返回
	if ( $strlen <= $length ) return $string;

//    $string = str_replace([' ', ' ', '&amp;', '"', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '<', '>', '&middot;', '&hellip;'], ['', '',
//        '&', '"', "'", '&ldquo;', '&rdquo;', '&mdash;', '<', '>', '&middot;', '&hellip;'], $string);
//    $string = preg_replace("/<\/?[^>]+>/i", '', $string);
	$strcut = mb_substr ( $string, 0, $length, 'utf-8' );

	return $strcut . $dot;
}

/**
 * 创建文件所在目录
 *
 * @param     $path
 * @param int $mode
 *
 * @return bool
 */
function mkPathDir ( $path, $mode = 0777 )
{
	$dir = dirname ( $path );
	if ( !is_dir ( $dir ) ) {
		if ( !file_exists ( $dir ) ) {
			return mkdir ( $dir, $mode, TRUE );
		} else {
			return FALSE;
		}
	} else {
		return TRUE;
	}
}

//function Log ($content, $type = \GFPHP\Log::MESSAGE)
//{
//	if ( Config::debug ( 'log' ) ) {
//	    \GFPHP\Log::log($content,$type);
//	}
//}

/**
 * 获取数组内数据，如果不存在则返回空值
 *
 * @param bool  $params
 * @param array $array
 *
 * @return array|bool|null
 */
function getValue ( $params = FALSE, $array )
{
	if ( $params == FALSE ) {
		return $array;
	} else if ( is_array ( $params ) ) {
		$data = [ ];
		foreach ( $params as $key ) {
			$data[ $key ] = getValue ( $key, $array );
		}

		return $data;
	} else {
		return isset( $array[ $params ] ) ? $array[ $params ] : NULL;
	}
}

/**
 * 获取GET值
 *
 * @param $params
 *
 * @return array|bool|null
 */
function GET ( $params=false )
{
	return getValue ( $params, $_GET );
}


/**
 * 获取POST值
 *
 * @param $params
 *
 * @return array|bool|null
 */
function POST ( $params=false )
{
	return getValue ( $params, $_POST );
}


/**
 * 获取POST值
 *
 * @param $params
 *
 * @return array|bool|null
 */
function FILES ( $params=false )
{
	return getValue ( $params, $_FILES );
}

/**
 * 获取SESSION值
 *
 * @param $params
 *
 * @return array|bool|null
 */
function SESSION ( $params=false )
{
	return getValue ( $params, $_SESSION );
}

/**
 * 获取COOKIE值
 *
 * @param $params
 *
 * @return array|bool|null
 */
function COOKIE ( $params=false )
{
	return getValue ( $params, $_COOKIE );
}

/**
 * 获取REQUEST值
 *
 * @param $params
 *
 * @return array|bool|null
 */
function REQUEST ( $params = FALSE )
{
	$GET = $_GET;
	unset( $GET[ '_router' ] );
	$_REQUEST = array_merge ( $_POST, $GET );

	return getValue ( $params, $_REQUEST );
}

/**
 * 获取ip地址
 */
function ip ()
{

	if ( isset( $_SERVER ) ) {

		if ( isset( $_SERVER[ "HTTP_X_FORWARDED_FOR" ] ) ) {

			$IPaddress = $_SERVER[ "HTTP_X_FORWARDED_FOR" ];

		} else if ( isset( $_SERVER[ "HTTP_CLIENT_IP" ] ) ) {

			$IPaddress = $_SERVER[ "HTTP_CLIENT_IP" ];

		} else {

			$IPaddress = $_SERVER[ "REMOTE_ADDR" ];

		}

	} else {

		if ( getenv ( "HTTP_X_FORWARDED_FOR" ) ) {

			$IPaddress = getenv ( "HTTP_X_FORWARDED_FOR" );

		} else if ( getenv ( "HTTP_CLIENT_IP" ) ) {

			$IPaddress = getenv ( "HTTP_CLIENT_IP" );

		} else {

			$IPaddress = getenv ( "REMOTE_ADDR" );

		}

	}

	return preg_match ( '/[\d\.]{7,15}/', $IPaddress, $matches ) ? $matches [ 0 ] : '';
}

/**
 * 人性化的时间显示
 *
 * @param  String $time        Unix时间戳，默认为当前时间
 * @param  string $date_format 默认时间显示格式
 *
 * @return String
 */
function toTime ( $time = NULL, $date_format = 'Y/m/d H:i:s' )
{
	$time = is_null ( $time ) ? time () : $time;
	$now = time ();
	$diff = $now - $time;
	if ( $diff < 10 )
		return '刚刚 ';
	if ( $diff < 60 )
		return $diff . '秒前 ';
	if ( $diff < ( 60 * 60 ) )
		return floor ( $diff / 60 ) . '分钟前 ';
	if ( date ( 'Ymd', $time ) == date ( 'Ymd', $now ) )
		return '今天 ' . date ( 'H:i:s', $time );

	return date ( $date_format, $time );
}

/**
 * 文件大小单位换算
 *
 * @param  int $byte 文件Byte值
 *
 * @return String
 */
function toSize ( $byte )
{
	if ( $byte >= pow ( 2, 40 ) ) {
		$return = round ( $byte / pow ( 1024, 4 ), 2 );
		$suffix = "TB";
	} elseif ( $byte >= pow ( 2, 30 ) ) {
		$return = round ( $byte / pow ( 1024, 3 ), 2 );
		$suffix = "GB";
	} elseif ( $byte >= pow ( 2, 20 ) ) {
		$return = round ( $byte / pow ( 1024, 2 ), 2 );
		$suffix = "MB";
	} elseif ( $byte >= pow ( 2, 10 ) ) {
		$return = round ( $byte / pow ( 1024, 1 ), 2 );
		$suffix = "KB";
	} else {
		$return = $byte;
		$suffix = "Byte";
	}

	return $return . " " . $suffix;
}

/**
 * xss过滤函数 --来自PHPCMS
 *
 * @param $string
 *
 * @return string
 */
function remove_xss ( $string )
{
	$string = preg_replace ( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $string );

	$parm1 = [ 'javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base' ];

	$parm2 = [ 'onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload' ];

	$parm = array_merge ( $parm1, $parm2 );

	for ( $i = 0; $i < sizeof ( $parm ); $i ++ ) {
		$pattern = '/';
		for ( $j = 0; $j < strlen ( $parm[ $i ] ); $j ++ ) {
			if ( $j > 0 ) {
				$pattern .= '(';
				$pattern .= '(&#[x|X]0([9][a][b]);?)?';
				$pattern .= '|(&#0([9][10][13]);?)?';
				$pattern .= ')?';
			}
			$pattern .= $parm[ $i ][ $j ];
		}
		$pattern .= '/i';
		$string = preg_replace ( $pattern, '', $string );
	}

	return $string;
}

/**
 * 函数来源 ThinkPhp
 *
 * @param      $var
 * @param bool $echo
 * @param null $label
 * @param bool $strict
 *
 * @return mixed|null|string
 */
function dump ( $var, $echo = TRUE, $label = NULL, $strict = TRUE )
{
	$label = ( $label === NULL ) ? '' : rtrim ( $label ) . ' ';
	if ( !$strict ) {
		if ( ini_get ( 'html_errors' ) ) {
			$output = print_r ( $var, TRUE );
			$output = "<pre>" . $label . htmlspecialchars ( $output, ENT_QUOTES ) . "</pre>";
		} else {
			$output = $label . print_r ( $var, TRUE );
		}
	} else {
		ob_start ();
		var_dump ( $var );
		$output = ob_get_clean ();
		if ( !extension_loaded ( 'xdebug' ) ) {
			$output = preg_replace ( "/\]\=\>\n(\s+)/m", "] => ", $output );
			$output = '<pre>' . $label . htmlspecialchars ( $output, ENT_QUOTES ) . '</pre>';
		}
	}
	if ( $echo ) {
		echo ( $output );

		return NULL;
	} else
		return $output;
}

/**
 * 网址跳转
 *
 * @param $url
 * @param $time
 */
function redirect ( $url, $time = 0 )
{
	if ( $time > 0 )
		header ( 'Refresh:' . $time . ';url=' . $url );
	else {
		header ( 'Location:' . $url );
		exit;
	}
}

/**
 * 将string转换成可以在正则中使用的正则
 *
 * @param $str
 *
 * @return string
 */
function srtToRE ( $str )
{
	$res = '';
	$regs = [
		'.', '+', '-', '$', '[', ']', '{', '}', '(', ')', '\\', '^', '|', '?', '*', '/', '_',
	];
	$str = str_split ( $str, 1 );
	foreach ( $str as $s ) {
		if ( in_array ( $s, $regs ) )
			$res .= '\\' . $s;
		else
			$res .= $s;
	}

	return $res;
}

/**
 * 判断请求是否为ajax请求
 *
 * @return bool
 */
function isAjax ()
{
	return IS_AJAX;
}

/**
 * 输出JSON信息
 *
 * @param        $msg
 * @param string $status true|false
 * @param array  $data
 */
function echo_json ( $msg, $status = true, $data = [ ] )
{

	header ( "Content-Type:application/Json; charset=UTF-8" );
	$array = [
		'msg'    => $msg,
		'status' => $status,
	];
	$array = array_merge ( $array, $data );
	echo json_encode ( $array, JSON_UNESCAPED_UNICODE );
	Debug::stop ();
}

/**
 * 输出JSON信息
 *
 * @param $data
 */
function response_json ( $data )
{
	header ( "Content-Type:application/Json; charset=UTF-8" );
	echo json_encode ( $data, JSON_UNESCAPED_UNICODE );
	Debug::stop();
}

/**
 * 判断是否为GET请求
 * 如果有参数会判断参数是否存在,如果全部存在才会返回TRUE,否则返回FALSE
 *
 * @param array $param
 *
 * @return bool
 */
function isGet ( $param = [ ] )
{
	if ( IS_GET ) {
		if ( is_string ( $param ) ) {
			if ( isset( $_GET[ $param ] ) ) {
				return TRUE;
			}
		} else {
			if ( !empty( $param ) ) {
				foreach ( $param as $item ) {
					if ( !isset( $_GET[ $item ] ) )
						return FALSE;
				}
			}
		}

		return TRUE;
	} else {
		return FALSE;
	}
}

/**
 * 判断是否为POST请求
 * 如果有参数，则会去判断这些参数是否存在，如果存在返回TRUE
 *
 * @param array $param
 *
 * @return bool
 */
function isPost ( $param = [ ] )
{
	if ( IS_POST ) {
		if ( is_string ( $param ) ) {
			if ( isset( $_POST[ $param ] ) ) {
				return TRUE;
			}
		} else {
			if ( !empty( $param ) ) {
				foreach ( $param as $item ) {
					if ( !isset( $_POST[ $item ] ) )
						return FALSE;
				}
			}
		}

		return TRUE;
	} else {
		return FALSE;
	}
}

/**
 * 发送邮件
 *
 * @param string $address 邮件地址 如果为数组，则为多个
 * @param string $Subject 邮件主题
 * @param string $body    内容
 * @param bool   $is_html 是否为HTML
 * @param bool   $replayaddress
 *
 * @return
 */
function send_mail ( $address, $Subject, $body, $is_html = FALSE, $replayaddress = FALSE )
{
	$Mailer = new Mailer();
	$Mailer = $Mailer->init ();
	if ( is_array ( $address ) ) {
		foreach ( $address as $adr ) {
			$Mailer->addAddress ( $adr );
		}
	} else {
		$Mailer->addAddress ( $address );
	}
	$Mailer->isHTML ( $is_html );
	$Mailer->Subject = $Subject;
	$Mailer->Body = $body;
	if ( $replayaddress )
		$Mailer->setFrom ( $replayaddress, '代发送' );
	$ret = $Mailer->send ();
	if ( !$ret ) {
		echo 'Mailer Error: ' . $Mailer->ErrorInfo;
	}

	return $ret;
}

/**
 * 系统路径转网址路径
 *
 * @param $path
 *
 * @return mixed
 */
function pathToUrl ( $path )
{
	$path = str_replace ( __ROOT__, '/', $path );

	return str_replace ( DIRECTORY_SEPARATOR, '/', $path );
}

/**
 * 产生随机字符串
 *
 * @param    int    $length 输出长度
 * @param    string $chars  可选的 ，默认为 0123456789
 *
 * @return   string     字符串
 */
function random ( $length, $chars = '0123456789' )
{
	$hash = '';
	$max = strlen ( $chars ) - 1;
	for ( $i = 0; $i < $length; $i ++ ) {
		$hash .= $chars[ mt_rand ( 0, $max ) ];
	}

	return $hash;
}

/**
 * 图片等比缩放，优先宽度
 *
 * @param string      $imgUrl 图片地址
 * @param string      $width  宽度
 * @param bool|string $height 高度
 *
 * @return String 图片地址
 */
function thumb ( $imgUrl, $width = 'auto', $height = 'auto' )
{
	if ( !$imgUrl ) {
		return '';
	}
	if ( $width == FALSE && $height == FALSE ) {
		return $imgUrl;
	} else {
		if ( $width == 'auto' && $height == 'auto' ) {
			return $imgUrl;
		}
		$img = Loader::vendor ( 'image' );
		$info = pathinfo ( $imgUrl );
		$dir = explode ( '/', $info[ 'dirname' ] );
		$dir = end ( $dir );
		$picPath = __ROOT__ . 'runtime/thumb/' . $dir . '/' . $info[ 'filename' ] . '/' . $width . 'X' . $height . '.png';
		$urlpath = '/runtime/thumb/' . $dir . '/' . $info[ 'filename' ] . '/' . $width . 'X' . $height . '.png';
		$info = pathinfo ( $picPath );
		if ( !is_dir ( $info[ 'dirname' ] ) ) {
			mkdir ( $info[ 'dirname' ], 0777, 1 );
		}
		if ( file_exists ( $picPath ) ) {
			return $urlpath;
		}
		if ( preg_match ( '/^http:\/\//i', $imgUrl ) )
			$image = $img->CreateThumbnail ( $imgUrl, $width == 'auto' ? FALSE : $width, $height == 'auto' ? FALSE : $height, $picPath );
		else
			$image = $img->CreateThumbnail ( __ROOT__ . $imgUrl, $width == 'auto' ? FALSE : $width, $height == 'auto' ? FALSE : $height, $picPath );

		return $urlpath;
	}
}


/**
 * XML编码
 *
 * @param mixed  $data     数据
 * @param string $root     根节点名
 * @param string $item     数字索引的子节点名
 * @param string $attr     根节点属性
 * @param string $id       数字索引子节点key转换的属性名
 * @param string $encoding 数据编码
 *
 * @return string
 */
function xml_encode ( $data, $root = 'root', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8' )
{
	if ( is_array ( $attr ) ) {
		$_attr = [ ];
		foreach ( $attr as $key => $value ) {
			$_attr[] = "{$key}=\"{$value}\"";
		}
		$attr = implode ( ' ', $_attr );
	}
	$attr = trim ( $attr );
	$attr = empty( $attr ) ? '' : " {$attr}";
	$xml = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
	$xml .= "<{$root}{$attr}>";
	$xml .= data_to_xml ( $data, $item, $id );
	$xml .= "</{$root}>";

	return $xml;
}

/**
 * 数据XML编码
 *
 * @param mixed  $data 数据
 * @param string $item 数字索引时的节点名称
 * @param string $id   数字索引key转换为的属性名
 *
 * @return string
 */
function data_to_xml ( $data, $item = 'item', $id = 'id' )
{
	$xml = $attr = '';
	foreach ( $data as $key => $val ) {
		if ( is_numeric ( $key ) ) {
			$id && $attr = " {$id}=\"{$key}\"";
			$key = $item;
		}
		$xml .= "<{$key}{$attr}>";
		$xml .= ( is_array ( $val ) || is_object ( $val ) ) ? data_to_xml ( $val, $item, $id ) : $val;
		$xml .= "</{$key}>";
	}

	return $xml;
}
