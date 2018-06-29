<?php

namespace SwooleGlue\Component\Swoole\Http;


interface ExceptionHandlerInterface {
    public function handle(\Throwable $throwable, \Swoole\Http\Request $request, \Swoole\Http\Response $response);
}