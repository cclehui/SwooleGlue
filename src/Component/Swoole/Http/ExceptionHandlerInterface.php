<?php

namespace SwooleTool\Component\Swoole\Http;


interface ExceptionHandlerInterface {
    public function handle(\Throwable $throwable, Request $request, Response $response);
}