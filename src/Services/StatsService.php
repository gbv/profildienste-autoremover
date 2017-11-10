<?php

namespace Services;


/**
 * Class StatsService
 *
 * This service collects statistics about the total and failed amount of attempted imports per updater.
 *
 * @package Services
 */
class StatsService {

    private $stats = [];

    public function recordDeletedTitle() {
        $this->stats['deleted_titles']++;
    }

    public function recordDeletedBackup() {
        $this->stats['deleted_backups']++;
    }

    public function getStats() {
        return $this->stats;
    }

}