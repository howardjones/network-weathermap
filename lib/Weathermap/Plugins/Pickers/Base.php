<?php

namespace Weathermap\Plugins\Pickers;

/**
 * Future plan - the picker in the editor will use this class to present
 * TARGET and OVERLIBGRAPH options from sources other than Cacti.
 *
 */
class Base
{
    private $owner;

    public function init(&$map)
    {
        $this->owner = $map;

        return true;
    }
}
