<?php

namespace Weathermap\Core;

/**
 * utility functions, mainly for poller
 */
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


    public static function testCronPart($value, $checkstring)
    {
        if ($checkstring == '*') {
            return true;
        }
        if ($checkstring == $value) {
            return true;
        }

        $value = intval($value);

        // Cron allows for multiple comma separated clauses, so let's break them
        // up first, and evaluate each one.
        $parts = explode(",", $checkstring);

        foreach ($parts as $part) {
            // just a number
            if ($part === $value) {
                return true;
            }

            // an interval - e.g. */5
            if (1 === preg_match('/\*\/(\d+)/', $part, $matches)) {
                $mod = intval($matches[1]);

                if (($value % $mod) === 0) {
                    return true;
                }
            }

            // a range - e.g. 4-7
            if (1 === preg_match('/(\d+)\-(\d+)/', $part, $matches)) {
                if (($value >= intval($matches[1])) && ($value <= intval($matches[2]))) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function checkCronString($time, $string)
    {
        if ($string == '' || $string == '*' || $string == '* * * * *') {
            return true;
        }

        $localTime = localtime($time, true);
        list($minute, $hour, $wday, $day, $month) = preg_split('/\s+/', $string);

        $matched = true;

        $matched = $matched && Utility::testCronPart($localTime['tm_min'], $minute);
        $matched = $matched && Utility::testCronPart($localTime['tm_hour'], $hour);
        $matched = $matched && Utility::testCronPart($localTime['tm_wday'], $wday);
        $matched = $matched && Utility::testCronPart($localTime['tm_mday'], $day);
        $matched = $matched && Utility::testCronPart($localTime['tm_mon'] + 1, $month);

        return $matched;
    }
}
