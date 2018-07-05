<?php

namespace SwooleGlue\Component\Swoole;

class Request {

    /**
     * 文件描述符
     * @var int
     */
    public $fd;
    public $id;

    public $get = array();
    public $post = array();
    public $files = array();
    public $cookie = array();
    public $session = array();
    public $request;
    public $server = array();

    /**
     * @var \StdClass
     */
    public $attrs;

    public $header = array();
    public $body;
    public $meta = array();

    public $finish = false;
    public $ext_name;
    public $status;

    /**
     * 将原始请求信息转换到PHP超全局变量中
     */
    function setGlobal() {
        /**
         * 将HTTP头信息赋值给$_SERVER超全局变量
         */
        foreach ($this->header as $key => $value) {
            $_key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $this->server[$_key] = $value;
        }

        $_GET = $this->get;
        $_POST = $this->post;
        $_FILES = $this->files;
        $_COOKIE = $this->cookie;
        $_SERVER = $this->server;

        $this->request = $_REQUEST = array_merge($this->get, $this->post);
    }

    /**
     * LAMP环境初始化
     */
    function initWithLamp() {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookie = $_COOKIE;
        $this->server = $_SERVER;
        $this->request = $_REQUEST;
    }

    function unsetGlobal() {
        $_REQUEST = $_SESSION = $_COOKIE = $_FILES = $_POST = $_SERVER = $_GET = array();
    }

}
