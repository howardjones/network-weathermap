<?php
// a test pre-processor plugin - does nothing but pollute the namespace
// the run()  method will be called just after the config is read, but before any rendering or data reading is done.

namespace Weathermap\Plugins\Pre;

use Weathermap\Core\MapUtility;

class WeatherMapPreProcessorExample extends PreProcessorBase
{
    public function run()
    {
        $this->owner->add_note('test', 'TEST!');
        MapUtility::wm_debug("Example Preprocessor in the hizouse\n");
    }
}

// vim:ts=4:sw=4:
