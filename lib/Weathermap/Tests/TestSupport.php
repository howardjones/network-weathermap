<?php

namespace Weathermap\Tests;

use Weathermap\Core\Map;
use Weathermap\Core\MapUtility;

class TestSupport
{

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public static function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public static function getMapTitle($fileName)
    {
        $title = "";
        $fileHandle = fopen($fileName, "r");
        if ($fileHandle) {
            while (!feof($fileHandle)) {
                $buffer = fgets($fileHandle, 1024);

                if (preg_match('/^\s*TITLE\s+(.*)/i', $buffer, $matches)) {
                    $title = $matches[1];
                    break;
                }
            }

            fclose($fileHandle);
        }
        return $title;
    }

    /**
     * Run a config-based test.
     * Read in config from $conffile, and produce an image and HTML output
     * Optionally Produce a new config file in $newconffile (for testing WriteConfig)
     * Optionally collect config-keyword-coverage stats about this config file
     *
     * From: https://jtreminio.com/2013/03/unit-testing-tutorial-part-3-testing-protected-private-methods-coverage-reports-and-crap/
     *
     * @param string $iconFileName
     * @param string $imageFileName
     * @param string $htmlFileName
     * @param string $newConfigFileName
     *
     * @return int number of warnings
     */
    public static function runOutputTest($iconFileName, $imageFileName, $htmlFileName, $newConfigFileName)
    {
        $map = new Map();

        $map->readConfig($iconFileName);
        $skip = 0;
        $numWarnings = 0;

        if (!strstr(WEATHERMAP_VERSION, "dev")) {
            // Allow tests to be from the future. Global SET in test file can exempt test from running
            // SET REQUIRES_VERSION 0.98
            // but don't check if the current version is a dev version
            $requiredVersion = $map->getHint("REQUIRES_VERSION");

            if ($requiredVersion != "") {
                // doesn't need to be complete, just in the right order
                $knownVersions = array(
                    "0.97",
                    "0.97a",
                    "0.97b",
                    "0.97c",
                    "0.98",
                    "0.98a",
                    "1.0.0"
                );
                $myVersionIndex = array_search(WEATHERMAP_VERSION, $knownVersions);
                $requiredVersionIndex = array_search($requiredVersion, $knownVersions);
                if ($requiredVersionIndex > $myVersionIndex) {
                    $skip = 1;
                    $numWarnings = -1;
                }
            }
        }

        if ($skip == 0) {
            $map->readData();
//            $map->preCalculate($map);
            $map->drawMap($imageFileName);
            $map->imagefile = $imageFileName;

            if ($htmlFileName != '') {
                MapUtility::TestOutput_HTML($htmlFileName, $map);
            }
            if ($newConfigFileName != '') {
                $map->writeConfig($newConfigFileName);
            }
            $numWarnings = $map->warncount;
        }

        $map->cleanUp();
        unset($map);

        return intval($numWarnings);
    }
}
