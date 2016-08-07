<?php

namespace Lang\ZenGM\Console\Command;

use Lang\ZenGM;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;

class Export extends SymfonyCommand {

    /**
     * Configure the command
     * @return void
     */
    protected function configure() {
        $this
            ->setName('export')
            ->setDescription('Exports all properties')
            ->setHelp('Looks in the ZenGM JSON file and exports all properties')
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the ZenGM export');
    }

    /**
     * Execute the command and print any output
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $commands = array('export:teams', 'export:players');

        // create a new progress bar (50 units)
        $progress = new ProgressBar($output, sizeof($commands));

        // start and displays the progress bar
        $progress->start();

        foreach ($commands as $commandName) {
            $command = $this->getApplication()->find($commandName);

            $arguments = new ArrayInput(array(
                'file' => $input->getArgument('file')
            ));
            $command->run($arguments, $output);
            $progress->advance();
        }

        // ensure that the progress bar is at 100%
        $progress->finish();
        $output->writeln("");
    }
}
