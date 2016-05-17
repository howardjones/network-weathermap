<?php

class WeatherMapDataSource_time extends WeatherMapDataSource
{
    private $timezones;

    public function __construct()
    {
        parent::__construct();

        $this->timezones = array();

        $this->regexpsHandled = array(
            '/^time:(.*)$/'
        );
    }

    public function Prefetch(&$map)
    {
        if ($this->recognised > 0) {
            $timezone_identifiers = DateTimeZone::listIdentifiers();
            foreach ($timezone_identifiers as $tz) {
                $this->timezones[strtolower($tz)] = $tz;

            }
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
        $data_time=0;

        if (preg_match("/^time:(.*)$/", $targetString, $matches)) {
            $timezone = $matches[1];

            $offset = "now";

            if (preg_match("/^([^:]+):(.*)$/", $timezone, $matches2)) {
                $timezone = $matches2[1];
                $offset = $matches2[2];

                // test that the offset is valid
                $timestamp = strtotime($offset);
                if (($timestamp     === false) || ($timestamp === -1)) {
                    warn("Time ReadData: Offset String ($offset) is bogus - ignoring [WMTIME03]\n");
                    $offset = "now";
                }
            }

            list($required_time, $timezone_name) = $this->getTimeForTimeZone($timezone, $offset);
            $data = $this->populateTimeData($mapItem, $required_time, $timezone_name);

            $data_time = time();

        } else {
            // some error code to go in here
            wm_warn("Time ReadData: Couldn't recognize $targetString \n");
        }

        wm_debug("Time ReadData: Returning (". WMUtility::valueOrNull($data[IN]) . "," . WMUtility::valueOrNull($data[OUT]).",$data_time)\n");

        return (array($data[IN], $data[OUT], $data_time));
    }

    /**
     * @param $timezone
     * @param $offset
     * @return array
     * @internal param $mapItem
     * @internal param $data
     * @internal param $matches
     * @internal param $timezone_l
     */
    private function getTimeForTimeZone($timezone, $offset)
    {
        $timezone_l = strtolower($timezone);

        if (array_key_exists($timezone_l, $this->timezones)) {
            $timezone_name = $this->timezones[$timezone_l];
            wm_debug("Time ReadData: Timezone exists: $timezone_name\n");
            $dateTime = new DateTime($offset, new DateTimeZone($timezone_name));

            return array($dateTime, $timezone_name);
        }

        wm_warn("Time ReadData: Couldn't recognize $timezone as a valid timezone name [WMTIME02]\n");

        return null;
    }

    /**
     * @param $mapItem
     * @param $required_time
     * @param $tz
     * @return array
     * @internal param $data
     */
    private function populateTimeData(&$mapItem, $required_time, $tz)
    {
        $mapItem->add_note("time_time12", $required_time->format("h:i"));
        $mapItem->add_note("time_time12ap", $required_time->format("h:i A"));
        $mapItem->add_note("time_time24", $required_time->format("H:i"));
        $mapItem->add_note("time_timet", $required_time->format("U"));

        $mapItem->add_note("time_timezone", $tz);


        $data[IN] = $required_time->format("H");
        $data[OUT] = $required_time->format("i");

        return $data;
    }
}

// vim:ts=4:sw=4:
