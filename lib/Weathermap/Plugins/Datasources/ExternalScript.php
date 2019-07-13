<?php

namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\MapUtility;

// Run an external 'MRTG-compatible' script, and return it's values
// TARGET !/usr/local/bin/qmailmrtg7 t /var/log/qmail

// MRTG Target scripts return 4 lines of text as output:
//    'Input' value - interpreted as a byte count (so multiplied by 8)
//    'Output' value - interpreted as a byte count (so multiplied by 8)
//    'uptime' as a string
//    'name of targer' as a string
// we ignore the last two

// NOTE: Obviously, if you allow anyone to create maps, you are
//       allowing them to run ANY COMMAND as the user that runs 
//       weathermap, by using this plugin. This might not be a 
//       good thing.

//       If you want to allow only one command, consider making
//       your own datasource plugin which only runs that one command.

/**
 * Get data from an external script. Disabled by default.
 */
class ExternalScript extends Base
{
    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^!(.*)$/'
        );

        $this->name = "ExternalScript";
    }

    public function ReadData($targetString, &$map, &$mapItem)
    {
        // By default, fail.
        // Remove these 4 lines ONLY if you understand the risks, and have taken appropriate measures
        // so that users (or the public) can't access your map editor! Otherwise they can run
        // arbitrary scripts on your Weathermap server.

        if (!array_key_exists("WM_TESTS_RUNNING", $_ENV)) {
            MapUtility::warn("ExternalScript targets are currently disabled");
            return $this->returnData();
        }

        if (preg_match("/^!(.*)$/", $targetString, $matches)) {
            $command = $matches[1];
            $lines = array();

            MapUtility::debug("ExternalScript ReadData: Running $command\n");
            // run the command here
            if (($pipe = popen($command, "r")) === false) {
                MapUtility::warn("ExternalScript ReadData: Failed to run external script. [WMEXT01]\n");
            } else {
                $i = 0;
                while (($i < 5) && !feof($pipe)) {
                    $lines[$i++] = rtrim(fgets($pipe, 1024));
                }
                pclose($pipe);

                if ($i == 5) {
                    $this->data[IN] = floatval($lines[0]);
                    $this->data[OUT] = floatval($lines[1]);

                    $mapItem->addHint("external_line1", $lines[0]);
                    $mapItem->addHint("external_line2", $lines[1]);
                    $mapItem->addHint("external_line3", $lines[2]);
                    $mapItem->addHint("external_line4", $lines[3]);
                    $this->dataTime = time();
                } else {
                    MapUtility::warn("ExternalScript ReadData: Not enough lines read from external script ($i read, 4 expected) [WMEXT02]\n");
                }
            }
        }


        return $this->returnData();
    }
}

// vim:ts=4:sw=4:
