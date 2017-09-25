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

class WeatherMapDataSource_snmpv3 extends DatasourceBase
{
    protected $downCache;

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
        // We can keep a list of unresponsive nodes, so we can give up earlier
        $this->downCache = array();

        if (function_exists('snmp3_get')) {
            return true;
        }
        MapUtility::wm_debug("SNMP3 DS: snmp3_get() not found. Do you have the PHP SNMP module?\n");

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


    public function readData($targetstring, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        $timeout = 1000000;
        $retries = 2;
        $abortCount = 0;

        $getResults = null;
        $outResult = null;

        $timeout = intval($map->getHint("snmp_timeout", $timeout));
        $abortCount = intval($map->getHint("snmp_abort_count", $abortCount));
        $retries = intval($map->getHint("snmp_retries", $retries));

        MapUtility::wm_debug("Timeout changed to " . $timeout . " microseconds.\n");
        MapUtility::wm_debug("Will abort after $abortCount failures for a given host.\n");
        MapUtility::wm_debug("Number of retries changed to " . $retries . ".\n");

        if (preg_match('/^snmp3:([^:]+):([^:]+):([^:]+):([^:]+)$/', $targetstring, $matches)) {
            $profileName = $matches[1];
            $host = $matches[2];
            $oids = array(IN => $matches[3], OUT => $matches[4]);

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
                $params = $this->buildSNMPParams($map, $profileName);

                MapUtility::wm_debug("SNMPv3 ReadData: SNMP settings are %s\n", json_encode($params));

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
                            MapUtility::wm_debug("Going to get $oid\n");
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
                            MapUtility::wm_debug("SNMPv3 ReadData: skipping $name channel: OID is '-'\n");
                        }
                    }
                } else {
                    MapUtility::wm_debug("SNMPv3 ReadData: no username defined, not going to try.\n");
                }

                MapUtility::wm_debug("SNMPv3 ReadData: Got '%s' and '%s'\n", $results[IN], $results[OUT]);

                $this->dataTime = time();

                if (function_exists("snmp_set_quick_print")) {
                    snmp_set_quick_print($was);
                }
            } else {
                MapUtility::wm_warn("SNMP for $host has reached $abortCount failures. Skipping. [WMSNMP01]");
            }
        } else {
            MapUtility::wm_debug("SNMPv3 ReadData: regexp didn't match after Recognise did - this is odd!\n");
        }

        return $this->returnData();
    }

    /**
     * @param $map
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

        $parts = array(
            "username" => "",
            "seclevel" => "noAuthNoPriv",
            "authpass" => "",
            "privpass" => "",
            "authproto" => "",
            "privproto" => ""
        );

        $params = array();

        // If they are explicitly defined...
        if (is_null($import)) {
            MapUtility::wm_debug("SNMPv3 ReadData: no import, defining profile $profileName from SET variables\n");
            foreach ($parts as $keyname => $default) {
                $params[$keyname] = $map->getHint("snmp3_" . $profileName . "_" . $keyname, $default);
            }
        } else {
            $import = intval($import);
            // if they are to be copied from a Cacti profile...
            MapUtility::wm_debug("SNMPv3 ReadData: will try to import profile $profileName from Cacti host id $import\n");

            foreach ($parts as $keyname => $default) {
                $params[$keyname] = $default;
            }

            if (function_exists("db_fetch_row")) {
                // this is something that should be cached or done in prefetch
                $result = \db_fetch_assoc(
                    sprintf(
                        "select * from host where snmp_version=3 and id=%d LIMIT 1",
                        $import
                    )
                );

                if (!$result) {
                    MapUtility::wm_warn("SNMPv3 ReadData snmp3_" . $profileName . "_import failed to read data from Cacti host id $import");
                } else {
                    $mapping = array(
                        "username" => "snmp_username",
                        "authpass" => "snmp_password",
                        "privpass" => "snmp_priv_passphrase",
                        "authproto" => "snmp_auth_protocol",
                        "privproto" => "snmp_priv_protocol"
                    );
                    foreach ($mapping as $param => $fieldname) {
                        $params[$param] = $result[0][$fieldname];
                    }
                    if ($params['privproto'] == "[None]" || $params['privpass'] == '') {
                        $params['seclevel'] = "authNoPriv";
                        $params['privproto'] = "";
                    } else {
                        $params['seclevel'] = "authPriv";
                    }
                    MapUtility::wm_debug(
                        "SNMPv3 ReadData Imported Cacti info for device %d into profile named %s\n",
                        $import,
                        $profileName
                    );
                }
            } else {
                MapUtility::wm_warn("SNMPv3 ReadData snmp3_" . $profileName . "_import is set but not running in Cacti");
            }
        }
        return $params;
    }
}

// vim:ts=4:sw=4:
