<?php
// a test post-processor plugin - does nothing but pollute the namespace
// the run()  method will be called just before rendering starts
//
// To use this plugin, add the following line to your config:
//    SET post_test_enable 1
//
class WeatherMapPostProcessorTest extends WeatherMapPostProcessor 
{
	function run(&$map)
	{
		$enable = $map->get_hint("post_test_enable");

		if($enable)
		{
			debug(__CLASS__." is here\n");

			// do your work in here...

			$orig = $map->get_note("test");
			$map->add_note("test",$orig." TESTYTEST");
			// -------------------------
		}
		else
		{
			debug(__CLASS__." Not Enabled\n");
		}
	}
}

// vim:ts=4:sw=4:
?>
