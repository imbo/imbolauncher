<?php
/**
 * This file is part of the ImboLauncher package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboLauncherTest;

use ImboLauncher\Application;

/**
 * Application test
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Unit tests
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase {
    /*
     * @covers ImboLauncher\Application::__construct
     */
    public function testCanCreateAnApplicationInstance() {
        $app = new Application();
        $this->assertTrue($app->has('start-servers'));
        $this->assertSame('ImboLauncher', $app->getName());
        $this->assertSame('dev', $app->getVersion());
    }
}
