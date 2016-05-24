<?php

class WeatherMapDataSource_time extends WeatherMapDataSource {

	function Recognise($targetstring)
	{
		if(preg_match("/^time:(.*)$/",$targetstring,$matches))
		{
			if(preg_match("/^[234]\./",phpversion()))
			{
				wm_warn("Time DS Plugin recognised a TARGET, but needs PHP5+ to run. [WMTIME01]\n");
				return FALSE;
			}
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	// function ReadData($targetstring, $configline, $itemtype, $itemname, $map)
	function ReadData($targetstring, &$map, &$item)
	{
		$data[IN] = NULL;
		$data[OUT] = NULL;
		$data_time=0;
		$itemname = $item->name;

		$matches=0;

		if(preg_match("/^time:(.*)$/",$targetstring,$matches))
		{
			$timezone = $matches[1];
			$timezone_l = strtolower($timezone);
			
			$timezone_identifiers = DateTimeZone::listIdentifiers();
			
			foreach ($timezone_identifiers as $tz)
			{
				if(strtolower($tz) == $timezone_l)
				{				
					wm_debug ("Time ReadData: Timezone exists: $tz\n");
					$dateTime = new DateTime("now", new DateTimeZone($tz));
					
					$item->add_note("time_time12",$dateTime->format("h:i"));
					$item->add_note("time_time12ap",$dateTime->format("h:i A"));
					$item->add_note("time_time24",$dateTime->format("H:i"));
					$item->add_note("time_timezone",$tz);
					$data[IN] = $dateTime->format("H");
					$data_time = time();
					$data[OUT] = $dateTime->format("i");
					$matches++;
				}
			}	
			if($matches==0)
			{
				wm_warn ("Time ReadData: Couldn't recognize $timezone as a valid timezone name [WMTIME02]\n"); 
			}			
		}
		else {
			// some error code to go in here
			wm_warn ("Time ReadData: Couldn't recognize $targetstring \n"); 
		}		
		
		wm_debug ("Time ReadData: Returning (".($data[IN]===NULL?'NULL':$data[IN]).",".($data[OUT]===NULL?'NULL':$data[OUT]).",$data_time)\n");
	
		return( array($data[IN], $data[OUT], $data_time) );
	}
}

// vim:ts=4:sw=4:
