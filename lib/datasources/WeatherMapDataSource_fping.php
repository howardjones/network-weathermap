<?php
// Pluggable datasource for PHP Weathermap 0.9
// - return a live ping result

// TARGET fping:ipaddress
// TARGET fping:hostname

class WeatherMapDataSource_fping extends WeatherMapDataSource
{
    # You may need to change the line below to have something like "/usr/local/bin/fping" or "/usr/bin/fping" instead.
    private $fping_cmd = "/usr/bin/fping";

    private $addresscache = array();
    private $donePings = false;
    private $pingResults = array();

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^fping:(\S+)$/'
        );

    }

    function Init(&$map)
    {
        if (is_executable($this->fping_cmd)) {
            wm_debug("FPing ReadData: Can't find fping executable. Check path at line 10 of WeatherMapDataSource_fping.php]\n");
            return false;
        }

        return true;
    }

    public function Register($targetString, &$map, &$item)
    {
        if (preg_match($this->regexpsHandled[0], $targetString, $matches)) {
            // save the address. This way, we can do ONE fping call for all the pings in the map.
            // fping does it all in parallel, so 10 hosts takes the same time as 1
            $this->addresscache[] = $matches[1];
            return true;
        }
    }

    function ReadData($targetString, &$map, &$mapItem)
    {
        $data[IN] = null;
        $data[OUT] = null;
        $data_time = 0;

        $ping_count = intval($map->get_hint("fping_ping_count"));
        if ($ping_count == 0) {
            $ping_count = 5;
        }

        $target = $this->extractTargetHost($targetString);
        if ($target == "") {
            wm_warn("FPing ReadData: No target? [WMFPING05]\n");
            return array(null, null, 0);
        }

        $pattern = '/^$target\s:';
        for ($i=0; $i<$ping_count; $i++) {
            $pattern .= '\s(\S+)';
        }
        $pattern .= "/";

        // TODO - this doesn't really validate the target in any way!!

        if (!is_executable($this->fping_cmd)) {
            wm_warn("FPing ReadData: Can't find fping executable. Check path at line 20 of WeatherMapDataSource_fping.php [WMFPING01]\n");
            return array(null, null, 0);
        }

        $command = $this->fping_cmd." -t100 -r1 -p20 -u -C $ping_count -i10 -q $target 2>&1";
        wm_debug("Running $command\n");
        $pipe = popen($command, "r");

        if (!isset($pipe)) {
            wm_warn("FPing ReadData: Couldn't open pipe to fping [WMFPING04]\n");
            return array(null, null, 0);
        }

        list($count, $hitcount, $loss, $ave, $min, $max) = $this->readDataFromFping($pipe, $pattern, $target, $ping_count, $matches);

        if ($hitcount >0) {
            $data[IN] = $ave;
            $data[OUT] = $loss;
            $mapItem->add_note("fping_min", $min);
            $mapItem->add_note("fping_max", $max);
        }

        wm_debug("FPing ReadData: Returning (". WMUtility::valueOrNull($data[IN]) . "," . WMUtility::valueOrNull($data[OUT]).", $data_time)\n");

        return( array($data[IN], $data[OUT], $data_time) );
    }

    /**
     * @param $pipe
     * @param $pattern
     * @param $target
     * @param $ping_count
     * @param $matches
     * @return array
     */
    protected function readDataFromFping($pipe, $pattern, $target, $ping_count, $matches)
    {
        $count = 0;
        $hitcount = 0;

        while (!feof($pipe)) {
            $line = fgets($pipe, 4096);
            $count++;
            wm_debug("Output: $line");

            if (preg_match($pattern, $line, $matches)) {
                wm_debug("Found output line for $target\n");
                $hitcount++;
                $loss = 0;
                $ave = 0;
                $total = 0;
                $cnt = 0;
                $min = 999999;
                $max = 0;
                for ($i = 1; $i <= $ping_count; $i++) {
                    if ($matches[$i] == '-') {
                        $loss += (100 / $ping_count);
                    } else {
                        $cnt++;
                        $total += $matches[$i];
                        $max = max($matches[$i], $max);
                        $min = min($matches[$i], $min);
                    }
                }
                if ($cnt > 0) {
                    $ave = $total / $cnt;
                }

                wm_debug("Result: $cnt $min -> $max $ave $loss\n");
            }
        }
        pclose($pipe);

        if ($count == 0) {
            wm_warn("FPing ReadData: No lines read. Bad hostname? ($target) [WMFPING03]\n");
        }

        if ($count > 0 && $hitcount == 0) {
            wm_warn("FPing ReadData: $count lines read. But nothing returned for target??? ($target) Try running with DEBUG to see output.  [WMFPING02]\n");
        }

        return array($count, $hitcount, $loss, $ave, $min, $max);
    }

    /**
     * @param $targetString
     * @param $matches
     * @return array
     */
    protected function extractTargetHost($targetString)
    {
        $target = "";
        if (preg_match($this->regexpsHandled[0], $targetString, $matches)) {
            $target = $matches[1];
            return array($matches, $target);
        }
        return $target;
    }
}

// vim:ts=4:sw=4:
