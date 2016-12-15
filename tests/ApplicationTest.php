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

use PHPUnit_Framework_TestCase;

/**
 * Application test
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Unit tests
 * @coversDefaultClass ImboLauncher\Application
 */
class ApplicationTest extends PHPUnit_Framework_TestCase {
    /*
     * @covers ::__construct
     */
    public function testCanCreateAnApplicationInstance() {
        $app = new Application();
        $this->assertTrue($app->has('start-servers'));
        $this->assertSame('ImboLauncher', $app->getName());
        $this->assertSame('dev', $app->getVersion());
    }
}
