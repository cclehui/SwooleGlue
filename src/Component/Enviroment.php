<?php
namespace SwooleGlue\Component;

//server 相关的一些环境处理
use SwooleGlue\Component\Config\ConfigUtil;

class Enviroment {

    public static function init() {
        defined('SWOOLE_VERSION') or define('SWOOLE_VERSION', intval(phpversion('swoole')));
        defined('SWOOLESERVER_ROOT') or define('SWOOLESERVER_ROOT', realpath(getcwd()));

        self::systemDirInit();

        self::setErrorHandler();

        self::registerShutdownFunc();

        //入口文件检测， 绝对路径
        $root_index_file = ConfigUtil::getInstance()->getConf(SysConst::ROOT_INDEX_FILE);
        if (!is_file($root_index_file)) {
            die("root index file: $root_index_file not exists\n");
        }


        //入口处理类
        /*
        $http_handler = ConfigUtil::getInstance()->getConf(SysConst::HTTP_HANDLER);
        if (!$http_handler || !class_exists($http_handler)) {
            die("HTTP HANDLER not exists");
        }

        $http_handler_obj = new $http_handler();
        if (!$http_handler_obj instanceof \SwooleGlue\Component\Swoole\Http\HttpHandler) {
            die("HTTP HANDLER not instanceof \SwooleGlue\Component\Swoole\Http\HttpHandler");
        }

        Di::getInstance()->set($http_handler, $http_handler_obj);
        */

    }

    protected static function systemDirInit(): void {
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

        $pid_file = $temp_dir . DIRECTORY_SEPARATOR;
        $pid_file .= ConfigUtil::getInstance()->getConf('SWOOLE_PID_FILE') ? : 'pid.pid';
        ConfigUtil::getInstance()->setConf('MAIN_SERVER.SETTING.pid_file', $pid_file);

        $log_file = $log_dir . DIRECTORY_SEPARATOR;
        $log_file .= ConfigUtil::getInstance()->getConf('SWOOLE_LOG_FILE') ? : 'swoole.log';

        ConfigUtil::getInstance()->setConf('MAIN_SERVER.SETTING.log_file', $log_file);
    }

    protected static function setErrorHandler(): void {
        $userHandler = Di::getInstance()->get(SysConst::ERROR_HANDLER);
        if (!is_callable($userHandler)) {
            $userHandler = function ($errorCode, $description, $file = null, $line = null) {
                $logStr = ErrorUtil::getErrorLevelStr($errorCode) . ": $description at {$file} line {$line}";

                Logger::getInstance()->error($logStr, debug_backtrace());
            };
        }

        set_error_handler($userHandler);
    }

    protected static function registerShutdownFunc(): void {
        $func = Di::getInstance()->get(SysConst::SHUTDOWN_FUNCTION);
        if (!is_callable($func)) {
            $func = function () {
                $error = error_get_last();
                if ($error) {
                    Logger::getInstance()->error("shutdown error", $error);
                }
            };
        }

        register_shutdown_function($func);
    }



}