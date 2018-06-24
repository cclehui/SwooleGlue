<?php

namespace SwooleTool;

use SwooleTool\AbstractInterface\Singleton;
use SwooleTool\Component\Di;

class Core {
    use Singleton;

    public function __construct() {
        defined('SWOOLE_VERSION') or define('SWOOLE_VERSION', intval(phpversion('swoole')));
        defined('SWOOLESERVER_ROOT') or define('SWOOLESERVER_ROOT', realpath(getcwd()));

        $this->sysDirectoryInit();
    }

    public function initialize(): Core {
        EasySwooleEvent::frameInitialize();
        $this->errorHandle();
        return $this;
    }

    public function run(): void {
        ServerManager::getInstance()->start();
    }

    private function sysDirectoryInit(): void {
        //创建临时目录    请以绝对路径，不然守护模式运行会有问题
        $tempDir = Config::getInstance()->getConf('TEMP_DIR');
        if (empty($tempDir)) {
            Config::getInstance()->setConf('TEMP_DIR', SWOOLESERVER_ROOT . '/Temp');
            $tempDir = SWOOLESERVER_ROOT . '/Temp';
        }

        $logDir = Config::getInstance()->getConf('LOG_DIR');
        if (empty($logDir)) {
            Config::getInstance()->setConf('LOG_DIR', SWOOLESERVER_ROOT . '/Log');
            $logDir = SWOOLESERVER_ROOT . '/Temp';
        }

        Config::getInstance()->setConf('MAIN_SERVER.SETTING.pid_file', $tempDir . '/pid.pid');
        Config::getInstance()->setConf('MAIN_SERVER.SETTING.log_file', $logDir . '/swoole.log');
    }

    private function errorHandle(): void {
        $conf = Config::getInstance()->getConf("DEBUG");
        if (!$conf) {
            return;
        }
        ini_set("display_errors", "On");
        error_reporting(E_ALL | E_STRICT);
        $userHandler = Di::getInstance()->get(SysConst::ERROR_HANDLER);
        if (!is_callable($userHandler)) {
            $userHandler = function ($errorCode, $description, $file = null, $line = null) {
                Trigger::error($description, $file, $line, $errorCode);
            };
        }
        set_error_handler($userHandler);

        $func = Di::getInstance()->get(SysConst::SHUTDOWN_FUNCTION);
        if (!is_callable($func)) {
            $func = function () use ($conf) {
                $error = error_get_last();
                if (!empty($error)) {
                    Trigger::error($error['message'], $error['file'], $error['line']);
                }
            };
        }
        register_shutdown_function($func);
    }
}