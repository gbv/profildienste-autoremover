<?php
namespace Commands;

use DateTime;
use DateInterval;
use Config\Config;
use Services\LogService;
use Services\StatsService;
use Services\MailerService;
use Services\DatabaseService;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteTitlesCommand extends BaseCommand {

    private $config;
    private $databaseService;
    private $statsService;

    public function __construct(LogService $logService, MailerService $mailerService, DatabaseService $databaseService, Config $config, StatsService $statsService) {
        parent::__construct($logService, $mailerService);

        $this->databaseService = $databaseService;
        $this->config = $config;
        $this->statsService = $statsService;
    }

    protected function configure() {
        parent::configure();
        $this->setName('delete:titles')
            ->setDescription('Deletes all rejected titles older than the configured time.');
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output) {

        $this->statsService->recordExecutedStep($this);

        $di = new DateInterval('P'.$this->config->getValue('remove', 'rejected_titles').'D');

        $delete_all_before = (new DateTime())->sub($di);

        $this->log->addInfo('Deleting all titles rejected before ' . date('d.m.Y H:i:s',
                $delete_all_before->getTimestamp()));

        $md = new UTCDateTime($delete_all_before);

        // get all titles older than the specified period of days
        $titles = $this->databaseService->getExpiredRejectedTitles($md);

        $this->log->addInfo('Number of titles to delete: ' . count($titles));

        $backupError = false;

        if ($this->config->getValue('general', 'safe_mode')) {
            foreach ($titles as $title) {
                $this->log->addInfo('Creating backup dump of title ' . $title['_id']);
                $path = $this->config->getValue('dirs', 'deleted', true) . $title['_id'] . '.json';
                if(file_put_contents($path, json_encode($title, JSON_PRETTY_PRINT)) === false) {
                    $this->log->addError('Failed to create a backup of title ' . $title['_id']);
                    $this->statsService->recordSavedTitle(false);
                    $backupError = true;
                } else {
                    $this->statsService->recordSavedTitle();
                }
            }
        }

        // Delete titles if no backup error occurred
        if (!$backupError) {
            $deletedTitles = $this->databaseService->deleteExpiredRejectedTitles($md);
            $this->log->addInfo('Deleted ' . $deletedTitles . ' titles from the database');
            $this->statsService->recordDeletedTitles($deletedTitles);
        } else {
            $this->log->addInfo('An error occurred during the backup phase, so no titles were deleted.');
        }
    }
}