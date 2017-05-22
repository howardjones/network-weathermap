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

class WeatherMapDataSource_snmpv3 extends WeatherMapDataSource
{
    protected $down_cache;

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^snmp3:([^:]+):([^:]+):([^:]+):([^:]+)$/'
        );
        $this->name = "SNMP3";
    }

    public function Init(&$map)
    {
        // We can keep a list of unresponsive nodes, so we can give up earlier
        $this->down_cache = array();

        if (function_exists('snmp3_get')) {
            return true;
        }
        wm_debug("SNMP3 DS: snmp3_get() not found. Do you have the PHP SNMP module?\n");

        return false;
    }

    public function ReadData($targetstring, &$map, &$item)
    {
        $timeout = 1000000;
        $retries = 2;
        $abort_count = 0;

        $get_results = null;
        $out_result = null;

        $timeout = intval($map->get_hint("snmp_timeout", $timeout));
        $abort_count = intval($map->get_hint("snmp_abort_count", $abort_count));
        $retries = intval($map->get_hint("snmp_retries", $retries));

        wm_debug("Timeout changed to " . $timeout . " microseconds.\n");
        wm_debug("Will abort after $abort_count failures for a given host.\n");
        wm_debug("Number of retries changed to " . $retries . ".\n");

        if (preg_match('/^snmp3:([^:]+):([^:]+):([^:]+):([^:]+)$/', $targetstring, $matches)) {
            $profile_name = $matches[1];
            $host = $matches[2];
            $oids[IN] = $matches[3];
            $oids[OUT] = $matches[4];

            if (($abort_count == 0)
                || (
                    ($abort_count > 0)
                    && (!isset($this->down_cache[$host]) || intval($this->down_cache[$host]) < $abort_count)
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

                $import = $map->get_hint("snmp3_" . $profile_name . "_import");

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
                    wm_debug("SNMPv3 ReadData: no import, defining profile $profile_name from SET variables\n");
                    foreach ($parts as $keyname => $default) {
                        $params[$keyname] = $map->get_hint("snmp3_" . $profile_name . "_" . $keyname, $default);
                    }
                } else {
                    $import = intval($import);
                    // if they are to be copied from a Cacti profile...
                    wm_debug("SNMPv3 ReadData: will try to import profile $profile_name from Cacti host id $import\n");

                    foreach ($parts as $keyname => $default) {
                        $params[$keyname] = $default;
                    }

                    if (function_exists("db_fetch_row")) {
                        // this is something that should be cached or done in prefetch
                        $result = db_fetch_assoc(sprintf("select * from host where snmp_version=3 and id=%d LIMIT 1", $import));

                        if (! $result) {
                            wm_warn("SNMPv3 ReadData snmp3_" . $profile_name . "_import failed to read data from Cacti host id $import");
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
                            wm_debug("SNMPv3 ReadData Imported Cacti info for device %d into profile named %s\n", $import, $profile_name);
                        }
                    } else {
                        wm_warn("SNMPv3 ReadData snmp3_" . $profile_name . "_import is set but not running in Cacti");
                    }
                }

                wm_debug("SNMPv3 ReadData: SNMP settings are %s\n", json_encode($params));

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
                            wm_debug("Going to get $oid\n");
                            $results[$id] = snmp3_get($host, $params['username'], $params['seclevel'], $params['authproto'], $params['authpass'], $params['privproto'], $params['privpass'], $oid, $timeout, $retries);
                            if ($results[$id] !== false) {
                                $this->data[$id] = floatval($results[$id]);
                                $item->add_hint("snmp_" . $name . "_raw", $results[$id]);
                            } else {
                                $this->down_cache{$host}++;
                            }
                        } else {
                            wm_debug("SNMPv3 ReadData: skipping $name channel: OID is '-'\n");
                        }
                    }
                } else {
                    wm_debug("SNMPv3 ReadData: no username defined, not going to try.\n");
                }

                wm_debug("SNMPv3 ReadData: Got '%s' and '%s'\n", $results[IN], $results[OUT]);

                $this->dataTime = time();

                if (function_exists("snmp_set_quick_print")) {
                    snmp_set_quick_print($was);
                }
            } else {
                wm_warn("SNMP for $host has reached $abort_count failures. Skipping. [WMSNMP01]");
            }
        } else {
            wm_debug("SNMPv3 ReadData: regexp didn't match after Recognise did - this is odd!\n");
        }

        return $this->ReturnData();
    }
}

// vim:ts=4:sw=4:
