<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 31/12/17
 * Time: 13:28
 */

namespace Weathermap\Core;


class Plugin
{
    public $name;
    public $object;
    public $active;
    public $type;
    public $source;

    public function __construct()
    {
        $this->active = false;
    }
}