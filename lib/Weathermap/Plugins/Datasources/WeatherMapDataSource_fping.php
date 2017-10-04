<?php
// Pluggable datasource for PHP Weathermap 0.9
// - return a live ping result

// TARGET fping:ipaddress
// TARGET fping:hostname
namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\Map;
use Weathermap\Core\MapDataItem;
use Weathermap\Core\MapUtility;

class WeatherMapDataSource_fping extends DatasourceBase
{

    private $addresscache = array();
    private $donepings = false;
    private $results = array();
    private $fpingCommand;


    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^fping:(\S+)$/'
        );
        $this->name = "FPing";
    }


    public function init(&$map)
    {
        #
        # You may need to change the line below to have something like "/usr/local/bin/fping" or "/usr/bin/fping" instead.
        #
        $this->fpingCommand = "/usr/local/sbin/fping";

        return true;
    }

    /**
     * pre-register a target + context, to allow a plugin to batch up queries to a slow database, or SNMP for example
     *
     * @param string $targetstring A clause from a TARGET line, after being processed by ProcessString
     * @param Map $map the WeatherMap main object
     * @param MapDataItem $item the specific WeatherMapItem that this target is for
     */
    public function register($targetstring, &$map, &$item)
    {
        if (preg_match('/^fping:(\S+)$/', $targetstring, $matches)) {
            // save the address. This way, we can do ONE fping call for all the pings in the map.
            // fping does it all in parallel, so 10 hosts takes the same time as 1
            // TODO - actually implement that!
            $this->addresscache[] = $matches[1];
        }
    }

    public function readData($targetstring, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        $pingCount = intval($map->getHint("fping_ping_count"));
        if ($pingCount == 0) {
            $pingCount = 5;
        }

        if (preg_match('/^fping:(\S+)$/', $targetstring, $matches)) {
            $target = $matches[1];

            $pattern = '/^$target\s:';
            for ($i = 0; $i < $pingCount; $i++) {
                $pattern .= '\s(\S+)';
            }
            $pattern .= "/";

            if (is_executable($this->fpingCommand)) {
                $command = $this->fpingCommand . " -t100 -r1 -p20 -u -C $pingCount -i10 -q $target 2>&1";
                MapUtility::debug("Running $command\n");
                $pipe = popen($command, "r");

                $count = 0;
                $hitCount = 0;
                if (isset($pipe)) {
                    while (!feof($pipe)) {
                        $line = fgets($pipe, 4096);
                        $count++;
                        MapUtility::debug("Output: $line");

                        if (preg_match($pattern, $line, $matches)) {
                            MapUtility::debug("Found output line for $target\n");
                            $hitCount++;
                            $loss = 0;
                            $ave = 0;
                            $total = 0;
                            $cnt = 0;
                            $min = 999999;
                            $max = 0;
                            for ($i = 1; $i <= $pingCount; $i++) {
                                if ($matches[$i] == '-') {
                                    $loss += (100 / $pingCount);
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

                            MapUtility::debug("Result: $cnt $min -> $max $ave $loss\n");
                        }
                    }
                    pclose($pipe);
                    if ($count == 0) {
                        MapUtility::warn("FPing ReadData: No lines read. Bad hostname? ($target) [WMFPING03]\n");
                    } else {
                        if ($hitCount == 0) {
                            MapUtility::warn("FPing ReadData: $count lines read. But nothing returned for target??? ($target) Try running with DEBUG to see output.  [WMFPING02]\n");
                        } else {
                            $this->data[IN] = $ave;
                            $this->data[OUT] = $loss;
                            $item->addNote("fping_min", $min);
                            $item->addNote("fping_max", $max);
                        }
                    }
                    $this->dataTime = time();
                }
            } else {
                MapUtility::warn("FPing ReadData: Can't find fping executable. Check path at line 19 of WeatherMapDataSource_fping.php [WMFPING01]\n");
            }
        }

        return $this->returnData();
    }
}

// vim:ts=4:sw=4:
