<?php
// Pluggable datasource for PHP Weathermap 0.9
// - return a live SNMP value

// doesn't work well with large values like interface counters (I think this is a rounding problem)
// - also it doesn't calculate rates. Just fetches a value.

// useful for absolute GAUGE-style values like DHCP Lease Counts, Wireless AP Associations, Firewall Sessions
// which you want to use to colour a NODE

// You could also fetch interface states from IF-MIB with it.

// TARGET snmp:public:hostname:1.3.6.1.4.1.3711.1.1:1.3.6.1.4.1.3711.1.2
// (that is, TARGET snmp:community:host:in_oid:out_oid

class WeatherMapDataSource_snmp extends WeatherMapDataSource
{
    private $snmpSavedQuickPrint;
    private $snmpSavedValueRetrieval;

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array('/^snmp:([^:]+):([^:]+):([^:]+):([^:]+)$/');
    }

    function Init(&$map)
    {
        // We can keep a list of unresponsive nodes, so we can give up earlier
        $this->down_cache = array();

        if (function_exists('snmpget')) {
            return(true);
        }
        wm_debug("SNMP DS: snmpget() not found. Do you have the PHP SNMP module?\n");

        return(false);
    }

    public function ReadData($targetString, &$map, &$mapItem)
    {
        $data[IN] = null;
        $data[OUT] = null;
        $data_time = 0;

        $timeout = intval($this->owner->get_hint("snmp_timeout", 1000000));
        $abort_count = intval($this->owner->get_hint("snmp_abort_count"), 0);
        $retries = intval($this->owner->get_hint("snmp_retries"), 2);

        if (preg_match($this->regexpsHandled[0], $targetString, $matches)) {
            $community = $matches[1];
            $host = $matches[2];

            $oids = array();
            $oids[IN] = $matches[3];
            $oids[OUT] = $matches[4];

            if (($abort_count == 0)
                || (
                    ( $abort_count > 0 )
                    && ( !isset($this->down_cache[$host]) || intval($this->down_cache[$host]) < $abort_count )
                )
              ) {
                $this->changeSNMPSettings();

                $channels = array("in"=>IN, "out"=>OUT);

                foreach ($channels as $channelName => $channel) {
                    if ($oids[$channel] != '-') {
                        $result = snmpget($host, $community, $oids[$channel], $timeout, $retries);
                        if ($result !== false) {
                            $data[$channel] = floatval($result);
                            $mapItem->add_hint("snmp_".$channelName."_raw", $result);
                        } else {
                            $this->down_cache{$host}++;
                        }
                    }
                    wm_debug("SNMP ReadData: Got $result for $channelName\n");
                }

                $data_time = time();
                $this->restoreSNMPSettings();

            } else {
                wm_warn("SNMP for $host has reached $abort_count failures. Skipping. [WMSNMP01]");
            }
        }

        wm_debug("SNMP ReadData: Returning (".($data[IN]===null?'null':$data[IN]).",".($data[OUT]===null?'null':$data[OUT]).",$data_time)\n");

        return (array($data[IN], $data[OUT], $data_time));
    }

    private function restoreSNMPSettings()
    {
// Restore the SNMP settings as before
        if (function_exists("snmp_set_quick_print")) {
            snmp_set_quick_print($this->snmpSavedQuickPrint);
        }
        if (function_exists("snmp_set_valueretrieval")) {
            snmp_set_valueretrieval($this->snmpSavedValueRetrieval);
        }
    }

    private function changeSNMPSettings()
    {
        if (function_exists("snmp_get_quick_print")) {
            $this->snmpSavedQuickPrint = snmp_get_quick_print();
            snmp_set_quick_print(1);
        }
        if (function_exists("snmp_get_valueretrieval")) {
            $this->snmpSavedValueRetrieval = snmp_get_valueretrieval();
        }

        if (function_exists('snmp_set_oid_output_format')) {
            snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
        }
        if (function_exists('snmp_set_valueretrieval')) {
            snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
        }
    }
}

// vim:ts=4:sw=4:
