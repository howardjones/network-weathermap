<?php
// Run an external 'MRTG-compatible' script, and return it's values
// TARGET !/usr/local/bin/qmailmrtg7 t /var/log/qmail

// MRTG Target scripts return 4 lines of text as output:
//    'Input' value - interpreted as a byte count (so multiplied by 8)
//    'Output' value - interpreted as a byte count (so multiplied by 8)
//    'uptime' as a string
//    'name of targer' as a string
// we ignore the last two

// NOTE: Obviously, if you allow anyone to create maps, you are
//       allowing them to run ANY COMMAND as the user that runs 
//       weathermap, by using this plugin. This might not be a 
//       good thing.

//       If you want to allow only one command, consider making
//       your own datasource plugin which only runs that one command.


class WeatherMapDataSource_external extends WeatherMapDataSource {

	function Recognise($targetstring)
	{
		if(preg_match("/^!(.*)$/",$targetstring,$matches))
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
		$data_time = 0;

		if(preg_match("/^!(.*)$/",$targetstring,$matches))
		{
			$command = $matches[1];

			debug("ExternalScript ReadData: Running $command\n");
			// run the command here
			if( ($pipe = popen($command,"r")) === false)
			{
				warn("ExternalScript ReadData: Failed to run external script.\n");
			}
			else
			{
				$i=0;
				while( ($i <5) && ! feof($pipe) )
				{
					$lines[$i++] = fgets($pipe,1024);
				}
				pclose($pipe);

				if($i==5)
				{
					$inbw = floatval($lines[0]);
					$outbw = floatval($lines[1]);
					$data_time = time();
				}
				else
				{
					warn("ExternalScript ReadData: Not enough lines read from external script ($i read, 4 expected)\n");
				}
			}
		}

		debug ("ExternalScript ReadData: Returning ($inbw,$outbw,$data_time)\n");

		return( array($inbw, $outbw, $data_time) );
	}
}

// vim:ts=4:sw=4:

?>
