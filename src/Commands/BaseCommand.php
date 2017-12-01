<?php


namespace Commands;


use Exception;
use Services\LogService;
use Services\MailerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command {

    private $sendMails = true;

    protected $logService;
    protected $mailerService;

    protected $log;

    public function __construct(LogService $logService, MailerService $mailerService) {
        parent::__construct();
        $this->logService = $logService;
        $this->mailerService = $mailerService;

        $this->log = $this->logService->getLog();
    }

    protected function configure() {
        $this->addOption('no-mails', null, InputOption::VALUE_NONE, 'If this flag is set, no mails will be sent');
        $this->addOption('disable-check', null, InputOption::VALUE_NONE, 'If this flag is set, no initial environment check will be executed.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        if ($input->hasParameterOption(['--verbose', '-v'])) {
            $this->logService->setVerbose();
        } else if ($input->hasParameterOption(['--quiet', '-q'])) {
            $this->logService->setQuiet();
        }

        if ($input->hasParameterOption(['--no-mails', 'no-mails'])) {
            $this->sendMails = false;
        }

        $doCheck = true;
        if ($input->hasParameterOption(['--disable-check', 'disable-check'])) {
            $doCheck = false;
        }

        // check
        if ($doCheck) {
            $command = $this->getApplication()->find('config:check');
            $inp = new ArrayInput([]);
            $out = $input->hasParameterOption(['--verbose', '-v']) ? $output : new NullOutput();

            $returnCode = $command->run($inp, $out);

            if ($returnCode !== 0) {
                throw new Exception('The configuration and/or the environment is not set up properly! Please check the logs and try again.');
            }
        }

        $this->executeCommand($input, $output);

        if ($this->sendMails) {
            $this->mailerService->sendReportMail();
        }
    }

    abstract protected function executeCommand(InputInterface $input, OutputInterface $output);
}