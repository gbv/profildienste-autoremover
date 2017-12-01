<?php

namespace Commands;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompleteRunCommand extends BaseCommand {

    private $availableSteps = [
        [
            'name' => 'delete-titles',
            'description' => 'Deletes all rejected titles older than the configured time',
            'command' => 'delete:titles'
        ],
        [
            'name' => 'delete-backups',
            'description' => 'Deletes all backups older than the configured time',
            'command' => 'delete:backups'
        ]
    ];

    protected function configure() {
        parent::configure();

        $this->setName('run')
            ->setDescription('Starts the AutoRemover.')
            ->setHelp('Runs either all or some of the specified steps. If no flags are specified, all steps are executed.');

        foreach ($this->availableSteps as $availableStep) {
            $this->addOption($availableStep['name'], null, InputOption::VALUE_NONE ,$availableStep['description']);
        }
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output) {

        $selectedSteps = [];
        foreach ($this->availableSteps as $availableStep) {
            if ($input->hasParameterOption([$availableStep['name'], '--'.$availableStep['name']])) {
                $selectedSteps[] = $availableStep['name'];
            }
        }

        if (count($selectedSteps) === 0) {
            $selectedSteps = array_map(function ($step) {
                return $step['name'];
            }, $this->availableSteps);
        }

        $selectedStepsCommands = array_filter($this->availableSteps, function($step) use ($selectedSteps){
            return in_array($step['name'], $selectedSteps);
        });

        $selectedStepsCommands = array_map(function ($step) {
            return $step['command'];
        }, $selectedStepsCommands);

        foreach ($selectedStepsCommands as $step) {
            $command = $this->getApplication()->find($step);
            $this->log->addInfo('Running ' .  $command->getName());
            $inp = new ArrayInput([
                '--no-mails' => true,
                '--disable-check' => true
            ]);
            $out = $input->hasParameterOption(['--verbose', '-v']) ? $output : new NullOutput();
            $command->run($inp, $out);
        }
    }
}