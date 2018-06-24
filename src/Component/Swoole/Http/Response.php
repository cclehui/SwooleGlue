<?php

namespace SwooleTool\Component\Swoole\Http;


class Response extends \Swoole\Http\Response {

    protected $response;

    public function __construct(\Swoole\Http\Response $response) {
        $this->response = $response;
    }

}