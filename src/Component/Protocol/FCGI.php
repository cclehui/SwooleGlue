<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace SwooleGlue\Component\Protocol;

use SwooleGlue\Component\Protocol\FCGI\FrameParser;
use SwooleGlue\Component\Protocol\FCGI\ProtocolException;
use SwooleGlue\Component\Protocol\FCGI\Record;
use SwooleGlue\Component\Protocol\FCGI\Response;
use SwooleGlue\Component\Protocol\FCGI\Request;

class FCGI {
    /**
     * Number of bytes in a FCGI_Header.  Future versions of the protocol
     * will not reduce this number.
     */
    const HEADER_LEN = 8;

    /**
     * Format of FCGI_HEADER for unpacking in PHP
     */
    const HEADER_FORMAT = "Cversion/Ctype/nrequestId/ncontentLength/CpaddingLength/Creserved";

    /**
     * Value for version component of FCGI_Header
     */
    const VERSION_1 = 1;

    /**
     * Values for type component of FCGI_Header
     * 请求包的 type
     */
    const BEGIN_REQUEST = 1;
    const ABORT_REQUEST = 2;
    const END_REQUEST = 3;
    const PARAMS = 4;
    const STDIN = 5;
    const STDOUT = 6;
    const STDERR = 7;
    const DATA = 8;
    const GET_VALUES = 9;
    const GET_VALUES_RESULT = 10;
    const UNKNOWN_TYPE = 11;

    /**
     * Value for requestId component of FCGI_Header
     */
    const NULL_REQUEST_ID = 0;

    /**
     * Mask for flags component of FCGI_BeginRequestBody
     */
    const KEEP_CONN = 1;

    /**
     * Values for role component of FCGI_BeginRequestBody
     *  角色定义
     */
    const RESPONDER = 1;
    const AUTHORIZER = 2;
    const FILTER = 3;

    /**
     * Values for protocolStatus component of FCGI_EndRequestBody
     * 结束请求的包的 的protocolStatus 状态定义
     */
    const REQUEST_COMPLETE = 0; //请求的正常结束
    const CANT_MPX_CONN = 1; //拒绝新请求。
    const OVERLOADED = 2;
    const UNKNOWN_ROLE = 3;

    //fastcgi request 数据的状态
    const STATUS_FINISH = 1; //完成，进入处理流程
    const STATUS_WAIT = 2; //等待数据
    const STATUS_CLOSE = 3; //错误，丢弃此包

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

        echo "xxxxxxxxxx:$data\n";

        while (FrameParser::hasFrame($this->bufferData[$fd])) {
            $record = FrameParser::parseFrame($this->bufferData[$fd]);

            echo $record . "\n";

            //判断数据包的状态
            switch ($record->getType()) {
                case self::BEGIN_REQUEST: //请求开始
                    if (!$this->handleNewRequest($record, $fd)) {
                        $packageStatus = self::STATUS_CLOSE;
                    }
                    $packageStatus = self::STATUS_WAIT;
                    break;

                case self::ABORT_REQUEST:
                    $packageStatus = self::STATUS_CLOSE;
                    break;

                case self::PARAMS:
                    if (!$this->handleRequestParams($record)) {
                        $packageStatus = self::STATUS_CLOSE;
                    }
                    $packageStatus = self::STATUS_WAIT;
                    break;

                case self::STDIN:
                    if ($record->getContentLength() <= 0) {
                        $packageStatus = self::STATUS_FINISH;
                    } else {
                        $this->handleStdin($record);
                        $packageStatus = self::STATUS_WAIT;
                    }
                    break;
            }

            //根据当前数据包的状态做处理
            switch ($packageStatus) {
                case self::STATUS_FINISH:
                    $request = $this->requests[$record->getRequestId()];
                    $this->bufferData[$fd] = '';
                    unset($this->requests[$record->getRequestId()]);

                    //设置全局变量
                    $request->setGlobal();
                    $response = new Response($server, $fd);

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

        $server = $response->server;
        $fd = $response->fd;

        ob_start();
        print_r($request);

        $result = ob_get_contents();
        ob_end_clean();

        $stdoutRecord = new Record\Stdout($result);
        $server->send($fd, $stdoutRecord->getContentData());

        $endRequest = new Record\EndRequest(self::REQUEST_COMPLETE);
        $server->send($fd, $endRequest->getContentData());
        $server->close($fd);

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

        if ($record->getRole() != self::RESPONDER) {
            throw new ProtocolException('handleNewRequest unsupported Role:' . $record->getRole());
        }

        $request = new Request();
        $request->fd = $fd;
        $request->id = $requestId;
        $request->body = fopen('php://input', 'r+');
//        $request->body = "";

        $this->requests[$requestId] = $request;
        return true;
    }
}
