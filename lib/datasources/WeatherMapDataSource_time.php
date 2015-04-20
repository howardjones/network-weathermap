<?php

class WeatherMapDataSource_time extends WeatherMapDataSource
{

    function Recognise($targetString)
    {
        if (preg_match("/^time:(.*)$/", $targetString)) {
            if (preg_match("/^[234]\./", phpversion())) {
                wm_warn("Time DS Plugin recognised a TARGET, but needs PHP5+ to run. [WMTIME01]\n");
                return false;
            }
            return true;
        }

        return false;
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

        $matches=0;

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

            $timezone_l = strtolower($timezone);

            $timezone_identifiers = DateTimeZone::listIdentifiers();

            foreach ($timezone_identifiers as $tz) {
                if (strtolower($tz) == $timezone_l) {
                    wm_debug("Time ReadData: Timezone exists: $tz\n");
                    $dateTime = new DateTime($offset, new DateTimeZone($tz));

                    $mapItem->add_note("time_time12", $dateTime->format("h:i"));
                    $mapItem->add_note("time_time12ap", $dateTime->format("h:i A"));
                    $mapItem->add_note("time_time24", $dateTime->format("H:i"));
                    $mapItem->add_note("time_timet", $dateTime->format("U"));

                    $mapItem->add_note("time_timezone", $tz);
                    $data[IN] = $dateTime->format("H");
                    $data_time = time();
                    $data[OUT] = $dateTime->format("i");
                    $matches++;
                }
            }
            if ($matches==0) {
                wm_warn("Time ReadData: Couldn't recognize $timezone as a valid timezone name [WMTIME02]\n");
            }
        } else {
            // some error code to go in here
            wm_warn("Time ReadData: Couldn't recognize $targetString \n");
        }

        wm_debug("Time ReadData: Returning (".($data[IN]===null?'null':$data[IN]).",".($data[OUT]===null?'null':$data[OUT]).",$data_time)\n");

        return (array($data[IN], $data[OUT], $data_time));
    }
}

// vim:ts=4:sw=4:
