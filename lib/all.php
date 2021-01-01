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

use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Weathermap\Core\WMDebugFactory;
use Monolog\Logger;


// Turn on ALL error reporting for now.
error_reporting(E_ALL | E_STRICT);

# TODO - this should vary (Handlers) depending on if we're in CLI, Editor or Cacti
$wmLogger = new Monolog\Logger("weathermap");
$wmLogger->pushProcessor(new IntrospectionProcessor());
$wmLogger->pushProcessor(new MemoryUsageProcessor());
$wmLogger->pushProcessor(new MemoryPeakUsageProcessor());
