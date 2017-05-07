<?php

namespace GFPHP;


use GFPHP\Model\filesModel;


/**
 * Model基类
 * 所有模型必须继承此类
 * 你也可以仅仅使用此类 或者你牛掰也可以不用 O(∩_∩)O哈！
 * 为了完美的IDE提示，建议在没有模型类的时候，使用new tableModel()实例化模型
 * 创建时间：2014-08-10 07:52 PGF
 * 修改时间：2015-06-18 10:31 PGF            function __Clone(){}
 * 修改时间：2015-04-13 13:45 PGF            function Save(){}
 * 修改时间: 2016-08-01 08:42 PGF            Update PHPDOC
 *
 * @package Model
 *
 *
 * @method $this select(string $field = '')
 * @method $this between(string $field, string $Between)
 * @method $this notBetween(string $field, string $Between)
 * @method $this in(string $field, string $in)
 * @method $this where(string $field, string $comPar = '', string $val = '')
 * @method $this orderBy(string $orderby = '')
 * @method $this limit(string $limit)
 * @method $this orWhere(string $field, string $comPar = '', string $val = '')
 * @method bool|int update(array $update)
 * @method bool|int exec(string $sql = '')
 * @method bool|int count($field = '*')
 * @method bool|int insert(array $insert)
 * @method $this leftJoin($table, $on1, $on2)
 * @method $this rightJoin($table, $on1, $on2)
 * @method $this Join($table, $on1, $on2)
 * @method $this fullJoin($table = '', $on1 = '', $on2 = '')
 * @method $this innerJoin($table = '', $on1 = '', $on2 = '')
 * @method $this union(bool $all = false)
 * @method bool|int delete($table = '', $where = '', $orderby = '', $limit = '')
 * @method $this group($group = '')
 * @method DataObject|bool|array query($sql = '')
 * @method DataObject getOne($field = '*')
 * @method string getField($column, $value)
 * @method string setField($column, $value)
 * @method DataObject paginate($page, $size)
 * @method string get_table($table = FALSE)
 * @method int|bool setInc($column, $num) 字段自增加
 * @method int|bool setDnc($column, $num) 字段自减少
 * @method string compile()
 * @method bool beginTransaction() 开启事务
 * @method bool commit() 结束事务
 * @method bool rollBack() 回滚事务
 * @method string version() 获取MYSQL版本
 * @method string lastSql() 获取最后执行的sql
 */
abstract class Model
{
    /**
     * 默认的主键名
     * @var string
     */
    protected $primary_key = 'id';

    /**
     * @var DBase
     */
    public $db;

    /**
     * Column Example
     *
     * @var array
    protected $Column = array(
     * 'member_name' => array(
     * 'rule'  => 'require',
     * 'ColumnName' => '用户名'
     * ),
     * );
     */
    protected $Column = [];

    protected $model;

    protected $configName = 'default';

    /**
     * 获取配置
     */
    public function getConfig()
    {
        return Config::database($this->configName);
    }

    /**
     * 写法兼容
     * @param string $field
     * @return DataObject
     */
    public function find($field = '*')
    {
        return $this->getOne($field);
    }

    /**
     * 写法兼容
     * @param bool $sql
     * @return DataObject|null|array
     */
    public function findAll($sql = false)
    {
        return $this->query($sql);
    }

    /**
     * 写法兼容
     * @param      $primary_value
     * @param bool $primary_key
     * @return DataObject
     */
    public function findByPk($primary_value,$primary_key=false){
        !$primary_key && $primary_key = $this->primary_key;
        return $this->where($primary_key,$primary_value)->getOne();
    }

