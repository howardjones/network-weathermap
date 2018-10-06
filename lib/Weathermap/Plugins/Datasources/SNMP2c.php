<?php
// Pluggable datasource for PHP Weathermap 0.9
// - return a live SNMP value

// doesn't work well with large values like interface counters (I think this is a rounding problem)
// - also it doesn't calculate rates. Just fetches a value.

// useful for absolute GAUGE-style values like DHCP Lease Counts, Wireless AP Associations, Firewall Sessions
// which you want to use to colour a NODE

// You could also fetch interface states from IF-MIB with it.

// TARGET snmp2c:public:hostname:1.3.6.1.4.1.3711.1.1:1.3.6.1.4.1.3711.1.2
// (that is, TARGET snmp:community:host:in_oid:out_oid

// http://feathub.com/howardjones/network-weathermap/+2
namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\MapUtility;

/**
 * SNMPv2c data collection
 *
 * @package Weathermap\Plugins\Datasources
 */
class SNMP2C extends Base
{
    protected $downCache;

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^snmp2c:([^:]+):([^:]+):([^:]+):([^:]+)$/'
        );
        $this->name = "SNMP2C";
    }

    public function init(&$map)
    {
        // We can keep a list of unresponsive nodes, so we can give up earlier
        $this->downCache = array();

        if (function_exists('snmp2_get')) {
            return true;
        }
        MapUtility::debug("SNMP2c DS: snmp2_get() not found. Do you have the PHP SNMP module?\n");

        return false;
    }

    public function register($targetstring, &$map, &$item)
    {
        parent::register($targetstring, $map, $item);

        if (preg_match($this->regexpsHandled[0], $targetstring, $matches)) {
            // make sure there is a key for every host in the down_cache
            $host = $matches[2];
            $this->downCache[$host] = 0;
        }
    }

    public function readData($targetString, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        $timeout = 1000000;
        $retries = 2;
        $abortCount = 0;

        $inResult = null;
        $outResult = null;

        $timeout = intval($map->getHint("snmp_timeout", $timeout));
        $abortCount = intval($map->getHint("snmp_abort_count", $abortCount));
        $retries = intval($map->getHint("snmp_retries", $retries));

        MapUtility::debug("Timeout changed to " . $timeout . " microseconds.\n");
        MapUtility::debug("Will abort after $abortCount failures for a given host.\n");
        MapUtility::debug("Number of retries changed to " . $retries . ".\n");

        if (preg_match("/^snmp2c:([^:]+):([^:]+):([^:]+):([^:]+)$/", $targetString, $matches)) {
            $community = $matches[1];
            $host = $matches[2];
            $inOID = $matches[3];
            $outOID = $matches[4];

            if (($abortCount == 0)
                || (
                    ($abortCount > 0)
                    && (!isset($this->downCache[$host]) || intval($this->downCache[$host]) < $abortCount)
                )
            ) {
                if (function_exists("snmp_get_quick_print")) {
                    $was = snmp_get_quick_print();
                    snmp_set_quick_print(1);
                }
                if (function_exists("snmp_get_valueretrieval")) {
                    $was2 = snmp_get_valueretrieval();
                }

                if (function_exists('snmp_set_oid_output_format')) {
                    snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
                }
                if (function_exists('snmp_set_valueretrieval')) {
                    snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
                }

                if ($inOID != '-') {
                    $inResult = snmp2_get($host, $community, $inOID, $timeout, $retries);
                    if ($inResult !== false) {
                        $this->data[IN] = floatval($inResult);
                        $item->addHint("snmp_in_raw", $inResult);
                    } else {
                        $this->downCache{$host}++;
                    }
                }
                if ($outOID != '-') {
                    $outResult = snmp2_get($host, $community, $outOID, $timeout, $retries);
                    if ($outResult !== false) {
                        // use floatval() here to force the output to be *some* kind of number
                        // just in case the stupid formatting stuff doesn't stop net-snmp returning 'down' instead of 2
                        $this->data[OUT] = floatval($outResult);
                        $item->addHint("snmp_out_raw", $outResult);
                    } else {
                        $this->downCache{$host}++;
                    }
                }

                MapUtility::debug("SNMP2c ReadData: Got $inResult and $outResult\n");

                $this->dataTime = time();

                if (function_exists("snmp_set_quick_print")) {
                    snmp_set_quick_print($was);
                }
                if (function_exists("snmp_set_valueretrieval")) {
                    snmp_set_valueretrieval($was2);
                }
            } else {
                MapUtility::warn("SNMP for $host has reached $abortCount failures. Skipping. [WMSNMP01]");
            }
        }

        return $this->returnData();
    }
}

// vim:ts=4:sw=4:
