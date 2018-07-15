<?php

namespace SwooleGlue\Component;


//cgi 执行程序
class PhpCgiRunner {

    /**
     * 执行php程序
     * @return string
     */
    public static function runPhp(): string {

        ob_start();

//        include \SwooleGlue\Component\Config\ConfigUtil::getInstance()->getConf(SysConst::ROOT_INDEX_FILE);
        Di::getInstance()->get(SysConst::PSERVERLET)->doRequest();

        $result = ob_get_contents();
        ob_end_clean();


        return $result;
    }

    public static function getHttpHeadersStr(): string {

        //http header处理  这里包含了cookie数据
        $headers = headers_list();

        $headers[] = 'Content-Type:text/html';
        $headers[] = 'aaaaaaa:bbbbbbbbbbb';

//        var_dump($headers);

        $result = "";

        if ($headers) {
            foreach ($headers as $item) {
                $result .= $item . "\r\n";
            }
        }

        return $result;
    }

}