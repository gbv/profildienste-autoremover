<?php

namespace Services;

use Config\Config;
use Monolog\Handler\NullHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogService {

    private $log;
    private $logPath;

    private $verbose = false;

    private $errors = [];

    public function __construct(Config $config) {

        $this->log = new Logger('AutoRemover');

        $this->log->pushProcessor(function ($record) {
            if ($record['level'] >= Logger::ERROR) {
                $this->errors[] = $record['message'];
            }
            return $record;
        });

        if (is_dir($config->getValue('logging', 'dir', true))) {
            $this->logPath = $config->getValue('logging', 'dir', true) . 'AutoRemover';
            $handler = new RotatingFileHandler($this->logPath, 7 ,Logger::INFO);
            $this->log->pushHandler($handler);
        } else {
            $this->setVerbose();
        }

    }

    public function setVerbose() {
        // add the verbose logger if none has been set so far
        if (!$this->verbose) {
            $this->log->pushHandler(new StreamHandler(STDOUT, Logger::INFO));
            $this->log->pushHandler(new StreamHandler(STDERR, Logger::ERROR, false));
        }

        $this->verbose = true;
    }

    /**
     * Stops the logger from logging anything.
     * This is especially useful for the unit tests.
     */
    public function setQuiet() {
        $this->log->pushHandler(new NullHandler());
    }

    public function getLog() {
        return $this->log;
    }

    public function getLogPath() {
        return $this->logPath;
    }

    public function getLoggedErrors() {
        return $this->errors;
    }
}