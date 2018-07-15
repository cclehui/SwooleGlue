<?php

return [
    'SERVER_NAME'=> "SwooleServer",
    'VERSION' => '1.0.0',
    'MAIN_SERVER'=> [
        'HOST'=> '0.0.0.0',
        'PORT'=> 9501,
        'SERVER_TYPE'=> \SwooleGlue\Component\Swoole\SwooleServer::TYPE_FAST_CGI_SERVER,
        'SOCK_TYPE'=> SWOOLE_TCP,//该配置项当为SERVER_TYPE值为TYPE_SERVER时有效  //cclehui_todo
        'RUN_MODEL'=> SWOOLE_PROCESS,
        'SETTING'=> [
//            'task_worker_num' => 8, //异步任务进程
//            'task_max_request'=> 10,
            'max_request' => 5000,//强烈建议设置此配置项
            'worker_num'=> 8,
        ],
    ],

    'PSERVERLET' => '', //server 处理的handler  PServerlet 的实例化类
    //'ROOT_INDEX_FILE' => '', //http处理的入口文件

    'ERROR_HANDLER' => null,
    'SHUTDOWN_FUNCTION' => null,
    'ROOT_INDEX_FILE' => "index.php",


    'LOG_LEVEL' => \Monolog\Logger::DEBUG,

    'DEBUG'=> true,
    'LOG_DIR'=> null, //Log和运行中的一些临时文件目录 若不配置，则默认框架初始化
    'SWOOLE_PID_FILE' => 'pid.pid', //进程id存储的文件
    'SWOOLE_LOG_FILE' => 'swoole.log', //swoole log文件

];