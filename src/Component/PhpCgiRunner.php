<?php

namespace SwooleGlue\Component;


//cgi 执行程序
use SwooleGlue\Component\Http\Headers;

class PhpCgiRunner {

    public static $ob_started = false;

    /**
     * 执行php程序
     * @return string
     */
    public static function runPhp(): string {

        //初始化header数据
        Headers::init();

        self::$ob_started = false;

        ob_start();

        self::$ob_started = true;

//        include \SwooleGlue\Component\Config\ConfigUtil::getInstance()->getConf(SysConst::ROOT_INDEX_FILE);
        Di::getInstance()->get(SysConst::PSERVERLET)->doRequest();

        $result = ob_get_contents();
        ob_end_clean();


        return $result;
    }

    /**
     * 获取http header信息
     * @return string
     */
    public static function getHttpHeadersStr($fastcgi = false): string {
        $result = Headers::getHeaderStr($fastcgi);

        return $result;
    }

    public static function getHttpHeaders() {
        return Headers::getHeaders();
    }

}