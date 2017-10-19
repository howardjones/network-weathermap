<?php
namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\Map;
use Weathermap\Core\MapDataItem;

class WeathermapData extends Base
{

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^wmdata:([^:]*):(.*)/'
        );
        $this->name = "WMData";
    }

    /**
     * @param string $targetstring The string from the config file
     * @param Map $map A reference to the map object (redundant)
     * @param MapDataItem $item A reference to the object this target is attached to
     * @return array invalue, outvalue, unix timestamp that the data was valid
     */
    public function readData($targetstring, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        $matches = 0;
        $datafile = "";
        $dataname = "";

        if (preg_match($this->regexpsHandled[0], $targetstring, $matches)) {
            $datafile = $matches[1];
            $dataname = $matches[2];
        }

        if (file_exists($datafile)) {
            $fd = fopen($targetstring, "r");
            if ($fd) {
                $found = false;
                while (!feof($fd)) {
                    $buffer = fgets($fd, 4096);
                    # strip out any Windows line-endings that have gotten in here
                    $buffer = str_replace("\r", "", $buffer);

                    $fields = explode("\t", $buffer);
                    if ($fields[0] == $dataname) {
                        $this->data[IN] = $fields[1];
                        $this->data[OUT] = $fields[2];
                        $found = true;
                    }
                }

                if ($found === true) {
                    $stats = stat($datafile);
                    $this->dataTime = $stats['mtime'];
                } else {
                    wm_warn("WMData ReadData: Data name ($dataname) didn't exist in ($datafile). [WMWMDATA03]\n");
                }
            } else {
                wm_warn("WMData ReadData: Couldn't open ($datafile). [WMWMDATA02]\n");
            }
        } else {
            wm_warn("WMData ReadData: $datafile doesn't exist [WMWMDATA01]");
        }

        return $this->returnData();
    }
}

// vim:ts=4:sw=4:
