<?php
namespace SwooleTool\Component\Swoole;


abstract class Handler {

    abstract public function init();

    abstract public function service();

    abstract public function destroy();
}