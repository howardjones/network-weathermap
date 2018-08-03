<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 24/09/17
 * Time: 12:15
 */

namespace Weathermap\Core;

class WeathermapRuntimeWarning extends \Exception
{
    // These should become warnings in the log for a map
    // (e.g. fonts couldn't be loaded, targets not recognised)
}
