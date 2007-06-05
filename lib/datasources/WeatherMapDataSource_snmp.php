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

class WeatherMapDataSource_snmp extends WeatherMapDataSource {

	function Init(&$map)
	{
		if(function_exists('snmpget')) { return(TRUE); }
		debug("SNMP DS: snmpget() not found. Do you have the PHP SNMP module?\n");

		return(FALSE);
	}


	function Recognise($targetstring)
	{
		if(preg_match("/^snmp:([^:]+):([^:]+):([^:]+):([^:]+)$/",$targetstring,$matches))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	function ReadData($targetstring, &$map, &$item)
	{
		$inbw=-1;
		$outbw=-1;
		$data_time=0;

		if(preg_match("/^snmp:([^:]+):([^:]+):([^:]+):([^:]+)$/",$targetstring,$matches))
		{
			$community = $matches[1];
			$host = $matches[2];
			$in_oid = $matches[3];
			$out_oid = $matches[4];

			$was = snmp_get_quick_print();
				snmp_set_quick_print(1);

			$in_result = snmpget($host,$community,$in_oid,1000000,2);
			$out_result = snmpget($host,$community,$out_oid,1000000,2);

			debug ("SNMP ReadData: Got $in_result and $out_result\n");

			if($in_result) { $in_bw = $in_result; }
			if($out_result) { $out_bw = $out_result;}
			$data_time = time();
			snmp_set_quick_print($was);
		}

		debug ("SNMP ReadData: Returning ($inbw,$outbw,$data_time)\n");

		return ( array($inbw,$outbw,$data_time) );
	}
}

// vim:ts=4:sw=4:
?>
