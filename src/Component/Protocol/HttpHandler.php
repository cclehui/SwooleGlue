<?php

namespace SwooleGlue\Component\Protocol;


use SwooleGlue\Component\Di;
use SwooleGlue\Component\Logger;
use SwooleGlue\Component\PhpCgiRunner;
use SwooleGlue\Component\SysConst;

class HttpHandler {

    public function __construct() {

    }


    /**
     * swoole onRequest事件处理
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     */
    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response) {


        try {
            //初始化全局变量
            $_GET = $request->get ?: [];
            $_POST = $request->post ?: [];
            $_REQUEST = array_merge($_GET, $_POST);
            $_SERVER = $request->server ?: [];
            $_COOKIE = $request->cookie ?: [];

            register_shutdown_function(function() use($response) {
                $result = "";
                if (PhpCgiRunner::$ob_started) {
                    $result = ob_get_contents();
                    ob_end_clean();
                }

                $this->sendReponse($response, $result);
            });

            //执行处理
            $result = PhpCgiRunner::runPhp();

            $this->sendReponse($response, $result);


        } catch (\Throwable $throwable) {

            switch ($throwable->getCode()) {//优化
                case E_ERROR:
                    Logger::getInstance()->error($throwable->getCode() . ", " . $throwable->getMessage() . ", " . $throwable->getTraceAsString());
                    break;
                default:
                    Logger::getInstance()->info($throwable->getCode() . ", " . $throwable->getMessage() . ", " . $throwable->getTraceAsString());

            }

            $handler = Di::getInstance()->get(SysConst::HTTP_EXCEPTION_HANDLER);
            if ($handler instanceof ExceptionHandlerInterface) {
                $handler->handle($throwable, $request, $response);
            } else {
                $response->status(500);
                $response->write(nl2br($throwable->getMessage() . "\n" . $throwable->getTraceAsString()));
            }
        }

    }

    protected function sendReponse($response, $result) {
        //http header处理
        //$headers = headers_list();
        $headers = PhpCgiRunner::getHttpHeaders();

        if ($headers) {
            foreach ($headers as $key => $value) {
                $response->header($key, $value);
            }
        }

        if (!isset($headers['Content-Type'])) {
            $response->header('Content-Type', 'text/html');
        }

        $response->write($result);
    }

}