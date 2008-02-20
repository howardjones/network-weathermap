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
		$data[IN] = NULL;
		$data[OUT] = NULL;
		$data_time = 0;

		if(preg_match("/^snmp:([^:]+):([^:]+):([^:]+):([^:]+)$/",$targetstring,$matches))
		{
			$community = $matches[1];
			$host = $matches[2];
			$in_oid = $matches[3];
			$out_oid = $matches[4];

			$was = snmp_get_quick_print();
			snmp_set_quick_print(1);
			$was2 = snmp_get_valueretrieval();

			snmp_set_oid_output_format  ( SNMP_OID_OUTPUT_NUMERIC  );
			snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

			$in_result = snmpget($host,$community,$in_oid,1000000,2);
			$out_result = snmpget($host,$community,$out_oid,1000000,2);

			debug ("SNMP ReadData: Got $in_result and $out_result\n");

			// use floatval() here to force the output to be *some* kind of number
			// just in case the stupid formatting stuff doesn't stop net-snmp returning 'down' instead of 2
			if($in_result) { $data[IN] = floatval($in_result); }
			if($out_result) { $data[OUT] = floatval($out_result);}
			$data_time = time();
			snmp_set_quick_print($was);
		}

		debug ("SNMP ReadData: Returning (".($data[IN]===NULL?'NULL':$data[IN]).",".($data[OUT]===NULL?'NULL':$data[IN]).",$data_time)\n");
		
		return( array($data[IN], $data[OUT], $data_time) );
	}
}

// vim:ts=4:sw=4:
?>
