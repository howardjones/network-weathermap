<?php
// Pluggable datasource for PHP Weathermap 0.9
// - return a live SNMP value

// doesn't work well with large values like interface counters (I think this is a rounding problem)
// - also it doesn't calculate rates. Just fetches a value.

// useful for absolute GAUGE-style values like DHCP Lease Counts, Wireless AP Associations, Firewall Sessions
// which you want to use to colour a NODE

// You could also fetch interface states from IF-MIB with it.

// TARGET snmp3:PROFILE1:hostname:1.3.6.1.4.1.3711.1.1:1.3.6.1.4.1.3711.1.2
// (that is, TARGET snmp3:profilename:host:in_oid:out_oid

// http://feathub.com/howardjones/network-weathermap/+1
namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\MapUtility;
use Weathermap\Core\Map;

/**
 * SNMPv3 data collection
 *
 * @package Weathermap\Plugins\Datasources\
 */
class SNMP3 extends Base
{
    protected $downCache;

    private $snmpParamDefaults = array(
        "username" => "",
        "seclevel" => "noAuthNoPriv",
        "authpass" => "",
        "privpass" => "",
        "authproto" => "",
        "privproto" => ""
    );

    private $originalQuickPrint;
    private $originalValueRetrieval;
    private $abortCount;
    private $timeout;
    private $retryCount;

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^snmp3:([^:]+):([^:]+):([^:]+):([^:]+)$/'
        );
        $this->name = "SNMP3";
    }

    public function init(&$map)
    {
        parent::init($map);

        // We can keep a list of unresponsive nodes, so we can give up earlier
        $this->downCache = array();

        $this->getMapGlobals();

        if (function_exists('snmp3_get')) {
            return true;
        }
        MapUtility::debug("SNMP3 DS: snmp3_get() not found. Do you have the PHP SNMP module?\n");

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

    /**
     * Decide if this host has failed too much already
     *
     * @param string $host
     * @return bool
     */
    private function isHostAborted($host)
    {
        // we're not keeping score
        if ($this->abortCount == 0) {
            return false;
        }

        if (!isset($this->downCache[$host]) || intval($this->downCache[$host]) < $this->abortCount) {
            return false;
        }

        return true;
    }

    public function readData($targetString, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        if (preg_match('/^snmp3:([^:]+):([^:]+):([^:]+):([^:]+)$/', $targetString, $matches)) {
            $profileName = $matches[1];
            $host = $matches[2];
            $oids = array(IN => $matches[3], OUT => $matches[4]);

            if ($this->isHostAborted($host)) {
                $this->prepareSNMPGlobals();
                $params = $this->buildSNMPParams($map, $profileName);

                MapUtility::debug("SNMPv3 ReadData: SNMP settings are %s\n", json_encode($params));

                $this->getSNMPData($host, $params, $oids, $item, $this->timeout, $this->retryCount);

                $this->restoreSNMPGlobals();
            } else {
                MapUtility::warn("SNMP for $host has reached $this->abortCount failures. Skipping. [WMSNMP01]");
            }
        } else {
            MapUtility::debug("SNMPv3 ReadData: regexp didn't match after Recognise did - this is odd!\n");
        }

        return $this->returnData();
    }

    /**
     * @param Map $map
     * @param $profileName
     * @return array
     */
    public function buildSNMPParams(&$map, $profileName)
    {
        # snmp3_PROFILE1_import 33
        #
        # OR
        #
        # snmp3_PROFILE1_username
        # snmp3_PROFILE1_seclevel
        # snmp3_PROFILE1_authproto
        # snmp3_PROFILE1_authpass
        # snmp3_PROFILE1_privproto
        # snmp3_PROFILE1_privpass

        $import = $map->getHint("snmp3_" . $profileName . "_import");

        $params = array();

        if (is_null($import)) {
            // If they are explicitly defined...
            MapUtility::debug("SNMPv3 ReadData: no import, defining profile $profileName from SET variables\n");
            foreach ($this->snmpParamDefaults as $keyname => $default) {
                $params[$keyname] = $map->getHint("snmp3_" . $profileName . "_" . $keyname, $default);
            }
        } else {
            // if they are to be copied from a Cacti profile...
            $import = intval($import);
            MapUtility::debug("SNMPv3 ReadData: will try to import profile $profileName from Cacti host id $import\n");

            $params = $this->copyParamsFromCacti($profileName, $import);
        }
        return $params;
    }

    /**
     * @param $profileName
     * @param $hostId
     * @return mixed
     */
    private function copyParamsFromCacti($profileName, $hostId)
    {
        $params = array();

        foreach ($this->snmpParamDefaults as $keyname => $default) {
            $params[$keyname] = $default;
        }

        if (!function_exists("db_fetch_row")) {
            MapUtility::warn("SNMPv3 ReadData snmp3_" . $profileName . "_import is set but not running in Cacti");
            return $params;
        }
        // this is something that should be cached or done in prefetch
        $result = \db_fetch_assoc(
            sprintf(
                "select * from host where snmp_version=3 and id=%d LIMIT 1",
                $hostId
            )
        );

        if (!$result) {
            MapUtility::warn("SNMPv3 ReadData snmp3_" . $profileName . "_import failed to read data from Cacti host id $hostId");
            return $params;
        }

        $dbFieldMapping = array(
            "username" => "snmp_username",
            "authpass" => "snmp_password",
            "privpass" => "snmp_priv_passphrase",
            "authproto" => "snmp_auth_protocol",
            "privproto" => "snmp_priv_protocol"
        );

        foreach ($dbFieldMapping as $param => $fieldname) {
            $params[$param] = $result[0][$fieldname];
        }

        if ($params['privproto'] == "[None]" || $params['privpass'] == '') {
            $params['seclevel'] = "authNoPriv";
            $params['privproto'] = "";
        } else {
            $params['seclevel'] = "authPriv";
        }

        MapUtility::debug(
            "SNMPv3 ReadData Imported Cacti info for device %d into profile named %s\n",
            $hostId,
            $profileName
        );

        return $params;
    }

    private function prepareSNMPGlobals()
    {
        if (function_exists("snmp_get_quick_print")) {
            $this->originalQuickPrint = snmp_get_quick_print();
            snmp_set_quick_print(1);
        }
        if (function_exists("snmp_get_valueretrieval")) {
            $this->originalValueRetrieval = snmp_get_valueretrieval();
        }

        if (function_exists('snmp_set_oid_output_format')) {
            snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
        }

        if (function_exists('snmp_set_valueretrieval')) {
            snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
        }
    }

    private function restoreSNMPGlobals()
    {
        if (function_exists("snmp_set_quick_print")) {
            snmp_set_quick_print($this->originalQuickPrint);
        }
    }

    /**
     * @param $host
     * @param $params
     * @param $oids
     * @param $item
     * @param $timeout
     * @param $retries
     */
    private function getSNMPData($host, $params, $oids, &$item, $timeout, $retries)
    {
        $channels = array(
            'in' => IN,
            'out' => OUT
        );
        $results = array();
        $results[IN] = null;
        $results[OUT] = null;

        if ($params['username'] != "") {
            foreach ($channels as $name => $id) {
                if ($oids[$id] != '-') {
                    $oid = $oids[$id];
                    MapUtility::debug("Going to get $oid\n");
                    $results[$id] = snmp3_get(
                        $host,
                        $params['username'],
                        $params['seclevel'],
                        $params['authproto'],
                        $params['authpass'],
                        $params['privproto'],
                        $params['privpass'],
                        $oid,
                        $timeout,
                        $retries
                    );
                    if ($results[$id] !== false) {
                        $this->data[$id] = floatval($results[$id]);
                        $item->addHint("snmp_" . $name . "_raw", $results[$id]);
                    } else {
                        $this->downCache{$host}++;
                    }
                } else {
                    MapUtility::debug("SNMPv3 ReadData: skipping $name channel: OID is '-'\n");
                }
            }
        } else {
            MapUtility::debug("SNMPv3 ReadData: no username defined, not going to try.\n");
        }

        MapUtility::debug("SNMPv3 ReadData: Got '%s' and '%s'\n", $results[IN], $results[OUT]);

        $this->dataTime = time();
    }

    /**
     * Get the map-global SNMP settings
     */
    private function getMapGlobals()
    {
        $this->timeout = intval($this->owner->getHint("snmp_timeout", 1000000));
        $this->abortCount = intval($this->owner->getHint("snmp_abort_count", 0));
        $this->retryCount = intval($this->owner->getHint("snmp_retries", 2));

        MapUtility::debug("Timeout changed to " . $this->timeout . " microseconds.\n");
        MapUtility::debug("Will abort after $this->abortCount failures for a given host.\n");
        MapUtility::debug("Number of retries changed to " . $this->retryCount . ".\n");
    }
}

// vim:ts=4:sw=4:
