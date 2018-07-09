<?php

namespace SwooleGlue\Component\Protocol\FCGI;

use SwooleGlue\Component\Protocol\FCGI\Record\EndRequest;
use SwooleGlue\Component\Protocol\FCGI\Record\Stdout;

class Response {
    /**
     * 文件描述符
     * @var int
     */
    public $fd;

    /**
     * @var \swoole_server
     */
    public $server;

    /**
     * @var Request
     */
    public $request;

    public $httpHeaders = [];


    public function __construct(Request $request, \swoole_server $server, int $fd) {
        $this->request = $request;
        $this->server = $server;
        $this->fd = $fd;
    }

    public function addHeader($name, $value) {

    }

    /**
     * 返回结果
     * @param string $data
     */
    public function sendStdoutResponse(string $data) {
        $dataLength = strlen($data);

        if ($dataLength <= 65535) {
            $record = new Stdout($data);
            $record->setRequestId($this->request->requestId);
            $this->server->send($this->fd, strval($record));

        } else {
            $start = 0;
            $chunkSize = 8092;
            do {
                $record = new Stdout(substr($data, $start, $chunkSize));
                $record->setRequestId($this->request->requestId);
                $this->server->send($this->fd, strval($record));
                $start += $chunkSize;

            } while($start < $dataLength);

            $record = new Stdout("");
            $record->setRequestId($this->request->requestId);
            $this->server->send($this->fd, strval($record));
        }
    }

    /**
     * 结束请求
     */
    public function sendEndRequest() {
        $record = new EndRequest();
        $record->setRequestId($this->request->requestId);

        $this->server->send($this->fd, strval($record));
        $this->server->close($this->fd);
    }

}
