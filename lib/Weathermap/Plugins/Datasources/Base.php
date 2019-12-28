<?php

namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\MapUtility;
use Weathermap\Core\StringUtility;
use Weathermap\Core\Map;
use Weathermap\Core\MapDataItem;

/** base class for data source plugins. All data sources extend this class. */

class Base
{
    protected $owner;
    protected $regexpsHandled;
    protected $recognised;
    protected $name;
    protected $data;
    protected $dataTime;


    public function __construct()
    {
        $this->recognised = 0;
        $this->regexpsHandled = array();

        $this->data = array();
        $this->dataTime = 0;
        $this->data[IN] = null;
        $this->data[OUT] = null;
        $this->name = "Unnamed";
    }


// Initialize - called after config has been read (so SETs are processed)
// but just before ReadData. Used to allow plugins to verify their dependencies
// (if any) and bow out gracefully. Return FALSE to signal that the plugin is not
// in a fit state to run at the moment.
    public function init(&$map)
    {
        $this->owner = $map;

        return true;
    }

    /**
     * called with the TARGET string by map->ReadData()
     *
     * Default implementation just checks the regexps in regexpsHandled[], so you may not need to implement at all.
     *
     * @param $targetString string A single 'word' from the TARGET line
     * @return bool Returns true or false, depending on whether it wants to handle this TARGET
     */
    public function recognise($targetString)
    {
        foreach ($this->regexpsHandled as $regexp) {
            if (preg_match($regexp, $targetString)) {
                $this->recognised++;
                return true;
            }
        }
        return false;
    }


    /**
     * the actual ReadData
     * returns an array of two values (in,out). -1,-1 if it couldn't get valid data
     *
     * @param string $targetString
     * @param Map $map
     * @param MapDataItem $mapItem
     * @return array
     */
    public function readData($targetString, &$map, &$mapItem)
    {
        return array(-1, -1);
    }

    /**
     * Prepare data for returning, and log it.
     * Removing some move boilerplate from data plugins, and also making the move
     * to multiple channels (>2) easier in the future.
     *
     * @return array
     */
    protected function returnData()
    {
        MapUtility::debug(
            sprintf(
                "%s ReadData: Returning (%s, %s, %s)\n",
                $this->name,
                StringUtility::valueOrNull($this->data[IN]),
                StringUtility::valueOrNull($this->data[OUT]),
                $this->dataTime
            )
        );

        return array(array($this->data[IN], $this->data[OUT]), $this->dataTime);
    }

    /**
     * pre-register a target + context, to allow a plugin to batch up queries to a slow database, or SNMP for example
     *
     * @param string $targetstring A clause from a TARGET line, after being processed by ProcessString
     * @param Map $map the WeatherMap main object
     * @param MapDataItem $item the specific WeatherMapItem that this target is for
     */
    public function register($targetstring, &$map, &$item)
    {
    }

    /**
     * called before ReadData, to allow plugins to DO the prefetch of targets known from Register
     *
     * @param Map $map the WeatherMap main object
     */
    public function preFetch(&$map)
    {
    }

    /**
     * Run after all data collection
     * some plugin might need to update a local cache, close files, or other state
     *
     * @param Map $map the WeatherMap main object
     */
    public function cleanUp(&$map)
    {
    }
}
