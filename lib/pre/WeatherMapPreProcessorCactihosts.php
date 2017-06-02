<?php
// a test pre-processor plugin - does nothing but pollute the namespace
// the run()  method will be called just after the config is read, but before any rendering or data reading is done.

class WeatherMapPreProcessorCactihosts extends WeatherMapPreProcessor
{
    public function run()
    {
        wm_debug("Cactihosts Preprocessor in the hizouse\n");

        # http://feathub.com/howardjones/network-weathermap/+9
    }
}
