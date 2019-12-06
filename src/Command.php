<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/7/0007
 * Time: 下午 10:09
 */

namespace GFPHP;

use GFPHP\Command\ColumnHandler;
use GFPHP\Command\ControllerHandler;
use GFPHP\Command\Handler;
use GFPHP\Command\ModelHandler;
use GFPHP\Command\ModuleHandler;
use GFPHP\Command\RouterHandler;
use League\CLImate\CLImate;

/**
 * Class Console
 * @package GFPHP
 */
class Command
{
    protected $name = "
 ________          ________          ___               ___     
|\   ____\        |\   ____\        |\  \             |\  \    
\ \  \___|        \ \  \___|        \ \  \            \ \  \   
 \ \  \  ___       \ \  \            \ \  \            \ \  \  
  \ \  \|\  \       \ \  \____        \ \  \____        \ \  \ 
   \ \_______\       \ \_______\       \ \_______\       \ \__\
    \|_______|        \|_______|        \|_______|        \|__|
                                          
= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
";
    protected $stdout;
    protected $stdin;
    protected $stderr;
    protected $argv;
    /**
     * @var CLImate
     */
    protected $cli;
    public $Handler = [];

    /**
     * Command constructor.
     */
    public function __construct()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        date_default_timezone_set("PRC");
        $this->stdout = fopen('php://stdout', 'w');
        $this->stdin = fopen('php://stdin', 'r');
        $this->stderr = fopen('php://stderr', 'w');
        $this->cli = new CLImate();
        array_shift($_SERVER['argv']);
        $this->argv = $_SERVER['argv'];
        $this->addHandler(new ModuleHandler($this));
        $this->addHandler(new ModelHandler($this));
        $this->addHandler(new ColumnHandler($this));
        $this->addHandler(new ControllerHandler($this));
        $this->addHandler(new RouterHandler($this));
        $Handlers = Config::command('CommandHandlers');

        /**
         * 将配置中的handle导入
         */
        foreach ($Handlers as $handle) {
            $this->addHandler(new $handle($this));
        }
    }

    /**
     * @param Handler $handler
     */
    public function addHandler(Handler $handler)
    {
        $this->Handler[$handler->name] = $handler;
    }

    /**
     * 执行
     */
    public function execute()
    {
        if (!$this->argv) {
            $this->help();
        } else {
            if ($this->argv[0] == '') {
                $this->argv[0] = $this->getStdin("请输入正确的Handler名称: [" . implode(',', array_keys($this->Handler)) . ']')[0];
                $this->execute();
            } else {
                if (isset($this->Handler[$this->argv[0]])) {
                    $argv = $this->argv;
                    $handle_name = array_shift($argv);
                    $this->Handler[$handle_name]->handler($argv);
                } else {
                    $this->argv[0] = $this->getStdin("请输入正确的Handler名称: [" . implode(',', array_keys($this->Handler)) . ']')[0];
                    $this->execute();
                }
            }
        }
    }

    /**
     * 打印基础的使用方法
     */
    public function help()
    {
        $this->writeln($this->name);
        $this->argv = $this->getStdin("请输入正确的Handler名称: [" . implode(',', array_keys($this->Handler)) . ']');
        $this->execute();
    }

    /**
     * 输出一行
     * @param string $message 输出的消息
     */
    public function writeln($message)
    {
        $this->write($message . "\r\n");
    }

    /**
     * 输出内容
     * @param string $message 输出的消息
     */
    public function write($message)
    {
        if (!is_string($message))
            $message = var_export($message, true);
//        if (strtoupper(substr(PHP_OS,0,3))==='WIN') {
//            if (mb_detect_encoding($message, 'UTF-8', true))
//                $message = mb_convert_encoding($message, "GBK", "UTF-8");
//        }
        fwrite($this->stdout, $message);
    }

    /**
     * 读取一行内容
     * @param string $notice
     * @return bool|string
     */
    public function getStdin($notice = '')
    {
        $this->write($notice);
        return explode(' ', str_replace(["\r\n", "\n"], '', fgets($this->stdin)));
    }

}