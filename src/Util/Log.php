<?php

namespace Util;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class Log
 * @package Util
 *
 * Utility class providing a singleton wrapper for the logger.
 */
class Log {

    private static $instance;

    /**
     * @var Logger Logger Instance of a logger
     */
    private $log;

    /**
     * @var string path of the logfile
     */
    private $logpath;

    private function __construct(){

        // generate a new logger
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

    /**
     * Getter for the Logger instance
     *
     * @return Logger
     */
    public function getLog(){
        return $this->log;
    }

    /**
     * Getter for the path of the log file
     *
     * @return string
     */
    public function getLogPath(){
        return $this->logpath;
    }

}