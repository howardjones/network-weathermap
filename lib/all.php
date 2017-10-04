<?php
/*   PHP Weathermap 0.98
     Copyright Howard Jones, 2005-2014 howie@thingy.com
     http://www.network-weathermap.com/
     Released under the GNU Public License

    one file to include all the others...
*/
//
require_once dirname(__FILE__) . '/Weathermap/Core/globals.php';
require_once dirname(__FILE__) . '/Weathermap/Core/constants.php';
require_once dirname(__FILE__) . '/../vendor/autoload.php';

use Weathermap\Core\WMDebugFactory;

// Turn on ALL error reporting for now.
error_reporting(E_ALL | E_STRICT);
// error_reporting(E_ALL);
// error_reporting(E_ALL & ~E_STRICT);

$wmDebugLogger = WMDebugFactory::create();
