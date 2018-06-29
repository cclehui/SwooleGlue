<?php

namespace SwooleGlue\Component\Swoole\Http;


class Request extends \Swoole\Http\Request {

    protected $request;

    public function __construct(\Swoole\Http\Request $request) {
        $this->request = $request;
    }

}