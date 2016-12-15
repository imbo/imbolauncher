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

use ImboLauncher\Command,
    Symfony\Component\Console;

/**
 * Main application class
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Application
 */
class Application extends Console\Application {
    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct('ImboLauncher', 'dev');

        // Register commands
        $this->addCommands([
            new Command\StartServers(),
            new Command\KillServers(),
        ]);
    }
}
