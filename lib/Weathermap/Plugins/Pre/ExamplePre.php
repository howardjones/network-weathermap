<?php
// a test pre-processor plugin - does nothing but pollute the namespace
// the run()  method will be called just after the config is read, but before any rendering or data reading is done.

namespace Weathermap\Plugins\Pre;

use Weathermap\Core\MapUtility;

/**
 * Another do-very-little example
 *
 * @package Weathermap\Plugins\Pre
 */
class ExamplePre extends Base
{
    public function run()
    {
        $this->owner->addNote('test', 'TEST!');
        MapUtility::debug("Example Preprocessor in the hizouse\n");
    }
}

// vim:ts=4:sw=4:
