<?php


class WMTestSupport
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

    public static function get_map_title($fileName)
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
     * @param string $conffile
     * @param string $imagefile
     * @param string $htmlfile
     * @param string $newconffile
     *
     * @return int number of warnings
     */
    public static function TestOutput_RunTest($conffile, $imagefile, $htmlfile, $newconffile)
    {
        global $WEATHERMAP_VERSION;

        $map = new WeatherMap();

        $map->ReadConfig($conffile);
        $skip = 0;
        $nwarns = 0;

        if (!strstr($WEATHERMAP_VERSION, "dev")) {
            // Allow tests to be from the future. Global SET in test file can exempt test from running
            // SET REQUIRES_VERSION 0.98
            // but don't check if the current version is a dev version
            $required_version = $map->get_hint("REQUIRES_VERSION");

            if ($required_version != "") {
                // doesn't need to be complete, just in the right order
                $known_versions = array(
                    "0.97",
                    "0.97a",
                    "0.97b",
                    "0.97c",
                    "0.98",
                    "0.98a"
                );
                $my_version = array_search($WEATHERMAP_VERSION, $known_versions);
                $req_version = array_search($required_version, $known_versions);
                if ($req_version > $my_version) {
                    $skip = 1;
                    $nwarns = -1;
                }
            }
        }

        if ($skip == 0) {
            $map->readData();
//            $map->preCalculate($map);
            $map->DrawMap($imagefile);
            $map->imagefile = $imagefile;

            if ($htmlfile != '') {
                TestOutput_HTML($htmlfile, $map);
            }
            if ($newconffile != '') {
                $map->WriteConfig($newconffile);
            }
            $nwarns = $map->warncount;
        }

        $map->CleanUp();
        unset($map);

        return intval($nwarns);
    }

}