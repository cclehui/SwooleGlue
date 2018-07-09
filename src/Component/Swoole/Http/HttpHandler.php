<?php

namespace SwooleGlue\Component\Swoole\Http;


use SwooleGlue\Component\Di;
use SwooleGlue\Component\Logger;
use SwooleGlue\Component\PhpCgiRunner;
use SwooleGlue\Component\SysConst;

class HttpHandler {


    /*
     * @var $reqeust \Swoole\Http\Request
     */
    protected $request;

    /*
     *  @var $response \Swoole\Http\Response
     */
    protected $response;

    public function __construct(\Swoole\Http\Request $request, \Swoole\Http\Response $response) {


    }


    /**
     * swoole onRequest事件处理
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     */
    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
        $_GET = $request->get ?: [];
        $_POST = $request->post ?: [];
        $_REQUEST = array_merge($_GET, $_POST);
        $_SERVER = $request->server ?: [];
        $_COOKIE = $request->cookie ?: [];

        $this->request = $request;
        $this->response = $response;

        try {

            //执行处理
            $result = PhpCgiRunner::runPhp();

            //http header处理
            $headers = headers_list();

            if ($headers) {
                foreach ($headers as $key => $value) {
                    $response->header($key, $value);
                }
            }

            if (!isset($headers['Content-Type'])) {
                $response->header('Content-Type', 'text/html');
            }

            //cookie处理
            //cclehui_todo


            $response->write($result);


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
                $response->status(Status::CODE_INTERNAL_SERVER_ERROR);
                $response->write(nl2br($throwable->getMessage() . "\n" . $throwable->getTraceAsString()));
            }
        }

    }

}