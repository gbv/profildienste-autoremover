<?php

namespace Services;

use Config\Config;
use Monolog\Logger;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Util\Util;

class MailerService {

    /**
     * @var LogService
     */
    private $logService;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StatsService
     */
    private $statsService;

    /**
     * @var Logger
     */
    private $log;

    private $twig;

    public function __construct(LogService $logService, Config $config, StatsService $statsService, string $resFolder) {
        $this->logService = $logService;
        $this->config = $config;
        $this->statsService = $statsService;

        $this->log = $this->logService->getLog();

        $loader = new Twig_Loader_Filesystem($resFolder);
        $this->twig = new Twig_Environment($loader);
    }

    public function sendReportMail() {

        $stats = $this->statsService->getStats();

        $total = array_reduce(array_values($stats), function ($carry, $stat){
            return $carry + $stat['success'] + $stat['fail'];
        }, 0);

        if ($total > 0) {

            $failed = array_reduce(array_values($stats), function ($carry, $stat){
                return $carry + $stat['fail'];
            }, 0);

            $template = $this->twig->load('templates/reportMail.twig');

            $mail = new Message;
            $mail->setFrom('Profildienst AutoRemover <noreply@online-profildienst.gbv.de>')
                ->setSubject('Profildienst AutoRemover Report')
                ->setHtmlBody($template->render([
                    'failedTitles' => $failed,
                    'stepList' => $this->statsService->getExecutedSteps(),
                    'stats' => $stats
                ]));

            $emails = $this->config->getValue('logging', 'mail');
            foreach ($emails as $email) {
                $mail->addTo($email);
            }

            $mailer = new SendmailMailer;
            $mailer->send($mail);

        } else {
            $this->log->addInfo('No email sent since nothing happened.');
        }
    }
}