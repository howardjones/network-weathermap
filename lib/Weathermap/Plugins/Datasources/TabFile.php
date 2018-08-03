<?php
namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\MapUtility;
use Weathermap\Core\StringUtility;
use Weathermap\Core\Map;
use Weathermap\Core\MapDataItem;

/**
 * Read data from a tab-separated text file
 *
 * @package Weathermap\Plugins\Datasources
 */
class TabFile extends Base
{
    public function __construct()
    {
        parent::__construct();

        $this->name = "TabText";
        $this->regexpsHandled = array(
            '/\.(tsv|txt)$/'
        );
    }

    /**
     * @param string $targetString The string from the config file
     * @param Map $map A reference to the map object (redundant)
     * @param MapDataItem $mapItem A reference to the object this target is attached to
     * @return array invalue, outvalue, unix timestamp that the data was valid
     */
    public function readData($targetString, &$map, &$mapItem)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        $itemName = $mapItem->name;

        $fullpath = realpath($targetString);
        $fileHandle = $this->validateAndOpenFile($fullpath);

        if ($fileHandle) {
            $data = $this->readDataFromTSV($fileHandle, $itemName);
            $this->data[IN] = $data[IN];
            $this->data[OUT] = $data[OUT];
            $stats = stat($fullpath);
            $this->dataTime = $stats['mtime'];
        } else {
            // some error code to go in here
            wm_warn("TabText ReadData: Couldn't open ($fullpath). [WMTABDATA01]\n");
        }

        return $this->returnData();
    }

    /**
     * @param $fileHandle
     * @param $itemName
     * @return array
     */
    protected function readDataFromTSV($fileHandle, $itemName)
    {
        $data = array();
        $data[IN] = null;
        $data[OUT] = null;

        while (!feof($fileHandle)) {
            $buffer = fgets($fileHandle, 4096);

            // strip out any line-endings that have gotten in here
            $buffer = str_replace("\r", "", $buffer);
            $buffer = str_replace("\n", "", $buffer);

            $parts = explode("\t", $buffer);

            if ($parts[0] == $itemName) {
                $data[IN] = StringUtility::interpretNumberWithMetricSuffixOrNull($parts[1]);
                $data[OUT] = StringUtility::interpretNumberWithMetricSuffixOrNull($parts[2]);
            }
        }

        return $data;
    }

    /**
     * @param $fullpath
     * @return resource
     */
    protected function validateAndOpenFile($fullpath)
    {
        MapUtility::debug("Opening $fullpath\n");

        if (!file_exists($fullpath)) {
            wm_warn("File '$fullpath' doesn't exist.");
            return null;
        }

        if (!is_readable($fullpath)) {
            wm_warn("File '$fullpath' isn't readable.");
            return null;
        }

        $fileHandle = fopen($fullpath, "r");
        return $fileHandle;
    }
}

// vim:ts=4:sw=4:
