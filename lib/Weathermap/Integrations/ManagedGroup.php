<?php

namespace Weathermap\Integrations;

/**
 * Some day we'll migrate to using this for the managed maps, and the various mutators below will be
 * methods here instead.
 */
class ManagedGroup
{
    public $id;
    public $name;
    public $sortOrder;

    public function __construct()
    {
    }

    public function maps()
    {
    }
}
