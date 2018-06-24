<?php

namespace SwooleTool\Component\Swoole;


use Swoole\Http\Request;
use Swoole\Http\Response;
use SwooleTool\Component\Swoole\Http\Status;

class EventHelper {

    public static function registerDefaultOnRequest(EventRegister $register): void {
        $register->set($register::onRequest, function (Request $request, Response $response)  {
            $request_psr = new \SwooleTool\Component\Swoole\Http\Request($request);
            $response_psr = new \SwooleTool\Component\Swoole\Http\Response($response);
            try {

                $response->write("xxxxxxxxxxxxxx:" .date("Y-m-d H:i:s"));


            } catch (\Throwable $throwable) {
                $handler = Di::getInstance()->get(SysConst::HTTP_EXCEPTION_HANDLER);
                if ($handler instanceof ExceptionHandlerInterface) {
                    $handler->handle($throwable, $request_psr, $response_psr);
                } else {
//                    $response_psr->withStatus(Status::CODE_INTERNAL_SERVER_ERROR);
//                    $response_psr->write(nl2br($throwable->getMessage() . "\n" . $throwable->getTraceAsString()));
                    $response->write(nl2br($throwable->getMessage() . "\n" . $throwable->getTraceAsString()));
                }
            }
        });
    }
}