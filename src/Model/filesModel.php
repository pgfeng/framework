<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2016/6/2
 * Time: 19:17
 */

namespace GFPHP\Model;

use Dflydev\ApacheMimeTypes\JsonRepository;
use GFPHP\Config;
use GFPHP\Model;
use Dflydev\ApacheMimeTypes\PhpRepository;

/**
 * 数据库结构
 * DROP TABLE IF EXISTS `{$table_pre}files`;
 * CREATE TABLE `{$table_pre}_files` (
 * `file_id` int(11) NOT NULL AUTO_INCREMENT,
 * `file_name` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名称',
 * `file_size` bigint(20) DEFAULT NULL COMMENT '文件大小',
 * `file_ext` varchar(15) DEFAULT NULL COMMENT '文件后缀名',
 * `file_type` varchar(100) DEFAULT NULL COMMENT '文件类型',
 * `file_md5` varchar(32) DEFAULT NULL COMMENT '文件md5值',
 * `file_time` int(10) DEFAULT NULL COMMENT '创建时间（上传时间）',
 * `file_path` varchar(512) DEFAULT NULL COMMENT '文件路径',
 * PRIMARY KEY (`file_id`),
 * UNIQUE KEY `file_md5` (`file_md5`)
 * ) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
 */

/**
 *
 * 零操作文件上传模型
 * Class filesModel
 * @package Model
 */
class filesModel extends Model
{

    /**
     * 删除文件
     * @param $file_id
     * @return boolean
     */
    public function del($file_id)
    {
        $file = $this->Where('file_id', $file_id)->getOne();
        if ($file) {
            if (unlink('./' . $file['file_path'])) {
                return $this->Where('file_id', $file_id)->delete();
            } else {
                return FALSE;
            }
        } else {
            return TRUE;
        }
    }

    /**
     *
     */
    public function base64_upload($base64_data, $allow_type = [])
    {
        //--如果没有设置允许格式,将使用默认允许的格式
        $allow_type = !empty($allow_type) ? $allow_type : Config::file('allow_type');
        foreach ($allow_type as &$type) {
            $type = strtolower($type);
        }
        preg_match('/data:(.*);/iUs', $base64_data, $type);
        $mime = $type[1];
        $repository = new JsonRepository();
        $extensions = $repository->findExtensions($mime);
        $data = base64_decode(explode('base64,',$base64_data)[1]);
        $ext = $extensions[0];
        if (in_array(strtolower($ext), $allow_type)) {
            $md5 = md5($data);
            if ($rfile = $this->getFileByMd5($md5)) {
                return [
                    'status' => 'true',
                    'path' => $rfile['file_path'],
                    'msg' => '上传成功！！',
                ];
            } else {
                $path = Config::file('upload_path') . date("Ymd") . '/' . time() . random(10) . '.' . $ext;
                $full_path = $path;
                mkPathDir($full_path);
                if (file_put_contents($full_path, $data)) {
                    $this->Insert([
                        'file_name' => time(),
                        'file_size' => strlen($data),
                        'file_ext' => $ext,
                        'file_type' => $mime,
                        'file_md5' => $md5,
                        'file_time' => time(),
                        'file_path' => $path,
                    ]);
                    return [
                        'status' => 'true',
                        'path' => $path,
                        'msg' => '上传成功',
                    ];
                }
            }
        } else {
            return [
                'status' => 'false',
                'msg' => '只允许上传' . implode('|', $allow_type) . '格式！',
            ];
        }
    }

