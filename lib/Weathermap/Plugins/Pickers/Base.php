<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 11:24
 */

namespace Weathermap\Plugins\Pickers;

/**
 * Class Base
 *
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
