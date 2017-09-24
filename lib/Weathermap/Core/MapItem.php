<?php

namespace Weathermap\Core;

// The 'things on the map' class. More common code (mainly variables, actually)
class MapItem extends MapBase
{
    /** @var  Map $owner */
    var $owner;

    var $configline;
    var $infourl;
    var $overliburl;
    var $overlibwidth, $overlibheight;
    var $overlibcaption;
    var $my_default;
    var $defined_in;
    var $name;
    var $configOverride;    # used by the editor to allow text-editing
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
        return "ITEM";
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
        $this->defined_in = $source;
    }

    public function getDefined()
    {
        return $this->defined_in;
    }

    public function replaceConfig($newConfig)
    {
        $this->configOverride = $newConfig;
    }


    public function asConfigData()
    {
        $config = array(
            "name"=>$this->name,
            "type"=>$this->myType(),
            'vars'=>$this->hints
        );

        return $config;
    }
}
