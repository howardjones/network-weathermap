<?php

/*
 * This is the beginnings of a schema-based config parser with validation based on types
 */


class WeatherMapConfigSchemaItem {
    const WM_CONFIG_SCHEMA_STRING = 1;
    const WM_CONFIG_SCHEMA_POSITIVE_INT = 2;
    const WM_CONFIG_SCHEMA_INT = 3;
    const WM_CONFIG_SCHEMA_POSITIVE_FLOAT = 3;
    const WM_CONFIG_SCHEMA_FLOAT = 5;
    const WM_CONFIG_SCHEMA_BANDWIDTH = 6;
    const WM_CONFIG_SCHEMA_COLOUR = 7;
    const WM_CONFIG_SCHEMA_COLOUR_OR_NONE = 8;
    const WM_CONFIG_SCHEMA_COLOUR_OR_NONE_OR_CONTRAST = 9;
    const WM_CONFIG_SCHEMA_COLOUR_OR_CONTRAST = 10;
    const WM_CONFIG_SCHEMA_ENUM = 11;
    const WM_CONFIG_SCHEMA_BOOLEAN = 12;
    const WM_CONFIG_SCHEMA_PERCENTAGE = 13;
    const WM_CONFIG_SCHEMA_COORDINATE = 14;
    const WM_CONFIG_SCHEMA_NODENAME = 15;

    var $item_type;
    var $name;
    var $schema_type;
    var $enums = [];

    function WeatherMapConfigSchemaItem($name, $data_type, $enumvalues=array())
    {
        $this->name = $name;
        $this->schema_type = $data_type;
        $this->enums = $enumvalues;
    }
}

class WeatherMapConfigSchema {
    var $types = array();

    function WeatherMapConfigSchema()
    {
        $this->types['GLOBAL'] = array();
        $this->types['LINK'] = array();
        $this->types['NODE'] = array();
    }

    function loadSchema()
    {
        $this->types['NODE']['LABEL'] []= new WeatherMapConfigSchemaItem('LABEL',WeatherMapConfigSchemaItem::WM_CONFIG_SCHEMA_STRING);
        $this->types['NODE']['LABELBGCOLOR'] []= new WeatherMapConfigSchemaItem('LABELBGCOLOR',WeatherMapConfigSchemaItem::WM_CONFIG_SCHEMA_COLOUR_OR_NONE);
        $this->types['NODE']['LABELSTYLE'] []= new WeatherMapConfigSchemaItem('LABELSTYLE',WeatherMapConfigSchemaItem::WM_CONFIG_SCHEMA_ENUM,array('classic','angled'));
    }

    function getScopeSchema($scope)
    {
        return $this->types[$scope];
    }
}


class WeatherMapConfigItem {
    var $name;
    var $value;
    var $schema;
}

class WeatherMapConfigScope {
    var $parent;
    var $items = [];
    var $attached_object;
    var $children = [];
}

class WeatherMapConfig {
    var $global = null;
    var $nodes = array();
    var $links = array();

    function WeatherMapConfig()
    {
        $this->global = new WeatherMapConfigScope();

        $this->nodes['DEFAULT'] = new WeatherMapConfigScope();
        $this->links['DEFAULT'] = new WeatherMapConfigScope();
    }
}