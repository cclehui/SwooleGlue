<?php

namespace SwooleGlue\AbstractInterface;


interface TriggerInterface {
    public static function error($msg, $file = null, $line = null, $errorCode = E_USER_ERROR);

    public static function throwable(\Throwable $throwable);
}