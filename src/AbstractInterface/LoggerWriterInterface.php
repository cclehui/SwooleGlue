<?php

namespace SwooleTool\AbstractInterface;


interface LoggerWriterInterface {
    function writeLog($obj, $logCategory, $timeStamp);
}