<?php

namespace SwooleTool\Component;

use SwooleTool\AbstractInterface\TriggerInterface;

class Trigger {



    public static function error($msg, $file = null, $line = null, $errorCode = E_USER_ERROR) {
        if ($file == null) {
            $bt = debug_backtrace();
            $caller = array_shift($bt);
            $file = $caller['file'];
            $line = $caller['line'];
        }
        $func = Di::getInstance()->get(SysConst::TRIGGER_HANDLER);
        if ($func instanceof TriggerInterface) {
            $func::error($msg, $file, $line, $errorCode);
        } else {
            $logStr = self::getErrorLevelStr($errorCode) . " at file[{$file}] line[{$line}] message:[{$msg}]";

            Logger::getInstance()->log($logStr, 'debug');
            Logger::getInstance()->console($logStr, false);
        }
    }

    public static function throwable(\Throwable $throwable) {
        $func = Di::getInstance()->get(SysConst::TRIGGER_HANDLER);
        if ($func instanceof TriggerInterface) {
            $func::throwable($throwable);
        } else {
            $logStr = "Exception at file[{$throwable->getFile()}] line[{$throwable->getLine()}] message:[{$throwable->getMessage()}]";

            Logger::getInstance()->log($logStr, 'debug');
            Logger::getInstance()->console($logStr, false);
        }
    }

    protected static function getErrorLevelStr($errorCode) {

        switch ($errorCode) {
            case E_USER_ERROR:
                return 'USER ERROR';

            case E_USER_WARNING:
                return 'USER WARNING';

            case E_USER_NOTICE:
                return 'USER NOTICE';

            default:
                return 'Unknown error';
        }
    }

}