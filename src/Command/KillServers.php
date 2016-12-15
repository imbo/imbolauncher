<?php
/**
 * This file is part of the ImboLauncher package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboLauncher\Command;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Question\ConfirmationQuestion,
    RuntimeException,
    InvalidArgumentException;

/**
 * Command used to kill PID's located in the pids file
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Commands
 */
class KillServers extends Command {
    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct('kill-servers');

        $this->setDescription('Kill servers based on PID\'s located in a file');

        $this->addOption(
            'pid-file',
            null,
            InputOption::VALUE_REQUIRED,
            'File that holds the PID\'s. Defaults to /tmp/imbolauncher-pids',
            '/tmp/imbolauncher-pids'
        );
    }

    /**
     * Execute the command
     *
     * @see Symfony\Components\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $pidFile = $input->getOption('pid-file');

        if (!file_exists($pidFile)) {
            throw new InvalidArgumentException(sprintf('File does not exist: %s', $pidFile));
        }

        $pids = explode(',', file_get_contents($pidFile));
        $commands = [];

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln('Fetching information about the PID\'s');
        }

        foreach ($pids as $pid) {
            $pid = (int) trim($pid);
            $result = [];

            $command = sprintf('ps -o command %d', $pid);

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                $output->writeln('Executing command: ' . $command);
            }

            exec($command, $result);

            if (!isset($result[1])) {
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln(sprintf('PID does not exist: %d', $pid));
                }

                continue;
            }

            $commands[$pid] = $result[1];
        }

        if (!$commands) {
            unlink($pidFile);
            throw new RuntimeException(sprintf(
                'The PID\'s listed in %s does not exist. The file has been deleted',
                $pidFile
            ));
        }

        $helper = $this->getHelperSet()->get('question');

        $question = 'You are about to kill the following processes:' . PHP_EOL . PHP_EOL;

        foreach ($commands as $pid => $command) {
            $question .= sprintf('%d: %s', $pid, $command) . PHP_EOL;
        }

        $question .= PHP_EOL . 'Continue? [Yn] ';
        $confirmation = new ConfirmationQuestion($question, true);

        if (
            !$input->getOption('no-interaction') &&
            !$helper->ask($input, $output, $confirmation)
        ) {
            return;
        }

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln('Killing PID\'s');
        }

        $command = sprintf('kill %s', implode(' ', array_keys($commands)));

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $output->writeln(sprintf('Executing command: %s', $command));
        }

        exec($command);
        unlink($pidFile);
    }
}
