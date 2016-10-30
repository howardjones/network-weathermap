<?php

class WeatherMapDataSource_time extends WeatherMapDataSource
{

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^time:(.*)$/'
        );
        $this->name = "Time";
    }

    public function Init(&$map)
    {
        if (preg_match('/^[234]\./', phpversion())) {
            wm_debug("Time plugin requires PHP 5+ to run\n");
            return FALSE;
        }
        return TRUE;
    }


    /**
     * @param string $targetstring
     * @param WeatherMap $map
     * @param WeatherMapDataItem $item
     * @return array
     */
    function ReadData($targetstring, &$map, &$item)
    {
        $matches = 0;

        if (preg_match($this->regexpsHandled[0], $targetstring, $matches)) {
            $timezone = $matches[1];
            $timezone_l = strtolower($timezone);

            $timezone_identifiers = DateTimeZone::listIdentifiers();

            foreach ($timezone_identifiers as $tz) {
                if (strtolower($tz) == $timezone_l) {
                    wm_debug("Time ReadData: Timezone exists: $tz\n");
                    $dateTime = new DateTime("now", new DateTimeZone($tz));

                    $item->add_note("time_time12", $dateTime->format("h:i"));
                    $item->add_note("time_time12ap", $dateTime->format("h:i A"));
                    $item->add_note("time_time24", $dateTime->format("H:i"));
                    $item->add_note("time_timezone", $tz);
                    $this->data[IN] = $dateTime->format("H");
                    $this->dataTime = time();
                    $this->data[OUT] = $dateTime->format("i");
                    $matches++;
                }
            }
            if ($matches == 0) {
                wm_warn("Time ReadData: Couldn't recognize $timezone as a valid timezone name [WMTIME02]\n");
            }
        } else {
            // some error code to go in here
            wm_warn("Time ReadData: Couldn't recognize $targetstring \n");
        }

        return $this->ReturnData();
    }
}

// vim:ts=4:sw=4:
