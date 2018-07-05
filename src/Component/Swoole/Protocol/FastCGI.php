<?php

namespace SwooleGlue\Component\Swoole\Protocol;

class FastCGI {

    const HEADER_LENGTH = 8;

    const FCGI_BEGIN_REQUEST = 1;
    const FCGI_ABORT_REQUEST = 2;
    const FCGI_END_REQUEST = 3;
    const FCGI_PARAMS = 4;
    const FCGI_STDIN = 5;
    const FCGI_STDOUT = 6;
    const FCGI_STDERR = 7;
    const FCGI_DATA = 8;
    const FCGI_GET_VALUES = 9;
    const FCGI_GET_VALUES_RESULT = 10;
    const FCGI_UNKNOWN_TYPE = 11;

    const FCGI_RESPONDER = 1;
    const FCGI_AUTHORIZER = 2;
    const FCGI_FILTER = 3;

    protected static $roles = [
        self::FCGI_RESPONDER => 'FCGI_RESPONDER',
        self::FCGI_AUTHORIZER => 'FCGI_AUTHORIZER',
        self::FCGI_FILTER => 'FCGI_FILTER',
    ];

    const STATE_HEADER = 0;
    const STATE_BODY = 1;
    const STATE_PADDING = 2;

    //fastcgi request 数据的状态
    const STATUS_FINISH = 1; //完成，进入处理流程
    const STATUS_WAIT   = 2; //等待数据
    const STATUS_ERROR  = 3; //错误，丢弃此包


    public function onReceive(\swoole_server $server, int $fd, int $reactorId, string $data) {

        //检测request data完整性
        $status = $this->checkDataStatus($data);

        switch ($status) {
            //错误的请求
            case self::ST_ERROR;
                $this->server->close($fd);
                return;
            //请求不完整，继续等待
            case self::ST_WAIT:
                return;
            default:
                break;
        }

    }


    protected function checkDataStatus($data) {

    }


}