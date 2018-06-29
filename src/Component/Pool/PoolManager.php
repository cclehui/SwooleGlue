<?php

namespace SwooleGlue\Component\Pool;

use SwooleGlue\Component\Config\ConfigUtil;
use SwooleGlue\AbstractInterface\Singleton;
use SwooleGlue\Component\Swoole\TableManager;
use Swoole\Table;

class PoolManager {
    use Singleton;

    private $poolTable = null;
    private $poolClassList = [];
    private $poolObjectList = [];

    const TYPE_ONLY_WORKER = 1;
    const TYPE_ONLY_TASK_WORKER = 2;
    const TYPE_ALL_WORKER = 3;

    function __construct() {
        TableManager::getInstance()->add('__PoolManager', ['createNum' => ['type' => Table::TYPE_INT, 'size' => 3]], 8192);
        $this->poolTable = TableManager::getInstance()->get('__PoolManager');

        $conf = ConfigUtil::getInstance()->getConf('POOL_MANAGER');
        if (is_array($conf)) {
            foreach ($conf as $class => $item) {
                $this->registerPool($class, $item['min'], $item['max'], $item['type']);
            }
        }
    }

    function registerPool(string $class, $minNum, $maxNum, $type = self::TYPE_ONLY_WORKER) {
        $ref = new \ReflectionClass($class);
        if ($ref->isSubclassOf(Pool::class)) {
            $this->poolClassList[$class] = ['min' => $minNum, 'max' => $maxNum, 'type' => $type];
            return true;
        } else {
            throw new \Exception($class . ' is not Pool class');
        }

        return false;
    }

    function getPool(string $class): ?Pool {
        if (isset($this->poolObjectList[$class])) {
            return $this->poolObjectList[$class];
        } else {
            return null;
        }
    }

    /*
     * 为自定义进程预留
     */
    function __workerStartHook($workerId) {
        $workerNum = ConfigUtil::getInstance()->getConf('MAIN_SERVER.SETTING.worker_num');
        foreach ($this->poolClassList as $class => $item) {
            if ($item['type'] === self::TYPE_ONLY_WORKER) {
                if ($workerId > ($workerNum - 1)) {
                    continue;
                }
            } else if ($item['type'] === self::TYPE_ONLY_TASK_WORKER) {
                if ($workerId <= ($workerNum - 1)) {
                    continue;
                }
            }
            $key = self::generateTableKey($class, $workerId);
            $this->poolTable->del($key);
            $this->poolObjectList[$class] = new $class($item['min'], $item['max'], $key);
        }
    }


    function getPoolTable() {
        return $this->poolTable;
    }

    public static function generateTableKey(string $class, int $workerId): string {
        return substr(md5($class . $workerId), 8, 16);
    }

}