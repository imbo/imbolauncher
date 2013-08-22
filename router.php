<?php
/**
 * This file is part of the ImboLauncher package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

if (file_exists($_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'])) {
    // The file exists, serve the file as is
    return false;
}

// Imbo uses SCRIPT_FILENAME for path resolution, so set that to the expected value
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . '/index.php';

require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
