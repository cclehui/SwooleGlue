<?php
require_once __DIR__ . "/../vendor/autoload.php";

use \SwooleGlue\Component\Protocol\FCGI\FrameParser;


$server = stream_socket_server("tcp://127.0.0.1:9001", $errorNumber, $errorString);

// Just take the first one request and process it
while (true) {
    $phpSocket = stream_socket_accept($server);

    $response = '';
    while ($partialData = fread($phpSocket, 4096)) {
        $response .= $partialData;
        while (FrameParser::hasFrame($response)) {
            $record = FrameParser::parseFrame($response);
            var_dump($record);
        };
    };

// We don't respond correctly here, it's a task for your application

    fclose($phpSocket);

}

fclose($server);