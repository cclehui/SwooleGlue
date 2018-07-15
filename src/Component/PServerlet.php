<?php

namespace SwooleGlue\Component;


use SwooleGlue\AbstractInterface\Singleton;

abstract class PServerlet {

    use Singleton;

    protected function __construct() {
        //init
        $this->init();
    }

    protected function init() {

    }

    protected function beforeRequest() {

    }

    protected function afterRequest() {

    }

    public function doRequest() {
        $this->beforeRequest();

        $this->doService();

        $this->afterRequest();

    }

    abstract public function doService();


}