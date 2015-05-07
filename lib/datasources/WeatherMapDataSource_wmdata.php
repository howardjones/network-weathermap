<?php

class WeatherMapDataSource_wmdata extends WeatherMapDataSource
{
    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array('/^wmdata:([^:]*):(.*)');
    }

    function ReadData($targetString, &$map, &$mapItem)
    {
        $data[IN] = null;
        $data[OUT] = null;
        $data_time = 0;

        $matches = 0;

        if (preg_match($this->regexpsHandled[0], $targetString, $matches)) {
            $dataFileName = $matches[1];
            $dataItemName = $matches[2];
        }

        if (! file_exists($dataFileName)) {
            wm_warn("WMData ReadData: $dataFileName doesn't exist [WMWMDATA01]");
            return array(null, null, 0);
        }

        $fileHandle = fopen($targetString, "r");
        if (!$fileHandle) {
            wm_warn("WMData ReadData: Couldn't open ($dataFileName). [WMWMDATA02]\n");
            return array(null, null, 0);
        }

        list($found, $data) = $this->findDataItem($fileHandle, $dataItemName, $data);

        if ($found===true) {
            $stats = stat($dataFileName);
            $data_time = $stats['mtime'];
        } else {
            wm_warn("WMData ReadData: Data name '$dataItemName' didn't exist in '$dataFileName'. [WMWMDATA03]\n");
        }

        wm_debug(
            sprintf(
                "WMData ReadData: Returning (%s, %s, %s)\n",
                string_or_null($data[IN]),
                string_or_null($data[OUT]),
                $data_time
            )
        );

        return (array (
            $data[IN],
            $data[OUT],
            $data_time
        ));
    }

    /**
     * @param $fileHandle
     * @param $dataItemName
     * @param $data
     * @return array
     */
    private function findDataItem($fileHandle, $dataItemName, $data)
    {
        $found = false;
        while (!feof($fileHandle)) {
            $buffer = fgets($fileHandle, 4096);
            # strip out any Windows line-endings that have gotten in here
            $buffer = str_replace("\r", "", $buffer);

            $fields = explode("\t", $buffer);
            if ($fields[0] == $dataItemName) {
                $data[IN] = $fields[1];
                $data[OUT] = $fields[2];
                $found = true;
            }
        }

        return array($found, $data);
    }
}

// vim:ts=4:sw=4:
