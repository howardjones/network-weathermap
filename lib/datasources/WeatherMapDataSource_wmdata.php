<?php

class WeatherMapDataSource_wmdata extends WeatherMapDataSource
{
    function Recognise($targetstring)
    {
        if (preg_match("/^wmdata:.*$/", $targetstring, $matches)) {
            return true;
        } else {
            return false;
        }
    }

    // function ReadData($targetstring, $configline, $itemtype, $itemname, $map)
    function ReadData($targetstring, &$map, &$item)
    {
        $data[IN] = null;
        $data[OUT] = null;
        $data_time = 0;
        $itemname = $item->name;

        $matches = 0;

        if( preg_match("/^wmdata:([^:]*):(.*)", $targetstring, $matches)) {
            $datafile = $matches[1];
            $dataname = $matches[2];
        }

        if( file_exists($datafile)) {
            $fd = fopen($targetstring, "r");
            if ($fd) {
                $found = false;
                while (!feof($fd)) {
                    $buffer = fgets($fd, 4096);
                    # strip out any Windows line-endings that have gotten in here
                    $buffer = str_replace("\r", "", $buffer);

                    $fields = explode("\t",$buffer);
                    if($fields[0] == $dataname) {
                        $data[IN] = $fields[1];
                        $data[OUT] = $fields[2];
                        $found = true;
                    }
                }

                if($found===true) {
                    $stats = stat($datafile);
                    $data_time = $stats['mtime'];
                } else {
                    wm_warn("WMData ReadData: Data name ($dataname) didn't exist in ($datafile). [WMWMDATA03]\n");
                }
                
            } else {
                wm_warn("WMData ReadData: Couldn't open ($datafile). [WMWMDATA02]\n");
            }

        } else {
            wm_warn("WMData ReadData: $datafile doesn't exist [WMWMDATA01]");
        }


        wm_debug( sprintf("WMData ReadData: Returning (%s, %s, %s)\n",
		        string_or_null($data[IN]),
		        string_or_null($data[OUT]),
		        $data_time
        	));

        return (array (
            $data[IN],
            $data[OUT],
            $data_time
        ));
    }
}

// vim:ts=4:sw=4:
