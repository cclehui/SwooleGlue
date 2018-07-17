<?php

namespace SwooleGlue\Component\Http;


class Headers {
    public static $http_protocol = 'HTTP/1.1';
    public static $http_status = 200;

    public static $head = [];
    public static $cookie = [];

    public static $cacheByClass = true;//使用类来缓存header信息

    static $HTTP_HEADERS = array(
        100 => "100 Continue",
        101 => "101 Switching Protocols",
        200 => "200 OK",
        201 => "201 Created",
        204 => "204 No Content",
        206 => "206 Partial Content",
        300 => "300 Multiple Choices",
        301 => "301 Moved Permanently",
        302 => "302 Found",
        303 => "303 See Other",
        304 => "304 Not Modified",
        307 => "307 Temporary Redirect",
        400 => "400 Bad Request",
        401 => "401 Unauthorized",
        403 => "403 Forbidden",
        404 => "404 Not Found",
        405 => "405 Method Not Allowed",
        406 => "406 Not Acceptable",
        408 => "408 Request Timeout",
        410 => "410 Gone",
        413 => "413 Request Entity Too Large",
        414 => "414 Request URI Too Long",
        415 => "415 Unsupported Media Type",
        416 => "416 Requested Range Not Satisfiable",
        417 => "417 Expectation Failed",
        500 => "500 Internal Server Error",
        501 => "501 Method Not Implemented",
        503 => "503 Service Unavailable",
        506 => "506 Variant Also Negotiates",
    );

    /**
     * 在每次处理请求前需要初始化
     */
    public static function init() {
        self::$http_status = 200;
        self::$head = [];
        self::$cookie= [];

        if (strpos(php_sapi_name(), 'cli') === false) {
            self::$cacheByClass = false;
        }
    }

    /**
     * 设置Http状态
     * @param $code
     */
    public static function setHttpStatus($code) {
        self::$head[0] = self::$http_protocol . ' ' . self::$HTTP_HEADERS[$code];
        self::$http_status = $code;
    }

    /**
     * 设置Http头信息
     * @param $key
     * @param $value
     */
    public static function setHeader($key, $value) {
        self::$head[$key] = $value;
    }

    /**
     * 设置header信息
     * @param $headerStr
     * @return bool|void
     */
    public static function header($headerStr) {
        if (!$headerStr) {
            return false;
        }

        if (self::$cacheByClass) {
            list($key, $value) = explode(":", $headerStr, 2);
            self::setHeader($key, $value);
        } else {
            return header($headerStr);
        }

        return true;
    }

    /**
     * 设置COOKIE
     * @param $name
     * @param null $value
     * @param null $expire
     * @param string $path
     * @param null $domain
     * @param null $secure
     * @param null $httponly
     */
    public static function setcookie($name, $value = null, $expire = null, $path = '/', $domain = null, $secure = null, $httponly = null) {

        if (!self::$cacheByClass) {
            return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }

        if ($value == null) {
            $value = 'deleted';
        }
        $cookie = "$name=$value";
        if ($expire) {
            $cookie .= "; expires=" . date("D, d-M-Y H:i:s T", $expire);
        }
        if ($path) {
            $cookie .= "; path=$path";
        }
        if ($secure) {
            $cookie .= "; secure";
        }
        if ($domain) {
            $cookie .= "; domain=$domain";
        }
        if ($httponly) {
            $cookie .= '; httponly';
        }
        self::$cookie[] = $cookie;

        return true;
    }


    public static function getHeaderStr($fastcgi = false) {

        if ($fastcgi) {
            $out = 'Status: ' . self::$http_status . ' ' . self::$HTTP_HEADERS[self::$http_status] . "\r\n";

        } else {
            //Protocol
            $out = self::$http_protocol . ' ' . self::$HTTP_HEADERS[self::$http_status] . "\r\n";
        }
        //fill header
//        if (!isset(self::$head['Server'])) {
//            self::$head['Server'] = 'Swoole';
//        }

//        if (!isset(self::$head['Content-Type'])) {
//            self::$head['Content-Type'] = 'text/html; charset=' . \Swoole::$charset;
//        }
//
//        if (!isset(self::$head['Content-Length'])) {
//            self::$head['Content-Length'] = strlen($this->body);
//        }
        //Headers
        foreach (self::$head as $k => $v) {
            $out .= $k . ': ' . $v . "\r\n";
        }
        //Cookies
        if (self::$cookie && is_array(self::$cookie)) {
            foreach (self::$cookie as $v) {
                $out .= "Set-Cookie: $v\r\n";
            }
        }
        //End
        $out .= "\r\n";
        return $out;
    }

    public static function noCache() {
        self::$head['Cache-Control'] = 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0';
        self::$head['Pragma'] = 'no-cache';
    }

}