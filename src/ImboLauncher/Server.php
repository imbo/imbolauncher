<?php
/**
 * This file is part of the ImboLauncher package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboLauncher;

use Symfony\Component\Console\Output\OutputInterface,
    RuntimeException,
    stdClass;

/**
 * Server class
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Application
 */
class Server {
    /**
     * Prefix to the install path
     *
     * @var string
     */
    static public $installPath;

    /**
     * Absolute path to the web server router
     *
     * @var string
     */
    static public $router;

    /**
     * How long time to use until we can connect to a started server, in seconds
     *
     * @var int
     */
    static public $timeout = 2;

    /**
     * Output instance
     *
     * @param OutputInterface
     */
    private $output;

    /**
     * Version
     *
     * @var string
     */
    private $version;

    /**
     * Host name
     *
     * @var string
     */
    private $host;

    /**
     * Port number
     *
     * @var int
     */
    private $port;

    /**
     * Absolute path to the config file
     *
     * @var string
     */
    private $config;

    /**
     * PID of the server
     *
     * @var int
     */
    private $pid;

    /**
     * Class constructor
     *
     * @param string $version The version
     * @param string $host The hostname
     * @param int $port The port number
     * @param string $config The config file
     */
    public function __construct($version, $host, $port, $config) {
        $this->version = $version;
        $this->host = $host;
        $this->port = (int) $port;
        $this->config = $config;
    }

    /**
     * Factory method
     *
     * @param stdClas $config Configuration object
     * @return Server
     */
    public static function create(stdClass $config) {
        return new self($config->version, $config->host, $config->port, $config->config);
    }

    /**
     * Install and start the server
     */
    public function start() {
        if ($this->isConnectable()) {
            throw new RuntimeException(sprintf(
                'Something seems to be running on %s:%d, aborting',
                $this->host,
                $this->port
            ));
        }

        $this->install();
        $this->run();
    }

    /**
     * Kill the server, KILL IT WITH FIRE!!!!
     */
    public function kill() {
        if (!$this->pid) {
            return;
        }

        $this->say(sprintf('Killing server with PID %d...', $this->pid));

        // Command to execute
        $command = sprintf('kill %d', $this->pid);
        $this->shout(sprintf('Executing command: %s', $command));
        exec($command);

        $this->say('Done!');
    }

    /**
     * Get the PID of the server
     *
     * @return int|null
     */
    public function getPid() {
        return $this->pid;
    }

    /**
     * Set an output instance
     *
     * @param OutputInterface $output An output instance
     */
    public function setOutput(OutputInterface $output) {
        $this->output = $output;
    }

    /**
     * Get the complete installation path for this server
     *
     * @return string
     */
    private function getInstallPath() {
        return rtrim(self::$installPath, '/') . '/' . $this->version;
    }

    /**
     * Shout a message
     *
     * @param string $message
     */
    private function shout($message) {
        $this->say($message, OutputInterface::VERBOSITY_VERBOSE);
    }

    /**
     * Write a message
     *
     * @param string $message The message to write
     * @param int $level The verbosity level
     */
    private function say($message, $level = OutputInterface::VERBOSITY_NORMAL) {
        if (!$this->output || $this->output->getVerbosity() < $level) {
            return;
        }

        $this->output->writeln($message);
    }

    /**
     * Is this server connectable?
     *
     * @return boolean True if connectable, false otherwise
     */
    private function isConnectable() {
        set_error_handler(function() { return true; });
        $sp = fsockopen($this->host, $this->port);
        restore_error_handler();

        if ($sp === false) {
            return false;
        }

        fclose($sp);

        return true;
    }

    /**
     * Install the server from GitHub
     */
    private function install() {
        $this->say(sprintf('Installing server (%s)...', $this->version));

        // Command used to create the project using composer
        $command = sprintf(
            'composer create-project -n imbo/imbo %s %s',
            escapeshellarg($this->getInstallPath()),
            escapeshellarg($this->version)
        );
        $this->shout(sprintf('Executing command: %s', $command));
        exec($command);

        // Create a link to the configuration file
        $command = sprintf(
            'ln -s %s %s',
            escapeshellarg($this->config),
            escapeshellarg($this->getInstallPath() . '/config/config.php')
        );
        $this->shout(sprintf('Executing command: %s', $command));
        exec($command);

        $this->say('Done!');
    }

    /**
     * Run the server (host using PHP's web server)
     */
    private function run() {
        $this->say(sprintf('Starting server (%s)...', $this->version));

        // Start the server
        $command = sprintf(
            'php -S %s:%d -t %s %s >/dev/null 2>&1 & echo $!',
            escapeshellarg($this->host),
            $this->port,
            escapeshellarg($this->getInstallPath() . '/public'),
            escapeshellarg(self::$router));

        $this->shout(sprintf('Executing command: %s', $command));
        $output = array();
        exec($command, $output);
        $this->pid = (int) $output[0];
        $this->shout(sprintf('PID %d', $this->pid));

        $start = microtime(true);

        $this->say(sprintf('Trying to connect (%s:%d)...', $this->host, $this->port));

        // Loop until we can connect to the server, or a timeout occurs
        while(!$this->isConnectable() && (microtime(true) - $start) < self::$timeout);

        if (!$this->isConnectable()) {
            throw new RuntimeException(sprintf(
                'Could not connect to %s:%d, aborting',
                $this->host,
                $this->port
            ));
        }

        $this->say('Done!');
    }
}
