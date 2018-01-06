<?php
namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\Map;
use Weathermap\Core\MapDataItem;
use Weathermap\Core\MapUtility;
use DateTimeZone;

class Time extends Base
{

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^time:(.*)$/'
        );
        $this->name = "Time";
    }

    public function init(&$map)
    {
        if (preg_match('/^[234]\./', phpversion())) {
            MapUtility::debug("Time plugin requires PHP 5+ to run\n");
            return false;
        }
        return true;
    }


    /**
     * @param string $targetString
     * @param Map $map
     * @param MapDataItem $item
     * @return array
     */
    public function readData($targetString, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        $matches = 0;

        if (preg_match($this->regexpsHandled[0], $targetString, $matches)) {
            $timezone = $matches[1];
            $timezoneLowerCase = strtolower($timezone);

            $allTimezones = \DateTimeZone::listIdentifiers();

            foreach ($allTimezones as $tz) {
                if (strtolower($tz) == $timezoneLowerCase) {
                    MapUtility::debug("Time ReadData: Timezone exists: $tz\n");
                    $dateTime = new \DateTime("now", new DateTimeZone($tz));

                    $item->addNote("time_time12", $dateTime->format("h:i"));
                    $item->addNote("time_time12ap", $dateTime->format("h:i A"));
                    $item->addNote("time_time24", $dateTime->format("H:i"));
                    $item->addNote("time_timezone", $tz);
                    $this->data[IN] = $dateTime->format("H");
                    $this->dataTime = time();
                    $this->data[OUT] = $dateTime->format("i");
                    $matches++;
                }
            }
            if ($matches == 0) {
                MapUtility::warn("Time ReadData: Couldn't recognize $timezone as a valid timezone name [WMTIME02]\n");
            }
        } else {
            // some error code to go in here
            MapUtility::warn("Time ReadData: Couldn't recognize $targetString \n");
        }

        return $this->returnData();
    }
}

// vim:ts=4:sw=4:
