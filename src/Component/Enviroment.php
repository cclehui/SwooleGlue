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
//        $root_index_file = ConfigUtil::getInstance()->getConf(SysConst::ROOT_INDEX_FILE);
//        if (!is_file($root_index_file)) {
//            die("root index file: $root_index_file not exists\n");
//        }


        //入口处理类, 绝对路径
        $pserverlet = ConfigUtil::getInstance()->getConf(SysConst::PSERVERLET);
        if (!$pserverlet) {
            die("Config PSERVERLET is empty");
        }

        require_once SWOOLESERVER_ROOT .DIRECTORY_SEPARATOR. $pserverlet . '.php';
        if (!class_exists($pserverlet)) {
            die("Config PSERVERLET class $pserverlet not exists");
        }

        $pserverletInstance = $pserverlet::getInstance();
        if (!($pserverletInstance instanceof PServerlet)) {
            die("Config PSERVERLET $pserverlet is not instanceof PServerlet");
        }

        Di::getInstance()->set(SysConst::PSERVERLET, $pserverletInstance);
    }

    protected static function systemDirInit() {

        $logDir = ConfigUtil::getInstance()->getConf('LOG_DIR');
        if (!$logDir) {
            $logDir = SWOOLESERVER_ROOT . '/Log';
            ConfigUtil::getInstance()->setConf('LOG_DIR', $logDir);
        }

        if (!is_dir($logDir) && !mkdir($logDir)) {
            throw new \Exception("log directory create fail:$logDir");
        }

        $pid_file = $logDir . DIRECTORY_SEPARATOR;
        $pid_file .= ConfigUtil::getInstance()->getConf('SWOOLE_PID_FILE') ? : 'pid.pid';
        ConfigUtil::getInstance()->setConf('MAIN_SERVER.SETTING.pid_file', $pid_file);

        $log_file = $logDir . DIRECTORY_SEPARATOR;
        $log_file .= ConfigUtil::getInstance()->getConf('SWOOLE_LOG_FILE') ? : 'swoole.log';

        ConfigUtil::getInstance()->setConf('MAIN_SERVER.SETTING.log_file', $log_file);
    }

    protected static function setErrorHandler() {
        $userHandler = Di::getInstance()->get(SysConst::ERROR_HANDLER);
        if (!is_callable($userHandler)) {
            $userHandler = function ($errorCode, $description, $file = null, $line = null) {
                $logStr = ErrorUtil::getErrorLevelStr($errorCode) . ": $description at {$file} line {$line}";

                Logger::getInstance()->error($logStr, debug_backtrace());
            };
        }

        set_error_handler($userHandler, E_ERROR);
    }

    protected static function registerShutdownFunc() {
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