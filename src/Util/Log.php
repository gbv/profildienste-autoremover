<?php

namespace Util;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Log {

    private static $instance;

    private $log;

    private $logpath;

    private function __construct(){
        $this->log = new Logger('AutoRemove');

        $this->logpath = Config::getInstance()->getValue('logging', 'dir', true).'Remove_'.date('d-m-y_H-i-s').'.log';
        $this->log->pushHandler(new StreamHandler($this->logpath, Logger::INFO));
    }

    public static function getInstance(){
        if(is_null(Log::$instance)){
            Log::$instance = new Log();
        }

        return Log::$instance;
    }

    public function getLog(){
        return $this->log;
    }

    public function getLogPath(){
        return $this->logpath;
    }

}