<?php

namespace SwooleGlue\Component\Swoole;


use SwooleGlue\Component\Enviroment;
use SwooleGlue\Component\Invoker;
use SwooleGlue\Component\Config\ConfigUtil;
use SwooleGlue\Component\Pool\PoolManager;

class SwooleServer {

    private static $instance;
    private $serverList = [];
    private $mainServer = null;
    private $isStart = false;

    const TYPE_SERVER = 1;
    const TYPE_WEB_SERVER = 2;
    const TYPE_WEB_SOCKET_SERVER = 3;

    protected function __construct() {
    }

    public static function getInstance(): SwooleServer {
        if (!isset(self::$instance)) {
            self::$instance = new SwooleServer();
        }
        return SwooleServer::$instance;
    }

    public function addServer(string $serverName, int $port, int $type = SWOOLE_TCP, string $host = '0.0.0.0', array $setting = ["open_eof_check" => false,]): EventRegister {
        $eventRegister = new EventRegister();
        $this->serverList[$serverName] = ['port' => $port, 'host' => $host, 'type' => $type, 'setting' => $setting, 'eventRegister' => $eventRegister];
        return $eventRegister;
    }

    public function isStart(): bool {
        return $this->isStart;
    }

    public function start() {

        if (!$this->isStart) {
            Enviroment::init();
        }

        $this->createMainServer();
        $this->attachListener();
        $this->isStart = true;
        $this->getServer()->start();
    }


    private function attachListener() {
        $mainServer = $this->getServer();
        foreach ($this->serverList as $serverName => $server) {
            $subPort = $mainServer->addlistener($server['host'], $server['port'], $server['type']);
            if ($subPort) {
                $this->serverList[$serverName] = $subPort;
                if (is_array($server['setting'])) {
                    $subPort->set($server['setting']);
                }
                $events = $server['eventRegister']->all();
                foreach ($events as $event => $callback) {
                    $subPort->on($event, function () use ($callback) {
                        $ret = [];
                        $args = func_get_args();
                        foreach ($callback as $item) {
                            array_push($ret, Invoker::callUserFuncArray($item, $args));
                        }
                        if (count($ret) > 1) {
                            return $ret;
                        }
                        return array_shift($ret);
                    });
                }
            } else {
                throw new \Exception("addListener with server name:{$serverName} at host:{$server['host']} port:{$server['port']} fail");
            }
        }
    }

    private function createMainServer(): \swoole_server {
        $conf = ConfigUtil::getInstance()->getConf("MAIN_SERVER");
        $runModel = $conf['RUN_MODEL'];
        $host = $conf['HOST'];
        $port = $conf['PORT'];
        $setting = $conf['SETTING'];
        $sockType = $conf['SOCK_TYPE'];
        switch ($conf['SERVER_TYPE']) {
            case self::TYPE_SERVER:
                $this->mainServer = new \swoole_server($host, $port, $runModel, $sockType);
                break;
            case self::TYPE_WEB_SERVER:
                $this->mainServer = new \swoole_http_server($host, $port, $runModel, $sockType);
                break;
            case self::TYPE_WEB_SOCKET_SERVER:
                $this->mainServer = new \swoole_websocket_server($host, $port, $runModel, $sockType);
                break;
            default:
                throw new \Exception("unknown server type :{$conf['SERVER_TYPE']}");
        }

        $this->mainServer->set($setting);

        //创建默认的事件注册器
        $register = new EventRegister();

        //注册时间处理函数
        $register = $this->setEeventHandler($register);
        $events = $register->all();

        foreach ($events as $event => $callback) {
            $this->mainServer->on($event, function () use ($callback) {
                $ret = [];
                $args = func_get_args();

                foreach ($callback as $item) {
                    array_push($ret, Invoker::callUserFuncArray($item, $args));
                }
                if (count($ret) > 1) {
                    return $ret;
                }
                return array_shift($ret);
            });
        }
        return $this->mainServer;
    }

    public function getServer($serverName = null): \swoole_server {
        if ($this->mainServer) {
            if ($serverName === null) {
                return $this->mainServer;
            } else {
                if (isset($this->serverList[$serverName])) {
                    return $this->serverList[$serverName];
                }

                throw new \Exception("server $serverName not exists");
            }
        } else {
            throw new \Exception("server $serverName not exists, mainServer not exists");
        }
    }


    private function setEeventHandler(EventRegister $register) {
        //实例化对象池管理
        PoolManager::getInstance();
        $register->add($register::onWorkerStart, function (\swoole_server $server, int $workerId) {
            PoolManager::getInstance()->__workerStartHook($workerId);
            $workerNum = ConfigUtil::getInstance()->getConf('MAIN_SERVER.SETTING.worker_num');
            $name = ConfigUtil::getInstance()->getConf('SERVER_NAME');
            if (PHP_OS != 'Darwin') {
                if ($workerId <= ($workerNum - 1)) {
                    $name = "{$name}_Worker_" . $workerId;
                } else {
                    $name = "{$name}_Task_Worker_" . $workerId;
                }
                cli_set_process_title($name);
            }
        });

//        EventHelper::registerDefaultOnTask($register);
//        EventHelper::registerDefaultOnFinish($register);
//        EventHelper::registerDefaultOnPipeMessage($register);
        $conf = ConfigUtil::getInstance()->getConf("MAIN_SERVER");
        if ($conf['SERVER_TYPE'] == self::TYPE_WEB_SERVER
            || $conf['SERVER_TYPE'] == self::TYPE_WEB_SOCKET_SERVER) {
            if (!$register->get($register::onRequest)) {
                EventHelper::registerDefaultOnRequest($register);
            }
        }

        return $register;
    }
}