<?php
/*   PHP Weathermap 1.0.0
     Copyright Howard Jones, 2005-2018 howie@thingy.com
     http://www.network-weathermap.com/
     PHP Weathermap is licensed under the MIT License, see LICENSE file for more information.

    one file to include all the others...
*/

require_once dirname(__FILE__) . '/Weathermap/Core/globals.php';
require_once dirname(__FILE__) . '/Weathermap/Core/constants.php';

// TODO: This is temporary for dev version
if (!file_exists(dirname(__FILE__) . '/../vendor/autoload.php')) {
    throw new Exception("No autoloader present. Did you read README.md for dev-specific instructions?");
}

require_once dirname(__FILE__) . '/../vendor/autoload.php';

use Weathermap\Core\WMDebugFactory;

// Turn on ALL error reporting for now.
error_reporting(E_ALL | E_STRICT);

$wmDebugLogger = WMDebugFactory::create();
