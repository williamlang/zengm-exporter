<?php

namespace Lang\ZenGM\Console\Command;

use Lang\ZenGM;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;

class Players extends SymfonyCommand {

    /**
     * Configure the command
     * @return void
     */
    protected function configure() {
        $this
            ->setName('export:players')
            ->setDescription('Exports players')
            ->setHelp('Looks in the ZenGM JSON file and exports the players')
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
            $z->exportPlayers();
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
