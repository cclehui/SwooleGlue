<?php


define('SWOOLESERVER_ROOT', realpath(getcwd()));

foreach ([__DIR__ . '/../../../autoload.php', __DIR__ . '/../vendor/autoload.php'] as $file) {
    if (file_exists($file)) {
        require_once $file;
        break;
    }
}

$server_manager = \SwooleTool\ServerManager::getInstance();

//环境检测
$server_manager->envCheck();

//sever管理
$server_manager->run();
