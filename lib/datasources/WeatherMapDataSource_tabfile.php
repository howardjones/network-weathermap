<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:databasename:username:pass:hostkey

class WeatherMapDataSource_tabfile extends WeatherMapDataSource
{

    function Recognise($targetString)
    {
        if (preg_match('/\.(tsv|txt)$/', $targetString)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $targetString The string from the config file
     * @param the $map A reference to the map object (redundant)
     * @param the $mapItem A reference to the object this target is attached to
     * @return array invalue, outvalue, unix timestamp that the data was valid
     */
    function ReadData($targetString, &$map, &$mapItem)
    {
        $data[IN] = null;
        $data[OUT] = null;
        $dataTime = 0;

        $itemName = $mapItem->name;

        $fullpath = realpath($targetString);

        wm_debug("Opening $fullpath\n");

        if (! file_exists($fullpath)) {
            wm_warn("File '$fullpath' doesn't exist.");
            return array(null, null, null);
        }

        if (! is_readable($fullpath)) {
            wm_warn("File '$fullpath' isn't readable.");
            return array(null, null, null);
        }

        $fileHandle=fopen($fullpath, "r");

        if ($fileHandle) {
            while (!feof($fileHandle)) {
                $buffer=fgets($fileHandle, 4096);
                # strip out any line-endings that have gotten in here
                $buffer=str_replace("\r", "", $buffer);
                $buffer=str_replace("\n", "", $buffer);

                $parts = explode("\t", $buffer);

                if ($parts[0] == $itemName) {
                    $data[IN] = ($parts[1]=="-" ? null : wmInterpretNumberWithMetricPrefix($parts[1]) );
                    $data[OUT] = ($parts[2]=="-" ? null : wmInterpretNumberWithMetricPrefix($parts[2]) );
                }
            }
            $stats = stat($targetString);
            $dataTime = $stats['mtime'];
        } else {
            // some error code to go in here
            wm_warn("TabText ReadData: Couldn't open ($targetString). [WMTABDATA01]\n");
        }

        wm_debug("TabText ReadData: Returning (".($data[IN]===null ? 'null' : $data[IN]) . "," . ($data[OUT]===null ? 'null' : $data[OUT]).",$dataTime)\n");

        return( array($data[IN], $data[OUT], $dataTime) );
    }
}

// vim:ts=4:sw=4:
