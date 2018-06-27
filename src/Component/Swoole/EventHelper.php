<?php

namespace SwooleTool\Component\Swoole;


use Swoole\Http\Request;
use Swoole\Http\Response;
use SwooleTool\Component\Di;
use SwooleTool\Component\Logger;
use SwooleTool\Component\Swoole\Http\HttpHandler;
use SwooleTool\Component\Swoole\Http\Status;
use SwooleTool\Component\SysConst;

class EventHelper {

    //默认的 http handler
    public static function registerDefaultOnRequest(EventRegister $register): void {
        $register->set($register::onRequest, function (Request $request, Response $response)  {
            ob_start();

            try {

                echo "11111111111\n";

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

                $result = ob_get_contents();

                Logger::getInstance()->console($result);
                ob_end_clean();

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