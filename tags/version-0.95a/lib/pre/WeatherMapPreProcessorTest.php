<?php
// a test pre-processor plugin - does nothing but pollute the namespace
// the run()  method will be called just after the config is read, but before any rendering or data reading is done.

class WeatherMapPreProcessorTest extends WeatherMapPreProcessor {

	function run(&$map)
	{
		$map->add_note("test","TEST!");
		debug("Test Preprocessor in the hizouse\n");
	}

}

// vim:ts=4:sw=4:
?>
