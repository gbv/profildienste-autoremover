<?php

namespace Services;

use Commands\BaseCommand;


/**
 * Class StatsService
 *
 * This service collects statistics about the total and failed amount of attempted imports per updater.
 *
 * @package Services
 */
class StatsService {

    private $stats = [
        'saved_titles' => [
            'success' => 0,
            'fail' => 0,
            'desc' => 'Backed up titles'
        ],
        'deleted_titles' => [
            'success' => 0,
            'fail' => 0,
            'desc' => 'Deleted titles'
        ],
        'deleted_backups' => [
            'success' => 0,
            'fail' => 0,
            'desc' => 'Deleted backups'
        ],
    ];

    private $executedSteps = [];

    public function recordSavedTitle($success = true) {
        $cat = $success ? 'success' : 'fail';
        $this->stats['saved_titles'][$cat]++;
    }

    public function recordSavedTitles($saved, $success = true) {
        $cat = $success ? 'success' : 'fail';
        $this->stats['saved_titles'][$cat] += $saved;
    }

    public function recordDeletedTitle($success = true) {
        $cat = $success ? 'success' : 'fail';
        $this->stats['deleted_titles'][$cat]++;
    }

    public function recordDeletedTitles($deleted, $success = true) {
        $cat = $success ? 'success' : 'fail';
        $this->stats['deleted_titles'][$cat] += $deleted;
    }

    public function recordDeletedBackup($success = true) {
        $cat = $success ? 'success' : 'fail';
        $this->stats['deleted_backups'][$cat]++;
    }

    public function recordDeletedBackups($deleted, $success = true) {
        $cat = $success ? 'success' : 'fail';
        $this->stats['deleted_backups'][$cat] += $deleted;
    }

    public function getStats() {
        return $this->stats;
    }

    public function recordExecutedStep(BaseCommand $cmd) {
        $this->executedSteps[] = $cmd->getName();
    }

    public function getExecutedSteps() {
        return $this->executedSteps;
    }

}