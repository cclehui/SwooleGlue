<?php

namespace SwooleGlue\Component\Swoole;

class Event extends MultiContainer {
    function add($key, $item) {
        if (is_callable($item)) {
            return parent::add($key, $item);
        } else {
            return false;
        }
    }

    function set($key, $item) {
        if (is_callable($item)) {
            return parent::set($key, $item);
        } else {
            return false;
        }
    }

    public function hook($event, ...$args) {
        $calls = $this->get($event);
        if (is_array($calls)) {
            foreach ($calls as $call) {
                try {
                    Invoker::callUserFunc($call, ...$args);
                } catch (\Throwable $throwable) {
                    Trigger::throwable($throwable);
                }
            }
        }
    }
}