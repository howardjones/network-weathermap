<?php
// a test post-processor plugin - does nothing but pollute the namespace
// the run()  method will be called just before rendering starts
//
// To use this plugin, add the following line to your config:
//    SET post_test_enable 1
//

namespace Weathermap\Plugins\Post;

use Weathermap\Core\MapUtility;

class WeatherMapPostProcessorExample extends PostProcessorBase
{
    public function run()
    {
        $enable = $this->owner->get_hint("post_test_enable");

        if ($enable) {
            MapUtility::wm_debug(__CLASS__ . " is here\n");

            // do your work in here...

            $orig = $this->owner->get_note("test");
            $this->owner->add_note("test", $orig . " TESTYTEST");
            // -------------------------
        } else {
            MapUtility::wm_debug(__CLASS__ . " Not Enabled\n");
        }
    }
}

// vim:ts=4:sw=4:
