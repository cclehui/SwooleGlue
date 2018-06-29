<?php

namespace SwooleGlue\Component\Swoole\Http;


use SwooleGlue\Component\SysConst;

class HttpHandler{


    /*
     * @var $reqeust \Swoole\Http\Request
     */
    protected $request;

    /*
     *  @var $response \Swoole\Http\Response
     */
    protected $response;

    public function __construct(\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
        $_GET = $request->get ? : [];
        $_POST = $request->post ? : [];
        $_REQUEST = array_merge($_GET, $_POST);
        $_SERVER = $request->server ? : [];
        $_COOKIE = $request->cookie ? : [];

        $this->request = $request;
        $this->response = $response;

    }


    public function doService() {
        include \SwooleGlue\Component\Config\ConfigUtil::getInstance()->getConf(SysConst::ROOT_INDEX_FILE);
    }

}