<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:databasename:username:pass:hostkey

namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\MapUtility;

/**
 * Get data from old-style MRTG HTML output where the data is in HTML comments
 *
 * @package Weathermap\Plugins\Datasources
 */
class Mrtg extends Base
{

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/\.(htm|html)$/'
        );
        $this->name = "MRTG";
    }

    private function readDataFromFile($targetstring, $matchvalue, $matchperiod)
    {
        $fd = fopen($targetstring, "r");

        if ($fd) {
            while (!feof($fd)) {
                $buffer = fgets($fd, 4096);
                MapUtility::debug("MRTG ReadData: Matching on '${matchvalue}in $matchperiod' and '${matchvalue}out $matchperiod'\n");

                if (preg_match("/<\!-- ${matchvalue}in $matchperiod ([-+]?\d+\.?\d*) -->/", $buffer, $matches)) {
                    $this->data[IN] = $matches[1] * 8;
                }
                if (preg_match("/<\!-- ${matchvalue}out $matchperiod ([-+]?\d+\.?\d*) -->/", $buffer, $matches)) {
                    $this->data[OUT] = $matches[1] * 8;
                }
            }
            fclose($fd);
            # don't bother with the modified time if the target is a URL
            if (!preg_match('/^[a-z]+:\/\//', $targetstring)) {
                $this->dataTime = filemtime($targetstring);
            }
        } else {
            // some error code to go in here
            MapUtility::debug("MRTG ReadData: Couldn't open ($targetstring). \n");
        }
    }

    public function readData($targetString, &$map, &$mapItem)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        $matchvalue = $mapItem->get_hint('mrtg_value', 'cu');
        $matchperiod = $mapItem->get_hint('mrtg_period', 'd');
        $swap = intval($mapItem->get_hint('mrtg_swap'), 0);
        $negate = intval($mapItem->get_hint('mrtg_negate'), 0);

        $this->readDataFromFile($targetString, $matchvalue, $matchperiod);

        if ($swap == 1) {
            MapUtility::debug("MRTG ReadData: Swapping IN and OUT\n");
            $t = $this->data[OUT];
            $this->data[OUT] = $this->data[IN];
            $this->data[IN] = $t;
        }

        if ($negate) {
            MapUtility::debug("MRTG ReadData: Negating values\n");
            $this->data[OUT] = -$this->data[OUT];
            $this->data[IN] = -$this->data[IN];
        }

        return $this->returnData();
    }
}

// vim:ts=4:sw=4:
