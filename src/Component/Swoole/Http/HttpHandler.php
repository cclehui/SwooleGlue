<?php

namespace SwooleGlue\Component\Swoole\Http;


use SwooleGlue\Component\SysConst;

class HttpHandler{


    //@var Reques
    protected $request;

    protected $response;

    public function __construct(\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
        $_GET = $request->get;
        $_POST = $request->post;
        $_REQUEST = array_merge($_GET, $_POST);
        $_REQUEST->test();
        $_SERVER = $request->server;
        $_COOKIE = $request->cookie;

        $this->request = $request;
        $this->response = $response;

    }


    public function doService() {
        print_r($this->request->header);

        include \SwooleGlue\Component\Config\ConfigUtil::getInstance()->getConf(SysConst::ROOT_INDEX_FILE);

    }

}