    /**
     * 验证规则
     *
     * @var array
     */
    private $validate = [
        #不能为空
        'require' => [
            'rule' => '/.+/',
            'msg' => '%ColumnName%不准为空',
        ],
        #邮箱
        'email' => [
            'rule' => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'msg' => '%ColumnName%不是正确的邮箱地址',
        ],
        #网址
        'url' => [
            'rule' => '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
            'msg' => '您输入的%ColumnName%不正确的网址',
        ],
        #日期格式 2016-06-01
        'date' => [
            'rule' => '^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)$',
            'msg' => '您输入的%ColumnName%格式不正确',
        ],
        #价格
        'currency' => [
            'rule' => '/^\d+(\.\d+)?$/',
            'msg' => '请输入正确格式的%ColumnName%',
        ],
        #数字
        'number' => [
            'rule' => '/^\d+$/',
            'msg' => '请输入正确的%ColumnName%,只能输入数字%',
        ],
        #邮编
        'zip' => [
            'rule' => '/^\d{6}$/',
            'msg' => '请输入正确的%ColumnName%',
        ],
        #整数
        'integer' => [
            'rule' => '/^[-\+]?\d+$/',
            'msg' => '%ColumnName%只能是正整数或者负整数',
        ],
        #浮点数
        'double' => [
            'rule' => '/^[-\+]?\d+(\.\d+)?$/',
            'msg' => '%ColumnName%只能是小数',
        ],
        #英语单词
        'english' => [
            'rule' => '/^[A-Za-z]+$/',
            'msg' => '%ColumnName%只能是英语单词',
        ],
        #验证汉字
        'chinese' => [
            'rule' => '/^[\u4e00-\u9fa5]+$/',
            'msg' => '%ColumnName%只能是汉字',
        ],
        #QQ号码
        'qq' => [
            'rule' => '/^[1-9]\d{4,10}$/',
            'msg' => '请输入正确的%ColumnName%',
        ],
        #验证手机号码
        'mobile' => [
            'rule' => '/^1[34578][0-9]{9}$/',
            'msg' => '请输入正确的%ColumnName%',
        ],
        # 微信号
        'weChat' => [
            'rule' => '/^[a-zA-Z\d_]{6,}$/',
            'msg' => '请输入正确的%ColumnName%',
        ],
        #用户名 常用正则
        'username' => [
            'rule' => '/^[a-zA-Z0-9_]{4,16}$/',
            'msg' => '%ColumnName%只允许输入英文数字下划线4-16位字符',
        ],
        #密码  常用正则   不能包含空白符 并且在4-16
        'password' => [
            'rule' => '/^[^\s]{4,16}$/',
            'msg' => '%ColumnName%不能包含空格，并且4-16个字符',
        ],
        #日期正则 年月日
        'Date' => [
            '/^(?:(?!0000)[0-9]{4}([-/.]?)(?:(?:0?[1-9]|1[0-2])\1(?:0?[1-9]|1[0-9]|2[0-8])|(?:0?[13-9]|1[0-2])\1(?:29|30)|(?:0?[13578]|1[02])\1(?:31))|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)([-/.]?)0?2\2(?:29))$/',
            '%ColumnName%不是正确的日期格式',
        ],
        #时间正则 年月日时分秒
        'DateTime' => [
            '/^(?:(?!0000)[0-9]{4}([-/.]?)(?:(?:0?[1-9]|1[0-2])\1(?:0?[1-9]|1[0-9]|2[0-8])|(?:0?[13-9]|1[0-2])\1(?:29|30)|(?:0?[13578]|1[02])\1(?:31))|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)([-/.]?)0?2\2(?:29))\s+([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/',
            '%ColumnName%不是正确的时间格式',
        ],
        #昵称 常用正则 中文字母数字下划线
        'nickerName' => [
            'rule' => '/^[\x80-\xff_a-zA-Z0-9]{1,16}$/',
            'msg' => '%ColumnName%只允许中英文下划线1-16个字符',
        ],
        #身份证
        'idCard' => [
            'rule' => '/d{15}|d{18}/',
            'msg' => '请输入正确的%ColumnName%',
        ],
    ];


    /**
     * @var array
     */

    /**
     * Model constructor.
     *
     * @param bool $model
     * @param      $configName
     */
    public function __construct($model = FALSE, $configName = FALSE)
    {
        if ($configName == FALSE)
            $configName = $this->configName;
        $this->database($configName);

        if ($model) $this->table($model);

        /**
         * 添加自动上传验证规则
         */
        $this->addCheckRule('file', function ($Column, &$value) {
            $filesModel = new filesModel();
            if (isset($Column['allow_type'])) {
                $allow_type = $Column['allow_type'];
            } else {
                $allow_type = [];
            }
            $value = $filesModel->upload($value, $allow_type);
            $status = isset($value['status']) ? $value['status'] : FALSE;
            $path = isset($value['path']) ? $value['path'] : FALSE;
            $msg = isset($value['msg']) ? $value['msg'] : '';
            if (isset($status)) {
                if ($status == 'false') {
                    return $Column['ColumnName'] . '上传出现错误：' . $msg;
                }
                $value = $path;

                return NULL;
            } else {
                $value = $path;

                return NULL;
            }
        }, NULL);
    }

