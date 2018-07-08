<?php

namespace SwooleGlue\Component\Protocol\FCGI;

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


    public function __construct(\swoole_server $server, int $fd) {
        $this->server = $server;
        $this->fd = $fd;
    }

    public function sendResponse(string $data) {
        $dataLength = strlen($data);

        if ($dataLength <= 65535) {
            $record = new Stdout($data);
            $this->server->send($this->fd, $record->getContentData());
        } else {
            $start = 0;
            $chunkSize = 8092;
            do {

                $record = new Stdout(substr($data, $start, $chunkSize));
                $this->server->send($this->fd, $record->getContentData());
                $start += $chunkSize;

            } while($start < $dataLength);

            $record = new Stdout("");
            $this->server->send($this->fd, $record->getContentData());
        }
    }

}
