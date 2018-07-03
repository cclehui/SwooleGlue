<?php

namespace SwooleGlue\Component\Swoole;


use Swoole\Http\Request;
use Swoole\Http\Response;
use SwooleGlue\Component\Di;
use SwooleGlue\Component\Logger;
use SwooleGlue\Component\Swoole\Http\HttpHandler;
use SwooleGlue\Component\Swoole\Http\Status;
use SwooleGlue\Component\SysConst;

class EventHelper {

    //默认的 http handler
    public static function registerDefaultOnRequest(EventRegister $register) {
        $register->set($register::onRequest, function (Request $request, Response $response)  {



            try {
                ob_start();

                $http_handler = new HttpHandler($request, $response);

                //执行处理
                $http_handler->doService();

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

                $result = ob_get_contents();
                ob_end_clean();

                $response->write($result);


            } catch (\Throwable $throwable) {

                switch ($throwable->getCode()) {//优化
                    case E_ERROR:
                        Logger::getInstance()->error($throwable->getCode() . ", " . $throwable->getMessage() . ", " . $throwable->getTraceAsString());
                        break;
                    default:
                        Logger::getInstance()->info($throwable->getCode() . ", " .$throwable->getMessage() . ", " . $throwable->getTraceAsString());

                }

                $handler = Di::getInstance()->get(SysConst::HTTP_EXCEPTION_HANDLER);
                if ($handler instanceof ExceptionHandlerInterface) {
                    $handler->handle($throwable, $request, $response);
                } else {
                    $response->status(Status::CODE_INTERNAL_SERVER_ERROR);
                    $response->write("xxxx:" . nl2br($throwable->getMessage() . "\n" . $throwable->getTraceAsString()));
                }
            }
        });
    }
}