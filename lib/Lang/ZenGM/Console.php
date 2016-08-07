<?php

namespace Lang\ZenGM;

use Symfony\Component\Console\Application;

class Console {

    const COMMAND_NAMESPACE = 'Lang\\ZenGM\\Console\\Command\\';
    const COMMAND_DIR = 'Console/Command';

    /**
     * Console Application
     * @var Application
     */
    private $application;

    /**
     * Create the ZenGM Console
     */
    public function __construct() {
        $this->application = new Application('ZenGM');
        $this->register();
    }

    /**
     * Access the run method on the Symfony Console Application
     */
    public function run() {
        $this->application->run();
    }

    /**
     * Using Reflection grab all the Commands from COMMAND_NAMESPACE and add to the Console application
     */
    private function register() {
        $commandDir = sprintf("%s/%s/", __DIR__, self::COMMAND_DIR);

        if (is_dir($commandDir)) {
            $handle = opendir($commandDir);

            while (false !== ($entry = readdir($handle))) {
                $className = str_replace('.php', '', $entry);

                if (preg_match('/[A-Za-z]+/', $className)) {
                    $className = sprintf("%s%s", self::COMMAND_NAMESPACE, $className);
                    $this->application->add(new $className());
                }
            }
        }
    }
}
