<?php
namespace SwooleTool\Component;

//server 相关的一些环境处理
class Enviroment {

    public static function init() {
        defined('SWOOLE_VERSION') or define('SWOOLE_VERSION', intval(phpversion('swoole')));
        defined('SWOOLESERVER_ROOT') or define('SWOOLESERVER_ROOT', realpath(getcwd()));

        self::systemDirInit();

        self::setErrorHandler();

        //入口文件配置检测
    }

    public static function systemDirInit(): void {
        //创建临时目录    请以绝对路径，不然守护模式运行会有问题
        $temp_dir = ConfigUtil::getInstance()->getConf('TEMP_DIR');
        if (empty($temp_dir)) {
            ConfigUtil::getInstance()->setConf('TEMP_DIR', SWOOLESERVER_ROOT . '/Temp');
            $temp_dir = SWOOLESERVER_ROOT . '/Temp';
        }

        if (!is_dir($temp_dir) && !mkdir($temp_dir)) {
            throw new \Exception("temp directory create fail:$temp_dir");
        }

        $log_dir = ConfigUtil::getInstance()->getConf('LOG_DIR');
        if (empty($log_dir)) {
            ConfigUtil::getInstance()->setConf('LOG_DIR', SWOOLESERVER_ROOT . '/Log');
            $log_dir = SWOOLESERVER_ROOT . '/Temp';
        }

        if (!is_dir($log_dir) && !mkdir($log_dir)) {
            throw new \Exception("log directory create fail:$log_dir");
        }


        ConfigUtil::getInstance()->setConf('MAIN_SERVER.SETTING.pid_file', $temp_dir . '/pid.pid');
        ConfigUtil::getInstance()->setConf('MAIN_SERVER.SETTING.log_file', $log_dir . '/swoole.log');
    }

    public static function setErrorHandler(): void {
        $conf = ConfigUtil::getInstance()->getConf("DEBUG");
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