    /**
     * 上传文件,返回文件保存路径,如果是数组形式的，那么返回的也是数组形式的，结构和单文件相同
     * @param $field_file
     * @param array $allow_type
     * @return array
     */
    public function upload($field_file, $allow_type = [])
    {
        //--如果没有设置允许格式,将使用默认允许的格式
        $allow_type = !empty($allow_type) ? $allow_type : Config::file('allow_type');
        foreach ($allow_type as &$type) {
            $type = strtolower($type);
        }
        $file = $field_file;
        if (!isset($file['error'])) {
            return [
                'status' => 'false',
                'msg' => '传入参数有误,请检查代码!',
            ];
        }
        if (is_array($file['error'])) {
            $files = [];
            foreach ($file['error'] as $key => $error) {
                $f = [];
                if ($error == 0 && $file['size'][$key] != 0) {
                    $md5 = md5_file($file['tmp_name'][$key]);
                    if ($rfile = $this->getFileByMd5($md5)) {
                        $f = [
                            'status' => 'true',
                            'path' => $rfile['file_path'],
                            'msg' => '上传成功！！',
                        ];
                    } else {
                        $allow_size = Config::file('allow_size');
                        if ($allow_size < $file['size'][$key]) {
                            $f = [
                                'status' => 'false',
                                'msg' => '文件超过系统所允许的大小！',
                            ];
                        } else {
                            $ext = explode('.', $file['name'][$key]);
                            $ext = end($ext);
                            if (in_array(strtolower($ext), $allow_type)) {
                                $path = Config::file('upload_path') . date("Ymd") . '/' . time() . random(10) . '.' . $ext;
                                $full_path = $path;
                                mkPathDir($full_path);
                                if (@move_uploaded_file($file['tmp_name'][$key], $full_path)) {
                                    $this->Insert([
                                        'file_name' => $file['name'][$key],
                                        'file_size' => $file['size'][$key],
                                        'file_ext' => $ext,
                                        'file_type' => $file['type'][$key],
                                        'file_md5' => $md5,
                                        'file_time' => time(),
                                        'file_path' => $path,
                                    ]);
                                    $f = [
                                        'status' => 'true',
                                        'path' => $path,
                                        'msg' => '上传成功',
                                    ];
                                }
                            } else {

                                $f = [
                                    'status' => 'false',
                                    'msg' => '只允许上传' . implode('|', $allow_type) . '格式！',
                                ];
                            }
                        }
                    }
                } else {
                    $f = [
                        'status' => 'false',
                        'msg' => $this->getErrorMsg($error),
                    ];
                }
                $files[] = $f;
            }

            return $files;
        } else {
            if ($file['error'] == 0 && $file['size'] != 0) {
                $md5 = md5_file($file['tmp_name']);
                if ($rfile = $this->getFileByMd5($md5)) {
                    return [
                        'status' => 'true',
                        'path' => $rfile['file_path'],
                        'msg' => '上传成功！！',
                    ];
                } else {
                    $allow_size = Config::file('allow_size');
                    if ($allow_size < $file['size']) {
                        return [
                            'status' => 'false',
                            'msg' => '超过系统所允许的大小！',
                        ];
                    }
                    $ext = explode('.', $file['name']);
                    $ext = end($ext);
                    if (in_array(strtolower($ext), $allow_type)) {
                        $path = Config::file('upload_path') . date("Ymd") . '/' . time() . random(10) . '.' . $ext;
                        $full_path = $path;
                        mkPathDir($full_path);
                        if (@move_uploaded_file($file['tmp_name'], $full_path)) {
                            $this->Insert([
                                'file_name' => $file['name'],
                                'file_size' => $file['size'],
                                'file_ext' => $ext,
                                'file_type' => $file['type'],
                                'file_md5' => $md5,
                                'file_time' => time(),
                                'file_path' => $path,
                            ]);

                            return [
                                'status' => 'true',
                                'path' => $path,
                                'msg' => '上传成功',
                            ];
                        }
                    } else {
                        return [
                            'status' => 'false',
                            'msg' => '只允许上传' . implode('|', $allow_type) . '格式！',
                        ];
                    }
                }
            } else {
                return [
                    'status' => 'false',
                    'msg' => $this->getErrorMsg($file['error']),
                ];
            }
        }

        return [
            'status' => 'false',
            'msg' => '异常错误！！！',
        ];
    }

    /**
     * 根据错误码获取错误
     * @param $error_code
     * @return string
     */
    public function getErrorMsg($error_code)
    {
        switch ($error_code) {
            case NULL:
                $error_msg = '没有上传！';
                break;
            case 1:
                $error_msg = '超过了配置中限制的值！';
                break;
            case 2:
                $error_msg = '超过了表单中限制的值！';
                break;
            case 3:
                $error_msg = '没有完整上传！';
                break;
            case 4:
                $error_msg = '没有文件上传！';
                break;
            case 5:
                $error_msg = '找不到临时文件夹！';
                break;
            case 6:
                $error_msg = '临时文件写入失败！';
                break;
            default:
                $error_msg = '出现未知错误！';

        }

        return $error_msg;
    }

    /**
     * 用MD5值获取文件
     * @param $md5
     * @param bool $all
     * @return array|bool
     */
    public function getFileByMd5($md5, $all = FALSE)
    {
        $this->Where('file_md5', $md5);
        if ($all)
            return $this->getOne();
        else
            return $this->getOne('file_path');
    }

    /**
     * @param $file_id
     * @param bool $all
     * @return array|bool
     */
    public function getFileById($file_id, $all = FALSE)
    {
        $this->Where('file_id', $file_id);
        if ($all)
            return $this->getOne();
        else
            return $this->getOne('file_path');
    }
}