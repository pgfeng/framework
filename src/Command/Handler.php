<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/7/0008
 * Time: 上午 11:32
 */

namespace GFPHP\Command;


use GFPHP\Command;

/**
 * Class Handler
 * @package GFPHP\Command
 */
abstract class Handler
{
    protected $command;
    protected $argv = [];

    /**
     * Handler constructor.
     * @param Command $command
     * @param array $argv
     */
    public function __construct(Command $command,$argv = [])
    {
        $this->command = $command;
    }

    /**
     * Handler名称
     * @var string
     */
    public $name = '';
    /**
     * @param $argv
     * @return mixed
     */
    abstract public function handler($argv);

    /**
     * @return mixed
     */
    abstract public function help();

}