<?php

// TODO - This seems to be half-written
// * Doesn't do anything with results of query
// * Doesn't add any rows to weathermap_data, so nothing would be collected anyway


namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\MapUtility;

/**
 * Get data directly from Cacti local_data_id (INCOMPLETE?)
 *
 * @package Weathermap\Plugins\Datasources
 */
class Cacti extends Base
{
    protected $name = "Cacti";

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^cacti:(\d+)$/'
        );
    }


    public function init(&$map)
    {
        if ($map->context === 'cacti') {
            if (true === function_exists('db_fetch_row')) {
                return true;
            } else {
                MapUtility::debug('ReadData cacti: Cacti database library not found.\n');
            }
        } else {
            MapUtility::debug("ReadData cacti: Can only run from Cacti environment.\n");
        }

        return false;
    }

    public function readData($targetString, &$map, &$mapItem)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        if (1 === preg_match($this->regexpsHandled[0], $targetString, $matches)) {
            $localDataId = intval($matches[1]);

            // This is very unlikely to work - nothing is ADDING the correct lines to weathermap_data!
            $SQL = 'select * from weathermap_data where local_data_id='.$localDataId;

            $result = \db_fetch_row($SQL);
        }

        return $this->returnData();
    }
}


// vim:ts=4:sw=4:
