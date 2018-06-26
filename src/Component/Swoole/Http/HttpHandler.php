<?php

namespace SwooleTool\Component\Swoole\Http;


use SwooleTool\Component\SysConst;
use SwooleTool\Config\ConfigUtil;

class HttpHandler{


    //@var Reques
    protected $request;

    protected $response;

    public function __construct(\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
        $_GET = $request->get;
        $_POST = $request->post;
        $_REQUEST = array_merge($_GET, $_POST);
        $_SERVER = $request->server;
        $_COOKIE = $request->cookie;

        $this->request = $request;
        $this->response = $response;

    }


    public function doService() {
        ob_start();

        print_r($this->request->header);

        include ConfigUtil::getInstance()->getConf(SysConst::ROOT_INDEX_FILE);

        $result = ob_get_contents();
        ob_end_clean();

        return $result;
    }

}