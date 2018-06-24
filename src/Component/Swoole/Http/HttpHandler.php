<?php

namespace SwooleTool\Component\Swoole\Http;


use SwooleTool\Component\Swoole\Handler;

abstract class HttpHandler extends Handler {

    abstract public function service();

}