<?php

namespace Weathermap\Core;

/**
 * The 'things on the map' class.
 */

class MapItem extends MapBase
{
    /** @var  Map $owner */
    public $owner;

    public $configline;
    public $infourl;
    public $overliburl;
    public $overlibwidth;
    public $overlibheight;
    public $overlibcaption;
//    public $my_default;
    public $definedIn;
    public $name;
    public $configOverride;    # used by the editor to allow text-editing
    /** @var HTMLImagemapArea[] $imagemapAreas */
    public $imagemapAreas;
    public $zorder;
    protected $descendents = array();
    protected $dependencies = array();

    public function __construct()
    {
        parent::__construct();

        $this->zorder = 1000;
        $this->imagemapAreas = array();
    }

    public function myType()
    {
        return 'ITEM';
    }

    public function getZIndex()
    {
        return $this->zorder;
    }

    public function getImageMapAreas()
    {
        return $this->imagemapAreas;
    }

    public function setDefined($source)
    {
        $this->definedIn = $source;
    }

    public function getDefined()
    {
        return $this->definedIn;
    }

    public function replaceConfig($newConfig)
    {
        $this->configOverride = $newConfig;
    }


    public function asConfigData()
    {
        $config = array(
            'name'=>$this->name,
            'type'=>$this->myType(),
            'vars'=>$this->hints
        );

        return $config;
    }

    /**
     * Used by processString to get internal properties of mapitems.
     * This used to be done by grabbing object properties directly, but they
     * are being renamed and cleaned up, so this function provides some
     * access-control (not everything should be accessible) and some translation.
     *
     * @param $name
     * @return null
     */
    public function getProperty($name)
    {
        return null;
    }
}
