<?php

namespace Lang\ZenGM\Console\Command;

use Lang\ZenGM;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;

class Teams extends SymfonyCommand {

    /**
     * Configure the command
     * @return void
     */
    protected function configure() {
        $this
            ->setName('export:teams')
            ->setDescription('Exports teams')
            ->setHelp('Looks in the ZenGM JSON file and exports the teams')
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the ZenGM export');
    }

    /**
     * Execute the command and print any output
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        try {
            $z = new ZenGM($input->getArgument('file'));
            $z->exportTeams();
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
