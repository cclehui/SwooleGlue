<?php

namespace SwooleGlue\Component\Protocol;

use SwooleGlue\Component\Logger;
use SwooleGlue\Component\PhpCgiRunner;
use SwooleGlue\Component\Protocol\FCGI\FCGI;
use SwooleGlue\Component\Protocol\FCGI\FrameParser;
use SwooleGlue\Component\Protocol\FCGI\ProtocolException;
use SwooleGlue\Component\Protocol\FCGI\Record;
use SwooleGlue\Component\Protocol\FCGI\Response;
use SwooleGlue\Component\Protocol\FCGI\Request;

class FCGIHandler {

    //fastcgi request 数据的状态
    const STATUS_FINISH = 10; //完成，进入处理流程
    const STATUS_WAIT = 2; //等待数据
    const STATUS_CLOSE = 99; //错误，丢弃此包

    /*
     * @var | array
     */
    protected $bufferData = [];

    protected $requests = [];

    public function __construct() {
        $this->bufferData = [];
    }

    public function onReceive(\swoole_server $server, int $fd, int $reactorId, string $data) {

        if (isset($this->bufferData[$fd])) {
            $this->bufferData[$fd] .= $data;
        } else {
            $this->bufferData[$fd] = $data;
        }

        $packageStatus = self::STATUS_WAIT;

        Logger::getInstance()->debug("FCGI data:$data");

        while (FrameParser::hasFrame($this->bufferData[$fd])) {
            $record = FrameParser::parseFrame($this->bufferData[$fd]);

            //判断数据包的状态
            switch ($record->getType()) {
                case FCGI::BEGIN_REQUEST: //请求开始
                    if (!$this->handleNewRequest($record, $fd)) {
                        $packageStatus = self::STATUS_CLOSE;
                    }
                    $packageStatus = self::STATUS_WAIT;
                    break;

                case FCGI::ABORT_REQUEST:
                    $packageStatus = self::STATUS_CLOSE;
                    break;

                case FCGI::PARAMS:
                    if (!$this->handleRequestParams($record)) {
                        $packageStatus = self::STATUS_CLOSE;
                    }
                    $packageStatus = self::STATUS_WAIT;
                    break;

                case FCGI::STDIN:
                    if ($record->getContentLength() <= 0) {
                        $packageStatus = self::STATUS_FINISH;
                    } else {
                        $this->handleStdin($record);
                        $packageStatus = self::STATUS_WAIT;
                    }
                    break;
            }

            Logger::getInstance()->debug("FCGI status, recordType:" . $record->getType() . ", packageStatus:$packageStatus");

            //根据当前数据包的状态做处理
            switch ($packageStatus) {
                case self::STATUS_FINISH:
                    $request = $this->requests[$record->getRequestId()];
                    $this->bufferData[$fd] = '';
                    unset($this->requests[$record->getRequestId()]);

                    //设置全局变量
                    $request->setGlobal();
                    $response = new Response($request, $server, $fd);

                    $this->handleRequest($request, $response); //处理请求
                    return true;

                case self::STATUS_WAIT; //等待请求数据
                    break;

                default:
                    $endRequest = new Record\EndRequest(self::REQUEST_COMPLETE);
                    $server->send($fd, $endRequest->getContentData());
                    $server->close($fd);

                    $this->bufferData[$fd] = '';
                    unset($this->requests[$record->getRequestId()]);
                    return true;
            }
        }

        return true;
    }

    //处理请求
    public function handleRequest(Request $request, Response $response) {

        try {

            register_shutdown_function(function() use($response) {
                $stdout = "";
                if (PhpCgiRunner::$ob_started) {
                    $stdout = ob_get_contents();
                    ob_end_clean();
                }

                $headerStr = PhpCgiRunner::getHttpHeadersStr();

                $result = $headerStr . "\r\n" . $stdout;

                //发送结果
                $response->sendStdoutResponse($result);
            });

            $stdout = PhpCgiRunner::runPhp();

            $headerStr = PhpCgiRunner::getHttpHeadersStr();

            $result = $headerStr . "\r\n" . $stdout;

            //发送结果
            $response->sendStdoutResponse($result);

            Logger::getInstance()->info("dddddd:$headerStr, " . php_sapi_name());


        } catch (\Throwable $throwable) {

            switch ($throwable->getCode()) {//优化
                case E_ERROR:
                    Logger::getInstance()->error($throwable->getCode() . ", " . $throwable->getMessage() . ", " . $throwable->getTraceAsString());
                    break;
                default:
                    Logger::getInstance()->info($throwable->getCode() . ", " . $throwable->getMessage() . ", " . $throwable->getTraceAsString());

            }


//            $response->status(Status::CODE_INTERNAL_SERVER_ERROR);
            $response->sendStdoutResponse("dddddddddddddddddddddddd");
//            $response->sendStdoutResponse(nl2br($throwable->getMessage() . "\n" . $throwable->getTraceAsString()));
        }


        //结束请求
        $response->sendEndRequest();
    }

    /**
     * @param Record\Stdin $record
     * @return bool
     * @throws ProtocolException
     */
    protected function handleStdin(Record\Stdin $record) {
//        if ($record->getContentLength() <= 0) {
//            return true;
//        }
        $requestId = $record->getRequestId();

        if (!isset($this->requests[$requestId])) {
            throw new ProtocolException("handleStdin: $requestId has no request inited");
        }

//        $this->requests[$requestId]->body .= $record->getContentData();
        fwrite($this->requests[$requestId]->body, $record->getContentData(), FILE_APPEND);
        return true;
    }

    /**
     * @param Record\Params $record
     * @return bool
     * @throws ProtocolException
     */
    protected function handleRequestParams(Record\Params $record) {
        $requestId = $record->getRequestId();

        if (!isset($this->requests[$requestId])) {
            throw new ProtocolException("handleRequestParams: $requestId has no request inited");
        }

        $params = $record->getValues();
        if (!$params) {
            return true;
        }

        /**
         * @var Request
         */
        $request = $this->requests[$requestId];

        foreach ($params as $name => $value) {
            $tempParamName = strtoupper($name);
            switch ($tempParamName) {
                case 'QUERY_STRING':
                    parse_str($value, $request->get);
                    break;

                case 'HTTP_COOKIE':
                    $cookies = explode('; ', $value);
                    foreach ($cookies as $item) {
                        $item = explode('=', $item);
                        if (count($item) === 2) {
                            $request->cookie[$item[0]] = urldecode($item[1]);
                        }
                    }
                    break;

                default:
                    $this->requests[$requestId]->server[$name] = $value;
                    break;
            }
        }

        return true;
    }

    /**
     * 根据fascgi 的record 创建一个新的request
     * @param Record\BeginRequest $record
     * @param int $fd
     *
     * @return boolean
     * @throws ProtocolException
     */
    protected function handleNewRequest(Record\BeginRequest $record, int $fd) {

        $requestId = $record->getRequestId();
        if (isset($this->requests[$requestId])) {
            throw new ProtocolException("handleNewRequest:$requestId has no request inited ");
        }

        if ($record->getRole() != FCGI::RESPONDER) {
            throw new ProtocolException('handleNewRequest unsupported Role:' . $record->getRole());
        }

        $request = new Request();
        $request->fd = $fd;
        $request->requestId = $requestId;
        $request->fcgiVersion = $record->getVersion();
        $request->body = fopen('php://input', 'r+');
//        $request->body = "";

        $this->requests[$requestId] = $request;
        return true;
    }
}
