<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:databasename:username:pass:hostkey

namespace Weathermap\Plugins\Datasources;

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

    public function readData($targetstring, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        $matchvalue = $item->get_hint('mrtg_value');
        $matchperiod = $item->get_hint('mrtg_period');
        $swap = intval($item->get_hint('mrtg_swap'));
        $negate = intval($item->get_hint('mrtg_negate'));

        if ($matchvalue == '') {
            $matchvalue = "cu";
        }
        if ($matchperiod == '') {
            $matchperiod = "d";
        }

        $fd = fopen($targetstring, "r");

        if ($fd) {
            while (!feof($fd)) {
                $buffer = fgets($fd, 4096);
                wm_debug("MRTG ReadData: Matching on '${matchvalue}in $matchperiod' and '${matchvalue}out $matchperiod'\n");

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
            wm_debug("MRTG ReadData: Couldn't open ($targetstring). \n");
        }

        if ($swap == 1) {
            wm_debug("MRTG ReadData: Swapping IN and OUT\n");
            $t = $this->data[OUT];
            $this->data[OUT] = $this->data[IN];
            $this->data[IN] = $t;
        }

        if ($negate) {
            wm_debug("MRTG ReadData: Negating values\n");
            $this->data[OUT] = -$this->data[OUT];
            $this->data[IN] = -$this->data[IN];
        }

        return $this->returnData();
    }
}

// vim:ts=4:sw=4:
