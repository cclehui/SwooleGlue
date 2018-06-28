<?php

namespace SwooleTool\Component;

use Monolog\Handler\RotatingFileHandler;
use SwooleTool\AbstractInterface\LoggerWriterInterface;
use SwooleTool\AbstractInterface\Singleton;
use SwooleTool\Component\Config\ConfigUtil;

class Logger {
    use Singleton;

    private $loggerWriter;

    function __construct() {
        $logDir = ConfigUtil::getInstance()->getConf('LOG_DIR');

        $this->loggerWriter = new \Monolog\Logger("swoole_glue_logger");
        $this->loggerWriter->pushHandler(new RotatingFileHandler($logDir . "/debug.log"));

    }

    public function debug($logStr) {

        $debug = $this->debugInfo();
        $debug = "file[{$debug['file']}] function[{$debug['function']}] line[{$debug['line']}]";
        $logStr = "{$debug} message: [{$logStr}]";

        $this->loggerWriter->debug($logStr);
    }

    public function __call($name, $arguments) {
        $this->loggerWriter->$name($arguments);
    }


    private function debugInfo() {
        $trace = debug_backtrace();
        $file = $trace[1]['file'];
        $line = $trace[1]['line'];
        $func = isset($trace[2]['function']) ? $trace[2]['function'] : 'unKnown';
        return ['file' => $file, 'line' => $line, 'function' => $func];
    }
}