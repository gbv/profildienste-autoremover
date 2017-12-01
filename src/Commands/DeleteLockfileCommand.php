<?php
namespace Commands;


use Config\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeleteLockfileCommand extends Command {

    protected function configure() {
        $this->setName('delete-lockfile')
            ->setDescription('Delete the lockfile manually (use with caution!)');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        // Check if a lockfile exists
        if (!file_exists(Config::getLockFilePath())) {
            $output->writeln('<error>There is no lockfile to delete.</error>');
            return;
        }

        $output->writeln('<comment>Please make sure that there is no active step before deleting the lockfile.</comment>');

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure that you want to delete the lockfile (Y|n)?', false);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Aborted.</info>');
            return;
        } else {
            if (unlink(Config::getLockFilePath())) {
                $output->writeln('<info>Lockfile removed.</info>');
            } else {
                $output->writeln('<error>Failed to delete the lockfile!</error>');
            }
        }


    }
}