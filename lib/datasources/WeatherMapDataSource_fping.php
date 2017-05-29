<?php
// Pluggable datasource for PHP Weathermap 0.9
// - return a live ping result

// TARGET fping:ipaddress
// TARGET fping:hostname

class WeatherMapDataSource_fping extends WeatherMapDataSource
{

    private $addresscache = array();
    private $donepings = false;
    private $results = array();


    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^fping:(\S+)$/'
        );
        $this->name = "FPing";
    }


    public function Init(&$map)
    {
        #
        # You may need to change the line below to have something like "/usr/local/bin/fping" or "/usr/bin/fping" instead.
        #
        $this->fping_cmd = "/usr/local/sbin/fping";

        return (true);
    }

    /**
     * pre-register a target + context, to allow a plugin to batch up queries to a slow database, or SNMP for example
     *
     * @param string $targetstring A clause from a TARGET line, after being processed by ProcessString
     * @param WeatherMap $map the WeatherMap main object
     * @param WeatherMapDataItem $item the specific WeatherMapItem that this target is for
     */
    public function Register($targetstring, &$map, &$item)
    {
        if (preg_match('/^fping:(\S+)$/', $targetstring, $matches)) {
            // save the address. This way, we can do ONE fping call for all the pings in the map.
            // fping does it all in parallel, so 10 hosts takes the same time as 1
            // TODO - actually implement that!
            $this->addresscache[] = $matches[1];
        }
    }

    public function ReadData($targetstring, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        $ping_count = intval($map->get_hint("fping_ping_count"));
        if ($ping_count == 0) {
            $ping_count = 5;
        }

        if (preg_match('/^fping:(\S+)$/', $targetstring, $matches)) {
            $target = $matches[1];

            $pattern = '/^$target\s:';
            for ($i = 0; $i < $ping_count; $i++) {
                $pattern .= '\s(\S+)';
            }
            $pattern .= "/";

            if (is_executable($this->fping_cmd)) {
                $command = $this->fping_cmd . " -t100 -r1 -p20 -u -C $ping_count -i10 -q $target 2>&1";
                wm_debug("Running $command\n");
                $pipe = popen($command, "r");

                $count = 0;
                $hitcount = 0;
                if (isset($pipe)) {
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
                    } else {
                        if ($hitcount == 0) {
                            wm_warn("FPing ReadData: $count lines read. But nothing returned for target??? ($target) Try running with DEBUG to see output.  [WMFPING02]\n");
                        } else {
                            $this->data[IN] = $ave;
                            $this->data[OUT] = $loss;
                            $item->add_note("fping_min", $min);
                            $item->add_note("fping_max", $max);
                        }
                    }
                    $this->dataTime = time();
                }
            } else {
                wm_warn("FPing ReadData: Can't find fping executable. Check path at line 19 of WeatherMapDataSource_fping.php [WMFPING01]\n");
            }
        }

        return $this->ReturnData();
    }
}

// vim:ts=4:sw=4:
