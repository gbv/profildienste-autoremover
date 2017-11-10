<?php

namespace Services;

use Config\CheckResult;
use Config\Config;
use Exception;
use Util\Util;

class ValidatorService {

    private $config;
    private $database;
    private $logService;
    private $mailer;

    private $log;
    private $validLogMails;

    public function __construct(Config $config, DatabaseService $databaseService, LogService $logService, MailerService $mailerService) {
        $this->config = $config;
        $this->database = $databaseService;
        $this->logService = $logService;
        $this->mailer = $mailerService;

        $this->log = $this->logService->getLog();
        $this->validLogMails = [];
    }

    public function checkEnvironment() {

        // check if the AutoRemover is enabled
        if (!$this->config->getValue('general', 'enable')) {
            $this->log->addError('The AutoRemover has been disabled in the configuration file. Please set enable to true to use this tool.');
            return new CheckResult(false, $this->logService->getLoggedErrors());
        }

        $errorsOccurred = false;

        // If this is the first run of the importer, always show errors on console
        // for easier debugging.
        $firstRun = $this->config->getValue('firstrun');
        if ($firstRun) {
            $this->logService->setVerbose();
        }

        $errorsOccurred = !$this->checkDatabaseConnectivity() || $errorsOccurred;

        //check if all dirs exist
        if ($firstRun) {
            $errorsOccurred = !$this->createLocalDirs() || $errorsOccurred;
        } else {
            $errorsOccurred = !$this->checkLocalDirs() || $errorsOccurred;
        }

        // if the log mailing feature is enabled, check that all log mail addresses are valid
        if ($this->config->getValue('logging', 'enable_mail')) {
            $errorsOccurred = !$this->checkLogMailAddresses() || $errorsOccurred;
        }

        return new CheckResult(!$errorsOccurred, $this->logService->getLoggedErrors());
    }

    /**
     * Checks if the importer is able to connect to the database.
     *
     * @return bool true if the database can be accessed
     */
    private function checkDatabaseConnectivity() {
        $this->log->addInfo('Trying to connect to the database...');
        try {
            $this->database->checkConnectivity();
            $this->log->addInfo('Connected!');
        } catch (Exception $e) {
            $this->log->addError('Fail! Reason: ' . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Returns an array with all required local directories.
     * These dirs must exist so that the importer can function properly.
     *
     * @return array
     */
    public function getRequiredDirs() {
        $required =  [
            $this->config->getValue('logging', 'dir')
        ];

        if ($this->config->getValue('general', 'safe_mode')) {
            $required[] = $this->config->getValue('dirs', 'deleted');
        }

        return $required;
    }

    /**
     * Checks if the dirs specified in @see getRequiredDirs() exist. If a directory
     * does not exist (as expected in the first run of the importer), it will be created.
     *
     * @return bool Returns true, if all dirs existed or could be created.
     */
    public function createLocalDirs() {
        $this->log->addInfo("Checking if all local directories exist and create them if possible.");
        $errorsOccurred = false;
        foreach ($this->getRequiredDirs() as $dir) {
            $logMessage = "Checking " . $dir . "... ";
            $checkResult = Util::checkAndCreateDir($dir);
            if (is_null($checkResult)) {
                $logMessage .= "Exists!";
                $this->log->addInfo($logMessage);
            } else if ($checkResult) {
                $logMessage .= "Created!";
                $this->log->addInfo($logMessage);
            } else {
                $logMessage .= "Create failed!";
                $this->log->addError($logMessage);
                $errorsOccurred = true;
            }
        }
        return !$errorsOccurred;
    }

    /**
     * Checks if the dirs specified in @see getRequiredDirs() exist,
     * but will not create them if they don't exist.
     *
     * @return bool true if all exist
     */
    public function checkLocalDirs() {

        $errorsOccurred = false;

        foreach ($this->getRequiredDirs() as $dir) {
            $logMessage = "Checking " . $dir . "... ";
            if (is_dir($dir)) {
                $logMessage .= "Exists!";
                $this->log->addInfo($logMessage);
            } else {
                $logMessage .= "Missing or not a directory!";
                $this->log->addError($logMessage);
                $errorsOccurred = true;
            }
        }
        return !$errorsOccurred;
    }

    /**
     * Checks if all email addresses for the log mailing are valid.
     *
     * @return bool true if all are valid.
     */
    public function checkLogMailAddresses () {
        $errorsOccurred = false;
        $this->log->addInfo("Checking if the log emails are valid...");
        $emails = $this->config->getValue('logging', 'mail');
        foreach ($emails as $email) {
            $logMessage = "\t " . $email . " ... ";
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $logMessage .= "Valid!";
                $this->log->addInfo($logMessage);
                $this->validLogMails[] = $email;
            } else {
                $logMessage .= "Invalid!";
                $this->log->addError($logMessage);
                $errorsOccurred = true;
            }
        }
        return !$errorsOccurred;
    }
}
