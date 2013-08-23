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

use ImboLauncher\Server,
    Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputOption,
    RuntimeException,
    InvalidArgumentException,
    stdClass,
    Json\Validator,
    RecursiveDirectoryIterator,
    RecursiveIteratorIterator;

/**
 * Command used to start one or more servers
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Commands
 */
class StartServers extends Command {
    /**
     * PID's of the started servers
     *
     * @var int[]
     */
    private $pids = array();

    /**
     * Servers
     *
     * @param Server[]
     */
    private $servers = array();

    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct('start-servers');

        $this->setDescription('Start one or more Imbo servers');
        $help = <<<HELP
This command will start one or more Imbo servers based on the configuration file specified with <info>--config</info>. The configuration file uses JSON and should contain an object with a single key: <info>"servers"</info>. The value should be a list of one or more server specifications, each specification an object with the following keys:

<info>"version"</info> The Imbo version. For instance <info>"dev"</info> or <info>"0.3.2"</info>
<info>"host"</info>    The host to use when hosting this version. For instance <info>"localhost"</info>
<info>"port"</info>    The port to use. For instance <info>81</info>
<info>"config"</info>  Path to the custom Imbo configuration file to use for this version. Multiple versions can use the same configuration file. The path should be relative to where you execute the command to start the servers. For instance <info>"configFiles/config.php"</info>

Here is an example of how the config file can look like:

<info>{
  "servers": [
    {
      "version": "dev-develop",
      "host": "localhost",
      "port": 81,
      "config": "configs/config.php"
    },
    {
      "version": "0.3.2",
      "host": "localhost",
      "port": 82,
      "config": "configs/someSpecialConfig.php"
    }
  ]
}</info>

By using this file you will spawn two web servers, one listening on <info>http://localhost:81</info>, running the develop branch of Imbo, and one listening on <info>http://localhost:82</info>, running the <info>0.3.2</info> version of Imbo. Available versions are listed at <info>https://packagist.org/packages/imbo/imbo</info>.
HELP;

        $this->setHelp($help);

        $this->addOption(
            'config',
            null,
            InputOption::VALUE_REQUIRED,
            'Path to JSON configuration file specifying Imbo servers to install'
        );
        $this->addOption(
            'install-path',
            null,
            InputOption::VALUE_REQUIRED,
            'Path to where to install the different Imbo versions. This path will be used as a prefix, and each version will be installed in a separate directory within.'
        );
        $this->addOption(
            'timeout',
            null,
            InputOption::VALUE_REQUIRED,
            'How long to wait until the started servers are connectable, in seconds. Defaults to 2',
            2
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        // Fetch the timeout
        $timeout = (int) $input->getOption('timeout');

        if (!$timeout) {
            throw new InvalidArgumentException('The timeout value must be a positive integer');
        }

        // Fetch the install path
        $installPath = $input->getOption('install-path');

        if (!$installPath) {
            throw new InvalidArgumentException('You need to specify an install path prefix using --install-path');
        }

        $absoluteInstallPath = realpath($installPath);

        if (!$absoluteInstallPath && !is_writable(dirname($installPath))) {
            throw new InvalidArgumentException('Install path does not exist or is not writable: ' . $installPath);
        } else if (!$absoluteInstallPath) {
            mkdir($installPath);
            $absoluteInstallPath = realpath($installPath);
        }

        if ($absoluteInstallPath && !is_writable($absoluteInstallPath)) {
            throw new InvalidArgumentException('Install path exists but is not writable: ' . $absoluteInstallPath);
        }

        // Empty the install path if it contains any files
        if (count(glob($absoluteInstallPath . '/*'))) {
            $dialog = $this->getHelperSet()->get('dialog');

            if (
                !$input->getOption('no-interaction') &&
                !$dialog->askConfirmation($output, $absoluteInstallPath . ' contains files and/or directories. Remove? [Yn]', true)
            ) {
                throw new RuntimeException('ImboLauncher requires the installation path to be empty. Aborting...');
            }

            $this->emptyDir($absoluteInstallPath, $output);
        }

        Server::$timeout = $timeout;
        Server::$installPath = $absoluteInstallPath;
        Server::$router = __DIR__ . '/../../../router.php';

        // Fetch the config option
        $configOption = $input->getOption('config');

        // Is it set?
        if ($configOption === null) {
            throw new InvalidArgumentException('Specify a path to the configuration file using --config');
        }

        // Try to generate an absolute path
        $fullPath = realpath($configOption);

        if (!$fullPath) {
            throw new InvalidArgumentException('Configuration file does not exist: ' . $configOption);
        }

        // Decode the contents
        $config = json_decode(file_get_contents($fullPath));

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Badly formatted configuration file: ' . $fullPath);
        }

        // Validate the configuration
        $this->validateConfiguration($config);

        // Add servers from config
        foreach ($config->servers as $server) {
            // Create a server instance and inject the output instance
            $server = Server::create($server);
            $server->setOutput($output);

            // Add to the pool
            $this->addServer($server);
        }

        // Try to start all servers in the pool
        $this->startServers();
    }

    /**
     * Add a server to the pool
     *
     * @param Server $server A Server instance
     */
    public function addServer(Server $server) {
        $this->servers[] = $server;
    }

    /**
     * Start the servers in the pool
     */
    private function startServers() {
        foreach ($this->servers as $server) {
            $server->start();
            $this->pids[] = $server->getPid();
        }
    }

    /**
     * Validate the configuration object from the --config file
     *
     * @param stdClass $config
     */
    private function validateConfiguration(stdClass $config) {
        // Validate the configuration file using the schema
        $validator = new Validator(__DIR__ . '/../../../config-schema.json');
        $validator->validate($config); // This throws exceptions when errors occur

        // Make sure that none of the ports are busy
        foreach ($config->servers as $server) {
            $absolutePath = realpath($server->config);

            if (!file_exists($absolutePath)) {
                throw new RuntimeException(sprintf('Imbo config file missing: %s', $server->config));
            }

            $server->config = $absolutePath;
        }
    }

    /**
     * Completely remove the contents of a directory (not the directory itself)
     *
     * @param string $dir Name of a directory
     * @param OutputInterface $output An output instance
     */
    private function emptyDir($dir, OutputInterface $output) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $name = $file->getPathname();

            if (substr($name, -1) === '.') {
                continue;
            }

            if ($file->isDir()) {
                // Remove dir
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln('Removing directory: ' . $name);
                }

                rmdir($name);
            } else {
                // Remove file
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                    $output->writeln('Removing directory: ' . $name);
                }
                unlink($name);
            }
        }
    }
}
