<?php

namespace SwooleTool\Component\Swoole;


use Swoole\Http\Request;
use Swoole\Http\Response;
use SwooleTool\Component\Swoole\Http\HttpHandler;
use SwooleTool\Component\Swoole\Http\Status;

class EventHelper {

    //默认的 http handler
    public static function registerDefaultOnRequest(EventRegister $register): void {
        $register->set($register::onRequest, function (Request $request, Response $response)  {

            $http_handler = new HttpHandler($request, $response);

            try {

                //执行处理
                $result = $http_handler->doService();

                //http header处理
                $headers = headers_list();

                if ($headers) {
                    foreach ($headers as $key => $value) {
                        $response->header($key, $value);
                    }
                }

                if (!$headers['Content-Type']) {
                    $response->header('Content-Type', 'text/html');
                }

                //cookie处理
                //cclehui_todo


                $response->write($result);


            } catch (\Throwable $throwable) {
                $handler = Di::getInstance()->get(SysConst::HTTP_EXCEPTION_HANDLER);
                if ($handler instanceof ExceptionHandlerInterface) {
                    $handler->handle($throwable, $request, $response);
                } else {

                    $response->status(Status::CODE_INTERNAL_SERVER_ERROR);
                    $response->write(nl2br($throwable->getMessage() . "\n" . $throwable->getTraceAsString()));
                }
            }
        });
    }
}