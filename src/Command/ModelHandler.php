<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/7/0008
 * Time: 上午 11:49
 */

namespace GFPHP\Command;

use GFPHP\Config;
use GFPHP\DB;


/**
 * Class ModelHandler
 * @package GFPHP\Command
 */
class ModelHandler extends Handler
{
    public $name = 'model';

    /**
     * 判断表是否存在
     */
    private function tableExists($table, $config_name)
    {
        echo $table;
        echo $config_name;
        if (!DB::table('', $config_name)->query('show tables like "' . $table . '"')) {
            $this->command->writeln("数据库不存在此表!");
            return false;
        } else {
            return true;
        }
    }

    /**
     * 处理
     * @param $argv
     * @return mixed|void
     */
    public function handler($argv)
    {
        $this->argv = $argv;
        if (!$argv || $argv[0] == '') {
            $this->help();
        } else {
            if (!isset($argv[0]) || $argv[0] == '') {
                $argv[0] = $this->command->getStdin("请输入表名[表名]:")[0];
                $this->handler($argv);
                return;
            }

            if (!isset($argv[1])) {
                $argv[1] = $this->command->getStdin("请输入模型目录(根目录 " . Config::command("ModelDir") . "):")[0];
                $this->handler($argv);
                return;
            } else {
                return $this->buildModel($this->getNameSpace($argv[1]), $this->getDir($argv[1]), $this->getModelPath($argv[0], $argv[1]));
            }
        }
        return;
    }

    /**
     * 生成模型
     * @param $nameSpace
     * @param $modelDir
     * @param $modelPath
     */
    protected function buildModel($nameSpace, $modelDir, $modelPath)
    {
        $to_build = true;
        if (file_exists($modelPath)) {
            $res = $this->command->getStdin('模型已经存在是否重新生成,字段都会重复生成[yes or no]:')[0];
            if (!preg_match('/yes/i', $res)) {
                $to_build = false;
            }else{
                $to_build = true;
            }
        }
        if ($to_build) {
            $config = $this->choseConfig();
            if (!$this->tableExists(Config::database($config)['table_pre'] . $this->argv[0], $config)) {
                $this->handler([]);
            }
            $column_class = $this->command->Handler['column']->handler([$config, $this->argv[0]]);
            $fields = DB::table($this->argv[0], $config)->query('show keys from `' . Config::database($config)['table_pre'] . $this->argv[0] . '` where key_name="PRIMARY"');
            $primary_key = '';
            if ($fields) {
                $primary_key = $fields[0]['Column_name'];
            }
            $date = date('Y-m-d H:i:s', time());
            $baseModel = $this->argv[3]?$this->argv[3]:'GFPHP\Model';
            $modelContent = <<<MODEL
<?php
/**
 * Created by GFPHP-GCLI.
 * Time: $date
 */

namespace $nameSpace;
use $baseModel;

/**
 * Class {$this->argv[0]}Model
 * @package Model
 */
class {$this->argv[0]}Model extends Model
{
    /**
     * @var string primary_key
     */
    public \$primary_key = '$primary_key';
    protected \$Column = [];
    
    /**
     * {$this->argv[0]}Model constructor.
     */
    public function __construct()
    {
        parent::__construct(false, '$config');
    }
    
}
MODEL;
            mkPathDir($modelPath);
            file_put_contents($modelPath, $modelContent);
            $this->command->writeln($nameSpace . '\\' . $this->argv[0] . 'Model生成成功!');
            return $column_class;
        } else {
            $column_class = $this->command->Handler['column']->handler([$config, $this->argv[0]]);
            $this->command->writeln("已取消生成{$modelPath}!");
            return $column_class;
        }
    }

    /**
     * @param string $msg
     * @return string
     */
    protected function choseConfig($msg = '请输入配置名称[默认default]:')
    {
        if (!isset($this->argv[2]) || !$config = $this->argv[2]) {
            $config = $this->command->getStdin($msg)[0];
            if (!$config) {
                $config = 'default';
            }
        } else {
            $config = 'default';
        }
        if (!Config::database($config)) {
            unset($this->argv[2]);
            return $this->choseConfig('请输入正确的配置名称'.implode(',',array_keys(Config::database())).':');
        } else {
            return $config;
        }
    }

    /**
     * @param string $dir_name
     * @return string
     */
    protected function getNameSpace($dir_name = '')
    {
        if (!$dir_name) {
            return Config::command('BaseModelNameSpace');
        } else {
            $nameSpace = Config::command('BaseModelNameSpace');
            $d = explode('\\', str_replace('/', '\\', $dir_name));
            foreach ($d as $dd) {
                $nameSpace .= '\\' . $dd;
            }
            return $nameSpace;
        }
    }

    /**
     * @param string $dir_name
     * @return mixed
     */
    protected function getDir($dir_name = '')
    {
        if (!$dir_name) {
            return BASE_PATH . Config::command('ModelDir') . DIRECTORY_SEPARATOR;
        } else {
            $dir = Config::command('ModelDir') . DIRECTORY_SEPARATOR;
            $d = explode('/', str_replace('\\', '/', $dir_name));
            foreach ($d as $dd) {
                $dir .= $dd . DIRECTORY_SEPARATOR;
            }
            return BASE_PATH . $dir;
        }
    }

    /**
     * @param $table
     * @param string $dir_name
     * @return string
     */
    protected function getModelPath($table, $dir_name = '')
    {
        return $this->getDir($dir_name) . $table . 'Model.php';
    }

    /**
     * @return mixed
     */
    public function help()
    {
        $this->argv = $this->command->getStdin('请输入表名 :');
        $this->handler($this->argv);
        return true;
    }
}