<?php
// Pluggable datasource for PHP Weathermap 0.9
// - return a live ping result

// TARGET fping:ipaddress
// TARGET fping:hostname
namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\Map;
use Weathermap\Core\MapDataItem;
use Weathermap\Core\MapUtility;

/**
 * Use fping to ping remote hosts
 *
 * @package Weathermap\Plugins\Datasources
 */
class FPing extends Base
{

    private $addresscache = array();
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
        if (preg_match($this->regexpsHandled[0], $targetstring, $matches)) {
            // save the address. This way, we can do ONE fping call for all the pings in the map.
            // fping does it all in parallel, so 10 hosts takes the same time as 1
            // TODO - actually implement that!
            $this->addresscache[] = $matches[1];
        }
    }

    /**
     * @param string $targetString
     * @param Map $map
     * @param MapDataItem $item
     * @return array
     */
    public function readData($targetString, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        $pingCount = intval($map->getHint("fping_ping_count", 5));

        if (preg_match($this->regexpsHandled[0], $targetString, $matches)) {
            $this->ping($item, $matches[1], $pingCount);
        }

        return $this->returnData();
    }

    /**
     * @param MapDataItem $item
     * @param integer $pingCount
     * @param string $targetAddress
     */
    private function ping(&$item, $targetAddress, $pingCount)
    {
        if (!is_executable($this->fpingCommand)) {
            MapUtility::warn("FPing ReadData: Can't find fping executable. Check path at line 36 of FPing");
            return;
        }

        $resultPattern = '/^$target\s:';
        for ($i = 0; $i < $pingCount; $i++) {
            $resultPattern .= '\s(\S+)';
        }
        $resultPattern .= "/";

        $lineCount = 0;
        $hitCount = 0;

        $command = sprintf(
            "%s  -t100 -r1 -p20 -u -C %d -i100 -q %s 2>&1",
            escapeshellcmd($this->fpingCommand),
            $pingCount,
            escapeshellarg($targetAddress)
        );

        MapUtility::debug("Running $command\n");

        $pipe = popen($command, "r");

        if (!isset($pipe)) {
            MapUtility::warn("FPing ReadData: Failed to run fping command [WMFPING04]\n");
            return;
        }

        while (!feof($pipe)) {
            $line = fgets($pipe, 4096);
            $lineCount++;
            MapUtility::debug("Output: $line");

            if (preg_match($resultPattern, $line, $matches)) {
                MapUtility::debug("Found output line for $targetAddress\n");

                $hitCount++;
                list($loss, $ave, $cnt, $min, $max) = $this->processResultLine($pingCount, $matches);

                MapUtility::debug("Result: $cnt $min -> $max $ave $loss\n");
            }
        }
        pclose($pipe);

        if ($lineCount == 0) {
            MapUtility::warn("FPing ReadData: No lines read. Bad hostname? ($targetAddress) [WMFPING03]\n");
            return;
        }
        if ($hitCount == 0) {
            MapUtility::warn("FPing ReadData: $lineCount lines read. But nothing returned for target??? ($targetAddress) Try running with DEBUG to see output.  [WMFPING02]\n");
            return;
        }

        $this->data[IN] = $ave;
        $this->data[OUT] = $loss;
        $item->addNote("fping_min", $min);
        $item->addNote("fping_max", $max);

        $this->dataTime = time();
    }

    /**
     * @param $pingCount
     * @param $matches
     * @return array
     */
    private function processResultLine($pingCount, $matches)
    {
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
        return array($loss, $ave, $cnt, $min, $max);
    }
}

// vim:ts=4:sw=4:
