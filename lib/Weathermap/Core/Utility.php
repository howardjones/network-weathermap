<?php

namespace Weathermap\Core;


use Weathermap\Core\StringUtility;
use Weathermap\Core\MapUtility;

class Utility
{
    public static function buildMemoryCheckString($note)
    {
        $memUsed = StringUtility::formatNumberWithMetricSuffix(memory_get_usage());
        $memAllowed = ini_get("memory_limit");
        return "$note: memory_get_usage() says " . $memUsed . "Bytes used. Limit is " . $memAllowed . "\n";
    }

    public static function memoryCheck($note = "MEM")
    {
        MapUtility::debug(Utility::buildMemoryCheckString($note));
    }

    public static function testDirectoryWritable($directoryPath)
    {
        if (!is_dir($directoryPath)) {
            MapUtility::warn("Output directory ($directoryPath) doesn't exist!. No maps created. You probably need to create that directory, and make it writable by the poller process (like you did with the RRA directory) [WMPOLL07]\n");
            return false;
        }

        $testfile = $directoryPath . DIRECTORY_SEPARATOR . "weathermap.permissions.test";

        $testfd = fopen($testfile, 'w');
        if ($testfd) {
            fclose($testfd);
            unlink($testfile);
            return true;
        }
        MapUtility::warn("Output directory ($directoryPath) isn't writable (tried to create '$testfile'). No maps created. You probably need to make it writable by the poller process (like you did with the RRA directory) [WMPOLL06]\n");
        return false;
    }

}