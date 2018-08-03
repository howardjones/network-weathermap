<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 24/09/17
 * Time: 12:16
 */

namespace Weathermap\Core;

class WeathermapInternalFail extends \Exception
{
    // this is an assertion failure, to make testing easier
    // (e.g. something was called with invalid arguments)
}
