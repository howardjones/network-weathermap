<?php
/**
 * A replacement for the old wm_debug trace functions littered through the code, so that in non-debug
 * mode, the function call is just a stub, with no checking required. There are enough of these that
 * this should make non-debug mode a bit faster, and also allow for more elaborate things to go one in
 * debug mode.
 */
namespace Weathermap\Core;

/**
 * Class WMDebugFactory - pick which of the debug-logging classes to use. We call wm_debug() thousands of
 * times in a map run, so having the minimum possible decision-making done inside it is useful, especially when
 * it's the same decision each time! (e.g. function_exists() )
 */
class WMDebugFactory
{
    public static function update($newStatus)
    {
        global $weathermap_debugging;

        $weathermap_debugging = $newStatus;

        return self::create();
    }

    public static function create()
    {
        global $weathermap_debugging_readdata;
        global $weathermap_debugging;
        global $weathermap_map;

        if ($weathermap_debugging) {
            if (function_exists('debug_log_insert') && (!function_exists('wmeShowStartPage'))) {
                return new WMDebugLoggingCacti($weathermap_map, $weathermap_debugging_readdata);
            }
            return new WMDebugLogging($weathermap_map, $weathermap_debugging_readdata);
        }
        return new WMDebugNull($weathermap_map, $weathermap_debugging_readdata);
    }
}