    /**
     * 验证字段是否合法,如果返回Null则视为合法，否则视为失败
     *
     * @param $data
     *
     * @return String|Null
     */
    final public function checkColumn(&$data)
    {
        foreach ($data as $column => &$value) {
            if (isset($this->Column[$column])) {
                if (is_callable($this->Column[$column]['rule'])) {
                    if ($res = $this->Column[$column]['rule']($this->Column[$column], $value)) {
                        if (isset($this->Column[$column]['msg']) && $this->Column[$column]['msg'] != '') {
                            return str_replace('%ColumnName%', $this->Column[$column]['ColumnName'], isset($this->validate[$this->Column[$column]['rule']]['msg']) ? $this->validate[$this->Column[$column]['rule']]['msg'] : '请输入正确的%ColumnName%');
                        } else {
                            return $res;
                        }
                    }
                } else
                    if (isset($this->validate[$this->Column[$column]['rule']])) {
                        if (is_callable($this->validate[$this->Column[$column]['rule']]['rule'])) {
                            if ($res = $this->validate[$this->Column[$column]['rule']]['rule']($this->Column[$column], $value)) {
                                if (isset($this->validate[$this->Column[$column]['rule']]['msg']) && $this->validate[$this->Column[$column]['rule']]['msg'] != '') {
                                    return str_replace('%ColumnName%', $this->Column[$column]['ColumnName'], isset($this->validate[$this->Column[$column]['rule']]['msg']) ? $this->validate[$this->Column[$column]['rule']]['msg'] : '请输入正确的%ColumnName%');
                                } else {
                                    return $res;
                                }
                            }
                        } else {
                            if (preg_match($this->validate[$this->Column[$column]['rule']]['rule'], $value) !== 1) {
                                return str_replace('%ColumnName%', $this->Column[$column]['ColumnName'], isset($this->validate[$this->Column[$column]['rule']]['msg']) ? $this->validate[$this->Column[$column]['rule']]['msg'] : '请输入正确的%ColumnName%');
                            }
                        }
                    } else {
                        Debug::add('Model: 验证规则' . $this->Column[$column]['rule'] . '未定义。');
                    }
            }
        }

        return NULL;

    }

    /**
     * @param $ruleName
     * @param $rule
     * @param $msg
     */
    final public function addCheckRule($ruleName, $rule, $msg)
    {
        $this->validate[$ruleName] = [
            'rule' => $rule,
            'msg' => $msg,
        ];
    }


    /**
     * database 加载数据库
     *
     * @param $configName
     *
     */
    final private function database($configName)
    {

        //--计算表名
        $tb_name = substr(get_class($this), 6);
        $class = substr($tb_name, (($start = strripos($tb_name, '\\')) > 0 ? $start + 1 : 0));
        $num = strpos($class, 'Model');
        if ($num != 0) {
            $table = substr($class, 0, $num);
        } else {
            $table = substr($this->model, 0, strpos($this->model, 'Model'));
        }
        $db = DB::table($table, $configName);
        $this->db = $db;
    }

    //设置模型操作的表

    /**
     * @param string $table
     *
     * @return $this
     */
    final public function table($table = '')
    {
        $this->db->table = $this->db->config['table_pre'] . $table;
        $this->db->_reset();

        return $this;
    }

    /**
     * 自动保存
     * @param      $data
     * @param bool $primary_key
     * @return bool|int
     */
    public function save($data, $primary_key = false)
    {
        !$primary_key && $primary_key = $this->primary_key;
        return $this->db->save($data, $primary_key);
    }

    /**
     * 执行静态方法
     *
     * @param $func
     * @param $val
     * @return mixed
     */
    final public static function __callStatic($func, $val)
    {
        $DataBase = new static();

        return call_user_func_array([$DataBase, $func], $val);
    }

    /**
     * 不存在的方法将执行DB类中的方法
     *
     * @param $func DBase
     * @param $val  DBase
     *
     * @return bool|DBase|DataObject|array|Model
     */
    final public function __call($func, $val)
    {
        /** @var array $val */
        if (method_exists($this->db, $func)) {
            $res = call_user_func_array([$this->db, $func], $val);
            if (is_object($res)) {
                if (get_class($res) == 'GFPHP\DataObject')
                    return $res;
                $this->db = $res;

                return $this;
            } else
                return $res;
        } else {
            $error = debug_backtrace()[1];
            $error['message'] = get_class($this) . ' 不存在 ' . $func . '方法!';
            new \Exception($error);
        }

        return FALSE;
    }

    /**
     * 防止clone Model出现DB对象还原的情况
     *
     * @return mixed
     */
    final public function __clone()
    {
        return $this->db = clone $this->db;
    }

}

//====================    END Model.class.php      ========================//