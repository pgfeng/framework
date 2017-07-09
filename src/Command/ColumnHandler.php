<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/7/0008
 * Time: 下午 10:42
 */

namespace GFPHP\Command;

use GFPHP\Config;
use GFPHP\DB;


/**
 * Class ColumnHandler
 * @package GFPHP\Command
 */
class ColumnHandler extends Handler
{
    /**
     * handler名称
     * @var string
     */
    public $name = 'column';

    /**
     * 判断表是否存在
     */
    private function tableExists($table)
    {
        if (!DB::table('')->query('show tables like "' . $table . '"')) {
            $this->command->writeln("数据表{$table}不存在!");
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return mixed
     */
    function help()
    {
        // TODO: Implement help() method.
        return;
    }

    /**
     * @param $argv
     * @return mixed
     */
    function handler($argv)
    {
        $this->argv = $argv;
        if (!$argv || !isset($argv[0]) || $argv[0] == '') {
            $res = $this->command->getStdin("未输入更新的数据库配置是否要更新全部数据库字段[yes or no]:[默认yes]")[0];
            if (preg_match('/no/i', $res)) {
                $this->argv[0] = $config = $this->choseConfig();
            }
        } else {
            if (!Config::database($this->argv[0])) {
                $this->argv[0] = $config = $this->choseConfig();
            }
        }
        if ((!isset($argv[1]) || $argv[1] == 1) && (isset($this->argv[0]) && $this->argv[0] != '')) {
            $res = $this->command->getStdin("未输入表名,是否{$this->argv[0]}全部数据表[yes or no]:[默认yes]")[0];
            if (preg_match('/no/i', $res)) {
                $this->argv[1] = $this->choseTable($this->argv[0]);
            }
        }
        if (isset($argv[1])) {
            if (!$this->tableExists(Config::database($this->argv[0])['table_pre'] . $this->argv[1])) {
                $this->argv[1] = $this->choseTable($this->argv[0]);
            }
        }
        $this->buildColumn();
        return;
    }

    /**
     * 选择表
     * @param $config
     * @return
     */
    public function choseTable($config)
    {
        $table = $this->command->getStdin("请输入表名:")[0];
        if (!$table || !$this->tableExists(Config::database($config)['table_pre'] . $table)) {
            return $this->choseTable($config);
        } else {
            return $table;
        }
    }

    /**
     * @param $config
     * @return string
     */
    public function getNameSpace($config)
    {
        return Config::command('BaseColumnNameSpace') . '_' . $config;
    }

    /**
     * @param $config
     * @param $table
     * @return string
     */
    public function getColumnPath($config, $table)
    {
        return BASE_PATH . Config::command('ColumnDir') . '_' . $config . DIRECTORY_SEPARATOR . $table . '.php';
    }

    /**
     * 生成字段
     */
    private function buildColumn()
    {
        if (!$this->argv) {
            $database_configs = array_keys(Config::database());
            foreach ($database_configs as $config) {
                $this->buildDatabaseColumn($config, $this->getNameSpace($config));
            }
        } elseif (isset($this->argv[0]) && !isset($this->argv[1])) {
            $this->buildDatabaseColumn($this->argv[0], $this->getNameSpace($this->argv[0]));
        } elseif (isset($this->argv[0]) && isset($this->argv[1])) {
            $this->buildTableColumn($this->argv[1], $this->argv[0], $this->getNameSpace($this->argv[0]), $this->getColumnPath($this->argv[0], $this->argv[1]));
        }
    }

    /**
     * @param $config
     * @param $nameSpace
     */
    private function buildDatabaseColumn($config, $nameSpace)
    {
        $tables = DB::table('', $config)->query("show tables");
        foreach ($tables as $table) {
            $table = $table[array_keys($table->toArray())[0]];
            if (strpos($table, Config::database($config)['table_pre']) === 0) {
                $table = substr($table, strlen(Config::database($config)['table_pre']));
                $this->buildTableColumn($table, $config, $this->getNameSpace($config), $this->getColumnPath($config, $table));
            }
        }
        $this->command->writeln($config . "数据库表字段更新完成");
    }

    /**
     * @param $config
     * @param $nameSpace
     * @param $columnPath
     */
    private function buildTableColumn($table, $config, $nameSpace, $columnPath)
    {
        $columns = DB::table('', $config)->query('desc ' . Config::database($config)['table_pre'] . $table);
        $const = '';
        foreach ($columns as $column) {
            $const .= '
    /*';
            $const .= $column;
            $const .= '*/
    const ' . strtoupper($column['Field']) . ' = \'' . $column['Field'] . '\';' . "\r\n";
        }
        $date = date('Y-m-d H:i:s');
        $ColumnContent = <<<Column
<?php
/**
 * Created by GFPHP-GCLI.
 * Time: $date
 */
 
namespace $nameSpace;

/**
 * Class {$table}
 * @package Model
 */
class $table{

    /* table name */
    const table_name='$table';
$const
}
Column;
        mkPathDir($columnPath);
        file_put_contents($columnPath, $ColumnContent);
        $this->command->writeln($nameSpace . '\\' . $table . 'Column生成成功!');
    }

    /**
     * @param string $msg
     * @return string
     */
    protected function choseConfig($msg = '请输入配置名称[默认default]:')
    {
        $config = $this->command->getStdin($msg)[0];
        if (!$config) {
            $config = 'default';
        }
        if (!Config::database($config)) {
            return $this->choseConfig('请输入正确的配置名称:');
        } else {
            return $config;
        }
    }

}