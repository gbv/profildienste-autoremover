<?php
namespace Commands;


use DateTime;
use DateInterval;
use Config\Config;
use Services\LogService;
use Services\StatsService;
use Services\MailerService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteBackupsCommand extends BaseCommand {

    private $config;
    private $statsService;

    public function __construct(LogService $logService, MailerService $mailerService, Config $config, StatsService $statsService) {
        parent::__construct($logService, $mailerService);

        $this->config = $config;
        $this->statsService = $statsService;
    }

    protected function configure() {
        parent::configure();
        $this->setName('delete:backups')
            ->setDescription('Deletes all backups older than the configured time.');
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output) {

        $this->statsService->recordExecutedStep($this);

        $di = new DateInterval('P' . $this->config->getValue('remove', 'backups') . 'D');

        $delete_all_before = (new DateTime())->sub($di);

        $this->log->addInfo('Deleting all backups created before ' . date('d.m.Y H:i:s',
                $delete_all_before->getTimestamp()));

        $deleteTimestamp = $delete_all_before->getTimestamp();

        $backupDirPath = $this->config->getValue('dirs', 'deleted', true);
        $backupDir = opendir($backupDirPath);
        if ($backupDir) {
            while (false !== ($f = readdir($backupDir))) {
                if($f !== '.' && $f !== '..'){

                    $filePath =  $backupDirPath . $f;

                    // delete the file if the timestamp of the last modification
                    // is older than the specified period of days for backups
                    if (filemtime($filePath) <= $deleteTimestamp){
                        $unlinkResult = unlink($filePath);
                        if ($unlinkResult){
                            $this->log->addInfo('Deleted backup ' . $filePath);
                        }else{
                            $this->log->addError('Could not delete ' . $filePath);
                        }
                        $this->statsService->recordDeletedBackup($unlinkResult);
                    }
                }
            }
        } else {
            $this->log->addError('Could not open backup dir ');
        }
    }
}