<?php
// a test post-processor plugin - does nothing but pollute the namespace
// the run()  method will be called just before rendering starts
//
// To use this plugin, add the following line to your config:
//    SET post_test_enable 1
//

namespace Weathermap\Plugins\Post;

use Weathermap\Core\MapUtility;

class ExamplePost extends Base
{
    public function run()
    {
        $enable = $this->owner->getHint("post_test_enable");

        if ($enable) {
            MapUtility::debug(__CLASS__ . " is here\n");

            // do your work in here...

            $orig = $this->owner->getNote("test");
            $this->owner->addNote("test", $orig . " TESTYTEST");
            // -------------------------
        } else {
            MapUtility::debug(__CLASS__ . " Not Enabled\n");
        }
    }
}

// vim:ts=4:sw=4:
