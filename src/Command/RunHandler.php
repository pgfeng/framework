<?php


namespace GFPHP\Command;


use Whoops\Exception\ErrorException;

class RunHandler extends Handler
{
    public $name = 'run';

    function handler($argv = [])
    {
        if (!isset($argv[0])) {
            $input = $this->command->getStdin('请输入端口【默认 8899】：');
            if (!$input[0]) {
                $port = 8899;
            } else {
                if (is_numeric($input[0]) && (int)$input[0] > 0) {
                    $port = (int)$input[0];
                } else {
                    $this->command->cli->red('端口号必须为正整数！');
                    return $this->handler();
                }
            }
        } else {
            if (is_numeric($argv[0]) && (int)$argv[0] > 0) {
                $port = (int)$argv[0];
            } else {
                $this->command->cli->red('端口号必须为正整数！');
                return $this->handler();
            }
        }
        $dir = 'Public';
        if (!$this->checkPortBindable($port)) {
            $this->command->cli->red($port . '端口已被占用！');
            return $this->handler([]);
        }
        if (!isset($argv[1])) {
            $input = $this->command->getStdin('请输入运行目录【默认 Public】：');
            if ($input[0]) {
                $dir = $input[0];
            }
        } else {
            $dir = $argv[1];
        }
        if (!is_dir($dir)) {
            $this->command->cli->red('运行目录【' . $dir . '】不存在！');
            return $this->handler([
                $port,
            ]);
        }

        passthru('clear');
        $router_path = __DIR__ . DIRECTORY_SEPARATOR . 'cliRouter.php';
        $this->command->cli->table([
                [
                    '测试网址' => 'http://127.0.0.1:' . $port,
                    '绑定IP' => '0.0.0.0',
                    '绑定端口' => $port,
                    '绑定IP' => '0.0.0.0',
                    '运行目录' => BASE_PATH . $dir,
                ]
            ]
        );
        passthru('cd ./Public/ && php -S 0.0.0.0:' . $port . ' -t ./ ' . $router_path);
        return true;
    }

    /**
     * 检查端口是否可以被绑定
     * @author flynetcn
     */
    function checkPortBindable($port, $host = '0.0.0.0', &$errno = null, &$errstr = null)
    {
        try {

            $socket = @stream_socket_server("tcp://$host:$port", $errno, $errstr);
            if (!$socket) {
                return false;
            }
            fclose($socket);
            unset($socket);
        } catch (ErrorException $e) {

        }
        return true;
    }

    /**
     * @return mixed
     */
    function help()
    {
        // TODO: Implement help() method.

        $this->command->cli->dump('c');
        return;
    }
}