<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 11:24
 */

namespace Weathermap\Plugins\Pickers;

/**
 * Class PickerBase
 *
 * Future plan - the picker in the editor will use this class to present
 * TARGET and OVERLIBGRAPH options from sources other than Cacti.
 *
 */
class PickerBase
{
    private $owner;

    public function Init(&$map)
    {
        $this->owner = $map;

        return true;
    }
}