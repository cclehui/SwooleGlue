<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/29
 * Time: 13:57
 */

namespace SwooleGlue\Component;


class ErrorUtil {

    public static function getErrorLevelStr($errorCode) {

        switch ($errorCode) {
            case E_NOTICE:
                return 'E_NOTICE';

            case E_WARNING:
                return 'E_WARNING';

            case E_ERROR:
                return 'E_ERROR';

            case E_PARSE:
                return 'E_PARSE';

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