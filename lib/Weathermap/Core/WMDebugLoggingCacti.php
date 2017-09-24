<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 24/09/17
 * Time: 12:08
 */

namespace Weathermap\Core;



/**
 * Class WMDebugLoggingCacti - debug logging enabled, and we're running in the Cacti poller.
 */
class WMDebugLoggingCacti extends WMDebugLogging
{
    protected function doLog($message)
    {
        cacti_log($message, true, "WEATHERMAP");
    }
}
