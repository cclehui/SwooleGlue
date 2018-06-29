<?php

namespace SwooleGlue\Component;

use Monolog\Handler\RotatingFileHandler;
use SwooleGlue\AbstractInterface\LoggerWriterInterface;
use SwooleGlue\AbstractInterface\Singleton;
use SwooleGlue\Component\Config\ConfigUtil;

class Logger extends \Monolog\Logger {
    use Singleton;

    function __construct() {
        $logDir = ConfigUtil::getInstance()->getConf('LOG_DIR');

        parent::__construct("SWOOLE_SERVER_LOGGER");
        $logLevel = ConfigUtil::getInstance()->getConf('LOG_LEVEL');
        $this->pushHandler(new RotatingFileHandler($logDir . "/debug.log", $logLevel));
    }

    private function debugInfo() {
        $trace = debug_backtrace();
        $file = $trace[1]['file'];
        $line = $trace[1]['line'];
        $func = isset($trace[2]['function']) ? $trace[2]['function'] : 'unKnown';
        return ['file' => $file, 'line' => $line, 'function' => $func];
    }
}